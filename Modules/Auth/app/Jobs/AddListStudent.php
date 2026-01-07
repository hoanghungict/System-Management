<?php

declare(strict_types=1);

namespace Modules\Auth\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\ImportJob;
use Modules\Auth\app\Services\StudentImportService;
use Modules\Auth\app\Events\ImportProgressUpdated;
use Modules\Auth\app\Events\ImportCompleted;
use Modules\Auth\app\Events\ImportFailed;
use Modules\Auth\app\Models\AuditLog;

class AddListStudent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $importJobId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $importJobId)
    {
        $this->importJobId = $importJobId;
    }

    /**
     * Execute the job.
     */
    public function handle(StudentImportService $importService): void
    {
        $importJob = ImportJob::findOrFail($this->importJobId);
        
        // Guard: only process if job is still pending to avoid duplicate execution on retries
        if ($importJob->status !== 'pending') {
            Log::channel('daily')->warning('Skipping duplicate job run', [
                'import_job_id' => $this->importJobId,
                'status' => $importJob->status,
                'attempts' => optional($this->job)->attempts(),
            ]);

            return;
        }
        
        // Update status to processing
        $importJob->update(['status' => 'processing']);
        
        try {
            // Step 1: Resolve file path and count total rows
            $resolvedPath = $importService->getResolvedPath($importJob->file_path);

            if (empty($resolvedPath) || !file_exists($resolvedPath) || !is_readable($resolvedPath)) {
                $message = 'File không tồn tại hoặc worker không thể truy cập file. Kiểm tra shared storage/volume giữa web và queue worker.';

                // Update the import job with helpful diagnostic info
                $importJob->update([
                    'status' => 'failed',
                    'error' => $message
                ]);

                broadcast(new ImportFailed(
                    $this->importJobId,
                    $importJob->user_id ?? 0,
                    $message,
                    $importJob->failed
                ));

                AuditLog::log(
                    action: 'import.failed',
                    userId: $importJob->user_id,
                    targetType: ImportJob::class,
                    targetId: $this->importJobId,
                    data: [
                        'reason' => 'file_not_accessible',
                        'stored_path' => $importJob->file_path,
                        'resolved_path' => $resolvedPath
                    ]
                );

                Log::channel('daily')->error('Import failed - file not accessible', [
                    'import_job_id' => $this->importJobId,
                    'stored_path' => $importJob->file_path,
                    'resolved_path' => $resolvedPath
                ]);

                return;
            }

            // Normalize stored path to resolved absolute path for later steps (optional)
            if ($importJob->file_path !== $resolvedPath) {
                $importJob->update(['file_path' => $resolvedPath]);
            }

            $totalRows = $importService->countTotalRows($resolvedPath);
            $importJob->update(['total' => $totalRows]);
            
            if ($totalRows === 0) {
                throw new \Exception('File Excel trống hoặc không hợp lệ');
            }
            
            // Step 2: VALIDATE TẤT CẢ ROWS
            Log::channel('daily')->info('Starting validation', [
                'import_job_id' => $this->importJobId,
                'total_rows' => $totalRows
            ]);
            
            $errors = $importService->validateAllRows($importJob->file_path, $this->importJobId);
            
            if (!empty($errors)) {
                // Có lỗi → Dừng, không import gì
                $importJob->update([
                    'status' => 'failed',
                    'error' => 'Validation failed. Please fix errors and try again.',
                    'failed' => count($errors)
                ]);
                
                // Broadcast lỗi
                broadcast(new ImportFailed(
                    $this->importJobId,
                    $importJob->user_id ?? 0,
                    'Validation failed. Please check errors and try again.',
                    count($errors)
                ));
                
                // Ghi audit log
                AuditLog::log(
                    action: 'import.failed',
                    userId: $importJob->user_id,
                    targetType: ImportJob::class,
                    targetId: $this->importJobId,
                    data: [
                        'reason' => 'validation_failed',
                        'total_rows' => $totalRows,
                        'error_count' => count($errors)
                    ]
                );
                
                Log::channel('daily')->warning('Import validation failed', [
                    'import_job_id' => $this->importJobId,
                    'error_count' => count($errors)
                ]);
                
                return;
            }
            
            // Step 3: IMPORT TẤT CẢ trong transaction
            Log::channel('daily')->info('Starting import', [
                'import_job_id' => $this->importJobId,
                'total_rows' => $totalRows
            ]);
            
            DB::transaction(function () use ($importService, $importJob) {
                $importService->importAllRows($importJob->file_path, $importJob);
            });
            
            // Clear students cache to prevent stale data
            $studentService = app(\Modules\Auth\app\Services\AuthUserService\StudentService::class);
            $studentService->clearStudentsCache();
            
            Log::channel('daily')->info('Cleared students cache after import', [
                'import_job_id' => $this->importJobId
            ]);
            
            // Update status to done
            $importJob->update(['status' => 'done']);
            
            // Broadcast completed
            broadcast(new ImportCompleted(
                $this->importJobId,
                $importJob->user_id ?? 0,
                $importJob->total,
                $importJob->success,
                $importJob->failed
            ));
            
            // Ghi audit log
            AuditLog::log(
                action: 'import.completed',
                userId: $importJob->user_id,
                targetType: ImportJob::class,
                targetId: $this->importJobId,
                data: [
                    'total' => $importJob->total,
                    'success' => $importJob->success,
                    'failed' => $importJob->failed
                ]
            );
            
            Log::channel('daily')->info('Import completed successfully', [
                'import_job_id' => $this->importJobId,
                'success' => $importJob->success,
                'failed' => $importJob->failed
            ]);
            
        } catch (\Exception $e) {
            // Lỗi trong quá trình import
            $importJob->update([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
            
            // Broadcast lỗi
            broadcast(new ImportFailed(
                $this->importJobId,
                $importJob->user_id ?? 0,
                $e->getMessage(),
                $importJob->failed
            ));
            
            // Ghi audit log
            AuditLog::log(
                action: 'import.failed',
                userId: $importJob->user_id,
                targetType: ImportJob::class,
                targetId: $this->importJobId,
                data: [
                    'reason' => 'import_error',
                    'error' => $e->getMessage()
                ]
            );
            
            Log::channel('daily')->error('Import failed', [
                'import_job_id' => $this->importJobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}

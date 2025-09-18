<?php

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Task\app\Services\FileService;

/**
 * Job xử lý files cho Task
 * 
 * Job này xử lý các thao tác liên quan đến file như upload, process, validation
 */
class ProcessTaskFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Số lần retry tối đa
     */
    public $tries = 3;

    /**
     * Timeout cho job (giây)
     */
    public $timeout = 300;

    /**
     * Dữ liệu file cần xử lý
     */
    protected $fileData;

    /**
     * Loại xử lý cần thực hiện
     */
    protected $processType;

    /**
     * Task ID liên quan
     */
    protected $taskId;

    /**
     * Khởi tạo job
     * 
     * @param array $fileData Dữ liệu file
     * @param string $processType Loại xử lý
     * @param int $taskId Task ID
     */
    public function __construct(array $fileData, string $processType = 'upload', int $taskId = null)
    {
        $this->fileData = $fileData;
        $this->processType = $processType;
        $this->taskId = $taskId;
    }

    /**
     * Thực thi job
     * 
     * @param FileService $fileService
     * @return void
     */
    public function handle(FileService $fileService): void
    {
        try {
            Log::info('ProcessTaskFileJob started', [
                'file_name' => $this->fileData['name'] ?? 'unknown',
                'process_type' => $this->processType,
                'task_id' => $this->taskId
            ]);

            switch ($this->processType) {
                case 'upload_processing':
                    $this->processUpload($fileService);
                    break;
                    
                case 'file_validation':
                    $this->validateFile($fileService);
                    break;
                    
                case 'file_compression':
                    $this->compressFile($fileService);
                    break;
                    
                case 'file_conversion':
                    $this->convertFile($fileService);
                    break;
                    
                case 'cleanup_files':
                    $this->cleanupFiles($fileService);
                    break;
                    
                default:
                    $this->processDefault($fileService);
                    break;
            }

            Log::info('ProcessTaskFileJob completed successfully', [
                'file_name' => $this->fileData['name'] ?? 'unknown',
                'process_type' => $this->processType,
                'task_id' => $this->taskId
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessTaskFileJob failed', [
                'file_name' => $this->fileData['name'] ?? 'unknown',
                'process_type' => $this->processType,
                'task_id' => $this->taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Xử lý upload file
     * 
     * @param FileService $fileService
     * @return void
     */
    protected function processUpload(FileService $fileService): void
    {
        Log::info('Processing file upload', [
            'file_name' => $this->fileData['name'] ?? 'unknown',
            'task_id' => $this->taskId
        ]);

        // Xử lý upload file
        $processedFile = $fileService->processUpload($this->fileData);
        
        // Lưu thông tin file vào database
        if ($this->taskId && $processedFile) {
            \Modules\Task\app\Models\TaskFile::create([
                'task_id' => $this->taskId,
                'file_path' => $processedFile['path'],
                'original_name' => $this->fileData['name'] ?? 'unknown',
                'file_size' => $this->fileData['size'] ?? 0,
                'mime_type' => $this->fileData['mime_type'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Validate file
     * 
     * @param FileService $fileService
     * @return void
     */
    protected function validateFile(FileService $fileService): void
    {
        Log::info('Validating file', [
            'file_name' => $this->fileData['name'] ?? 'unknown'
        ]);

        $isValid = $fileService->validateFile($this->fileData);
        
        if (!$isValid) {
            Log::warning('File validation failed', $this->fileData);
            // Có thể gửi notification cho user
        }
    }

    /**
     * Compress file
     * 
     * @param FileService $fileService
     * @return void
     */
    protected function compressFile(FileService $fileService): void
    {
        Log::info('Compressing file', [
            'file_path' => $this->fileData['path'] ?? 'unknown'
        ]);

        $compressedPath = $fileService->compressFile($this->fileData['path'] ?? '');
        
        if ($compressedPath) {
            Log::info('File compressed successfully', [
                'original_path' => $this->fileData['path'] ?? 'unknown',
                'compressed_path' => $compressedPath
            ]);
        }
    }

    /**
     * Convert file format
     * 
     * @param FileService $fileService
     * @return void
     */
    protected function convertFile(FileService $fileService): void
    {
        Log::info('Converting file', [
            'file_path' => $this->fileData['path'] ?? 'unknown',
            'target_format' => $this->fileData['target_format'] ?? 'pdf'
        ]);

        $convertedPath = $fileService->convertFile(
            $this->fileData['path'] ?? '',
            $this->fileData['target_format'] ?? 'pdf'
        );
        
        if ($convertedPath) {
            Log::info('File converted successfully', [
                'original_path' => $this->fileData['path'] ?? 'unknown',
                'converted_path' => $convertedPath
            ]);
        }
    }

    /**
     * Cleanup files
     * 
     * @param FileService $fileService
     * @return void
     */
    protected function cleanupFiles(FileService $fileService): void
    {
        Log::info('Cleaning up files for task', ['task_id' => $this->taskId]);

        if ($this->taskId) {
            // Lấy danh sách files của task
            $taskFiles = \Modules\Task\app\Models\TaskFile::where('task_id', $this->taskId)->get();
            
            foreach ($taskFiles as $taskFile) {
                // Xóa file khỏi storage
                if (Storage::exists($taskFile->file_path)) {
                    Storage::delete($taskFile->file_path);
                    Log::info('File deleted from storage', ['file_path' => $taskFile->file_path]);
                }
                
                // Xóa record khỏi database
                $taskFile->delete();
            }
        }
    }

    /**
     * Xử lý mặc định
     * 
     * @param FileService $fileService
     * @return void
     */
    protected function processDefault(FileService $fileService): void
    {
        Log::info('Processing default file operations', [
            'file_name' => $this->fileData['name'] ?? 'unknown'
        ]);

        // Thực hiện tất cả các xử lý cơ bản
        $this->validateFile($fileService);
        $this->processUpload($fileService);
        
        Log::info('Default file processing completed', [
            'file_name' => $this->fileData['name'] ?? 'unknown'
        ]);
    }

    /**
     * Xử lý khi job fail
     * 
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessTaskFileJob failed permanently', [
            'file_name' => $this->fileData['name'] ?? 'unknown',
            'process_type' => $this->processType,
            'task_id' => $this->taskId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Gửi notification cho admin về job fail
        // Có thể cleanup temporary files nếu cần
    }
}

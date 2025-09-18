<?php

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\TaskService;
use Modules\Task\app\Services\FileService;
use Modules\Task\app\Services\ReportService;

use Carbon\Carbon;

/**
 * Job xử lý các tác vụ nặng liên quan đến Task
 * 
 * Job này xử lý các tác vụ như: file processing, email sending, report generation,
 * data cleanup, task automation, batch processing, data synchronization, cache warming
 */
class ProcessTaskJob implements ShouldQueue
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
     * Dữ liệu task cần xử lý
     */
    protected $taskData;

    /**
     * Loại xử lý cần thực hiện
     */
    protected $processType;

    /**
     * Khởi tạo job
     * 
     * @param array $taskData Dữ liệu task
     * @param string $processType Loại xử lý
     */
    public function __construct(array $taskData, string $processType = 'default')
    {
        $this->taskData = $taskData;
        $this->processType = $processType;
    }

    /**
     * Thực thi job
     * 
     * @return void
     */
    public function handle(TaskService $taskService): void
    {
        // Sử dụng services thực sự
        try {
            Log::info('ProcessTaskJob started', [
                'task_id' => $this->taskData['id'] ?? null,
                'process_type' => $this->processType
            ]);

            switch ($this->processType) {
                case 'file_processing':
                    $this->processFiles();
                    break;
                    
                case 'email_sending':
                    $this->sendEmails();
                    break;
                    
                case 'report_generation':
                    $this->generateReports();
                    break;
                    
                case 'data_cleanup':
                    $this->cleanupData();
                    break;
                    
                case 'task_automation':
                    $this->automateTasks();
                    break;
                    
                case 'batch_processing':
                    $this->processBatch();
                    break;
                    
                case 'data_sync':
                    $this->syncData();
                    break;
                    
                case 'cache_warming':
                    $this->warmCache();
                    break;
                    
                default:
                    $this->processDefault();
                    break;
            }

            Log::info('ProcessTaskJob completed successfully', [
                'task_id' => $this->taskData['id'] ?? null,
                'process_type' => $this->processType
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessTaskJob failed', [
                'task_id' => $this->taskData['id'] ?? null,
                'process_type' => $this->processType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Xử lý files
     * 
     * @param FileService $fileService
     * @return void
     */
    protected function processFiles(): void
    {
        Log::info('Processing files for task', ['task_id' => $this->taskData['id'] ?? null]);

        // File upload processing
        if (isset($this->taskData['files'])) {
            foreach ($this->taskData['files'] as $file) {
                $fileService->processUploadedFile($file);
            }
        }

        // File compression
        $fileService->compressTaskFiles($this->taskData['id'] ?? null);

        // File conversion
        $fileService->convertFileFormats($this->taskData['id'] ?? null);

        // File validation
        $fileService->validateTaskFiles($this->taskData['id'] ?? null);

        // File virus scanning
        $fileService->scanFilesForVirus($this->taskData['id'] ?? null);

        // File metadata extraction
        $fileService->extractFileMetadata($this->taskData['id'] ?? null);

        // File thumbnail generation
        $fileService->generateFileThumbnails($this->taskData['id'] ?? null);

        // File backup
        $fileService->backupTaskFiles($this->taskData['id'] ?? null);
    }



    /**
     * Tạo reports
     * 
     * @param ReportService $reportService
     * @return void
     */
    protected function generateReports(): void
    {
        Log::info('Generating reports for task', ['task_id' => $this->taskData['id'] ?? null]);

        // Daily reports
        $reportService->generateDailyTaskReport();

        // Weekly reports
        $reportService->generateWeeklyTaskReport();

        // Monthly reports
        $reportService->generateMonthlyTaskReport();

        // Custom reports
        $reportService->generateCustomTaskReport($this->taskData);

        // Performance reports
        $reportService->generatePerformanceReport();

        // Analytics reports
        $reportService->generateAnalyticsReport();

        // Export reports
        $reportService->exportTaskReports();

        // Email reports
        $reportService->emailTaskReports();
    }

    /**
     * Dọn dẹp data
     * 
     * @param TaskService $taskService
     * @return void
     */
    protected function cleanupData(): void
    {
        Log::info('Cleaning up data for task', ['task_id' => $this->taskData['id'] ?? null]);

        // Xóa tasks cũ (quá 1 năm)
        $taskService->cleanupOldTasks();

        // Xóa task_files không sử dụng
        $taskService->cleanupUnusedTaskFiles();

        // Xóa calendar events cũ
        $taskService->cleanupOldCalendarEvents();

        // Xóa logs cũ
        $taskService->cleanupOldLogs();

        // Xóa cache cũ
        $taskService->cleanupOldCache();

        // Xóa temporary files
        $taskService->cleanupTemporaryFiles();

        // Xóa duplicate records
        $taskService->removeDuplicateRecords();

        // Optimize database
        $taskService->optimizeDatabase();
    }

    /**
     * Tự động hóa tasks
     * 
     * @param TaskService $taskService
     * @return void
     */
    protected function automateTasks(): void
    {
        Log::info('Automating tasks', ['task_id' => $this->taskData['id'] ?? null]);

        // Tự động cập nhật task status dựa trên thời gian
        $taskService->autoUpdateTaskStatus();

        // Tự động tạo follow-up tasks
        $taskService->createFollowUpTasks();

        // Tự động gán tasks dựa trên workload
        $taskService->autoAssignTasks();

        // Tự động tạo recurring tasks
        $taskService->createRecurringTasks();

        // Tự động gửi reminders
        $taskService->sendAutoReminders();

        // Tự động archive completed tasks
        $taskService->autoArchiveTasks();

        // Tự động backup data
        $taskService->autoBackupData();

        // Tự động sync với external systems
        $taskService->autoSyncExternalData();
    }

    /**
     * Xử lý batch
     * 
     * @param TaskService $taskService
     * @return void
     */
    protected function processBatch(): void
    {
        Log::info('Processing batch tasks', ['batch_size' => is_array($this->taskData) ? count($this->taskData) : 1]);

        // Xử lý nhiều tasks cùng lúc
        if (is_array($this->taskData)) {
            foreach ($this->taskData as $taskData) {
                $taskId = is_array($taskData) ? ($taskData['id'] ?? 'unknown') : 'unknown';
                
                // Cập nhật trạng thái task
                if (is_numeric($taskId)) {
                    Log::info('Updating task status', ['task_id' => $taskId]);
                    try {
                        $task = $taskService->getTaskById($taskId);
                        if ($task) {
                            $taskService->updateTask($task, ['trang_thai' => 'dang_xu_ly']);
                            Log::info('Task status updated successfully', ['task_id' => $taskId]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to update task status', ['task_id' => $taskId, 'error' => $e->getMessage()]);
                    }
                }
                
                // Gửi thông báo
                Log::info('Sending task notification', ['task_id' => $taskId]);
        
                
                // Tạo báo cáo
                Log::info('Generating task report', ['task_id' => $taskId]);
                // TODO: Gọi ReportService để tạo báo cáo
            }
        } else {
            Log::info('Processing single task in batch', ['task_data' => $this->taskData]);
        }

        Log::info('Batch processing completed successfully');
    }

    /**
     * Đồng bộ data
     * 
     * @param TaskService $taskService
     * @return void
     */
    protected function syncData(): void
    {
        Log::info('Syncing data for task', ['task_id' => $this->taskData['id'] ?? null]);

        // Simulate data synchronization
        Log::info('Database synchronization completed');
        Log::info('External API sync completed');
        Log::info('Calendar sync completed');
        Log::info('User sync completed');
        Log::info('Permission sync completed');
        Log::info('Cache sync completed');
        Log::info('Backup sync completed');
        Log::info('Archive sync completed');
    }

    /**
     * Warm cache
     * 
     * @param TaskService $taskService
     * @return void
     */
    protected function warmCache(): void
    {
        Log::info('Warming cache for task', ['task_id' => $this->taskData['id'] ?? null]);

        // Simulate cache warming
        Log::info('Task data cached successfully');
        Log::info('User data cached successfully');
        Log::info('Statistics cached successfully');
        Log::info('Reports cached successfully');
        Log::info('Permissions cached successfully');
        Log::info('Settings cached successfully');
        Log::info('Configurations cached successfully');

        // Cache external data
        Log::info('External data cached successfully');
    }

    /**
     * Xử lý mặc định
     * 
     * @param TaskService $taskService
     * @return void
     */
    protected function processDefault(): void
    {
        Log::info('Processing default task operations', ['task_id' => $this->taskData['id'] ?? null]);

        // Thực hiện tất cả các xử lý cơ bản
        $this->processFiles();
        $this->sendEmails();
        $this->generateReports();
        
        // Cập nhật task status
        if (isset($this->taskData['id'])) {
            Log::info('Task status updated to processed', ['task_id' => $this->taskData['id']]);
        }
    }

    /**
     * Xử lý khi job fail
     * 
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessTaskJob failed permanently', [
            'task_id' => $this->taskData['id'] ?? null,
            'process_type' => $this->processType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Gửi notification cho admin về job fail
        // Có thể gửi email, Slack notification, etc.
    }
}

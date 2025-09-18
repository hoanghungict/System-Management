<?php

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Services\SyncService;

/**
 * Job đồng bộ dữ liệu Task
 * 
 * Job này xử lý việc đồng bộ dữ liệu Task với các hệ thống khác
 */
class SyncTaskDataJob implements ShouldQueue
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
     * Loại đồng bộ cần thực hiện
     */
    protected $syncType;

    /**
     * Tham số cho đồng bộ
     */
    protected $syncParams;

    /**
     * Khởi tạo job
     * 
     * @param string $syncType Loại đồng bộ
     * @param array $syncParams Tham số đồng bộ
     */
    public function __construct(string $syncType = 'database', array $syncParams = [])
    {
        $this->syncType = $syncType;
        $this->syncParams = $syncParams;
    }

    /**
     * Thực thi job
     * 
     * @param SyncService $syncService
     * @return void
     */
    public function handle(SyncService $syncService): void
    {
        try {
            Log::info('SyncTaskDataJob started', [
                'sync_type' => $this->syncType,
                'params' => $this->syncParams
            ]);

            switch ($this->syncType) {
                case 'database':
                    $this->syncDatabase($syncService);
                    break;
                    
                case 'external_api':
                    $this->syncExternalAPI($syncService);
                    break;
                    
                case 'calendar':
                    $this->syncCalendar($syncService);
                    break;
                    
                case 'users':
                    $this->syncUsers($syncService);
                    break;
                    
                case 'permissions':
                    $this->syncPermissions($syncService);
                    break;
                    
                case 'cache':
                    $this->syncCache($syncService);
                    break;
                    
                case 'backup':
                    $this->syncBackup($syncService);
                    break;
                    
                case 'archive':
                    $this->syncArchive($syncService);
                    break;
                    
                default:
                    $this->syncDefault($syncService);
                    break;
            }

            Log::info('SyncTaskDataJob completed successfully', [
                'sync_type' => $this->syncType,
                'params' => $this->syncParams
            ]);

        } catch (\Exception $e) {
            Log::error('SyncTaskDataJob failed', [
                'sync_type' => $this->syncType,
                'params' => $this->syncParams,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Đồng bộ database
     * 
     * @param SyncService $syncService
     * @return void
     */
    protected function syncDatabase(SyncService $syncService): void
    {
        Log::info('Syncing database for tasks', $this->syncParams);
        
        $result = $syncService->syncDatabase($this->syncParams);
        
        if ($result) {
            Log::info('Database sync completed successfully');
        } else {
            Log::warning('Database sync completed with warnings');
        }
    }

    /**
     * Đồng bộ External API
     * 
     * @param SyncService $syncService
     * @return void
     */
    protected function syncExternalAPI(SyncService $syncService): void
    {
        Log::info('Syncing external API for tasks', $this->syncParams);
        
        $result = $syncService->syncExternalAPI($this->syncParams);
        
        if ($result) {
            Log::info('External API sync completed successfully');
        } else {
            Log::warning('External API sync completed with warnings');
        }
    }

    /**
     * Đồng bộ Calendar
     * 
     * @param SyncService $syncService
     * @return void
     */
    protected function syncCalendar(SyncService $syncService): void
    {
        Log::info('Syncing calendar for tasks', $this->syncParams);
        
        $result = $syncService->syncCalendar($this->syncParams);
        
        if ($result) {
            Log::info('Calendar sync completed successfully');
        } else {
            Log::warning('Calendar sync completed with warnings');
        }
    }

    /**
     * Đồng bộ Users
     * 
     * @param SyncService $syncService
     * @return void
     */
    protected function syncUsers(SyncService $syncService): void
    {
        Log::info('Syncing users for tasks', $this->syncParams);
        
        $result = $syncService->syncUsers($this->syncParams);
        
        if ($result) {
            Log::info('Users sync completed successfully');
        } else {
            Log::warning('Users sync completed with warnings');
        }
    }

    /**
     * Đồng bộ Permissions
     * 
     * @param SyncService $syncService
     * @return void
     */
    protected function syncPermissions(SyncService $syncService): void
    {
        Log::info('Syncing permissions for tasks', $this->syncParams);
        
        $result = $syncService->syncPermissions($this->syncParams);
        
        if ($result) {
            Log::info('Permissions sync completed successfully');
        } else {
            Log::warning('Permissions sync completed with warnings');
        }
    }

    /**
     * Đồng bộ Cache
     * 
     * @param SyncService $syncService
     * @return void
     */
    protected function syncCache(SyncService $syncService): void
    {
        Log::info('Syncing cache for tasks', $this->syncParams);
        
        $result = $syncService->syncCache($this->syncParams);
        
        if ($result) {
            Log::info('Cache sync completed successfully');
        } else {
            Log::warning('Cache sync completed with warnings');
        }
    }

    /**
     * Đồng bộ Backup
     * 
     * @param SyncService $syncService
     * @return void
     */
    protected function syncBackup(SyncService $syncService): void
    {
        Log::info('Syncing backup for tasks', $this->syncParams);
        
        $result = $syncService->syncBackup($this->syncParams);
        
        if ($result) {
            Log::info('Backup sync completed successfully');
        } else {
            Log::warning('Backup sync completed with warnings');
        }
    }

    /**
     * Đồng bộ Archive
     * 
     * @param SyncService $syncService
     * @return void
     */
    protected function syncArchive(SyncService $syncService): void
    {
        Log::info('Syncing archive for tasks', $this->syncParams);
        
        $result = $syncService->syncArchive($this->syncParams);
        
        if ($result) {
            Log::info('Archive sync completed successfully');
        } else {
            Log::warning('Archive sync completed with warnings');
        }
    }

    /**
     * Đồng bộ mặc định
     * 
     * @param SyncService $syncService
     * @return void
     */
    protected function syncDefault(SyncService $syncService): void
    {
        Log::info('Performing default sync for tasks', $this->syncParams);
        
        // Thực hiện tất cả các đồng bộ cơ bản
        $this->syncDatabase($syncService);
        $this->syncCache($syncService);
        
        Log::info('Default sync completed successfully');
    }

    /**
     * Xử lý khi job fail
     * 
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncTaskDataJob failed permanently', [
            'sync_type' => $this->syncType,
            'params' => $this->syncParams,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Gửi notification cho admin về job fail
        // Có thể schedule lại job nếu cần
    }
}

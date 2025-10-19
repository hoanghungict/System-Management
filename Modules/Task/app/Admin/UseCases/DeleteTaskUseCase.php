<?php

declare(strict_types=1);

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Services\CacheInvalidationService;
use Modules\Task\app\Admin\UseCases\TaskCacheEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Delete Task Use Case
 * 
 * Handles soft deletion of tasks with proper permission checking
 * Follows Clean Architecture principles
 */
class DeleteTaskUseCase
{
    public function __construct(
        private PermissionService $permissionService,
        private CacheInvalidationService $cacheInvalidationService
    ) {}

    /**
     * Execute the delete task use case
     * 
     * @param int $taskId
     * @param int $userId
     * @param string $userType
     * @return array
     * @throws \Exception
     */
    public function execute(int $taskId, int $userId, string $userType): array
    {
        try {
            // Check admin permissions
            $userContext = (object) [
                'id' => $userId,
                'user_type' => $userType
            ];

            if (!$this->permissionService->isAdmin($userContext)) {
                throw new \Exception('Unauthorized: Admin access required');
            }

            // Find the task
            $task = Task::find($taskId);
            if (!$task) {
                throw new \Exception('Task not found');
            }

            // Check if task is already soft deleted
            if ($task->trashed()) {
                throw new \Exception('Task is already deleted');
            }

            DB::beginTransaction();

            try {
                // Soft delete the task
                $task->delete();

                // Invalidate related caches
                $this->cacheInvalidationService->invalidateTaskCache($taskId);
                $this->cacheInvalidationService->invalidateUserCache($userId);

                // Dispatch cache invalidation event
                event(new TaskCacheEvent('task_deleted', [
                    'task_id' => $taskId,
                    'user_id' => $userId,
                    'user_type' => $userType
                ]));

                DB::commit();

                Log::info('Task deleted successfully', [
                    'task_id' => $taskId,
                    'user_id' => $userId,
                    'user_type' => $userType,
                    'task_title' => $task->title
                ]);

                return [
                    'success' => true,
                    'message' => 'Task deleted successfully',
                    'data' => [
                        'task_id' => $taskId,
                        'deleted_at' => now()->toISOString()
                    ]
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete task', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}

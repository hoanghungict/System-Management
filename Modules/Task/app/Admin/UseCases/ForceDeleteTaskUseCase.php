<?php

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Services\CacheInvalidationService;
use Modules\Task\app\Admin\UseCases\TaskCacheEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Force Delete Task (Admin only)
 * 
 * This use case handles the business logic for force deleting a task
 * following Clean Architecture principles
 */
class ForceDeleteTaskUseCase
{
    public function __construct(
        private PermissionService $permissionService,
        private CacheInvalidationService $cacheInvalidationService
    ) {}

    /**
     * Execute force delete task
     * 
     * @param int $taskId
     * @param int $userId
     * @param string $userType
     * @return array
     * @throws \Exception
     */
    public function execute(int $taskId, int $userId, string $userType): array
    {
        // Check admin permission
        $userContext = (object) [
            'id' => $userId,
            'user_type' => $userType
        ];

        if (!$this->permissionService->isAdmin($userContext)) {
            throw new \Exception('Unauthorized: Admin access required');
        }

        // Find task (including soft deleted)
        $task = Task::find($taskId);

        if (!$task) {
            throw new \Exception('Task not found');
        }

        DB::beginTransaction();

        try {
            // Force delete the task
            $task->forceDelete();

            // Invalidate related caches
            $this->cacheInvalidationService->invalidateTaskCache($taskId);
            $this->cacheInvalidationService->invalidateUserCache($userId);

            // Dispatch cache invalidation event
            event(new TaskCacheEvent('task_force_deleted', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'user_type' => $userType
            ]));

            DB::commit();

            Log::info('Task force deleted successfully', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'user_type' => $userType,
                'task_title' => $task->title
            ]);

            return [
                'success' => true,
                'message' => 'Task force deleted successfully',
                'data' => [
                    'task_id' => $taskId,
                    'deleted_at' => now()->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
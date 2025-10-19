<?php

declare(strict_types=1);

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Services\CacheInvalidationService;
use Illuminate\Support\Facades\Log;

/**
 * Show Task Use Case
 * 
 * Handles task detail retrieval with proper permission checking
 * Follows Clean Architecture principles
 */
class ShowTaskUseCase
{
    public function __construct(
        private PermissionService $permissionService,
        private CacheInvalidationService $cacheInvalidationService
    ) {}

    /**
     * Execute the show task use case
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

            // Find the task with relationships
            $task = Task::with([
                'receivers',
                'files',
                'submissions',
                'creator',
                'receivers.receiver'
            ])->find($taskId);

            if (!$task) {
                throw new \Exception('Task not found');
            }

            Log::info('Task retrieved successfully', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'user_type' => $userType
            ]);

            return [
                'success' => true,
                'message' => 'Task retrieved successfully',
                'data' => $task
            ];
        } catch (\Exception $e) {
            Log::error('Failed to retrieve task', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
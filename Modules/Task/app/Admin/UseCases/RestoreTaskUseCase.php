<?php

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;

/**
 * Use Case: Restore Task (Admin only)
 * 
 * This use case handles the business logic for restoring a soft deleted task
 * following Clean Architecture principles
 */
class RestoreTaskUseCase
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Execute restore task
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

        // Find task with trashed (soft deleted) tasks
        $task = Task::withTrashed()->find($taskId);

        if (!$task) {
            throw new \Exception('Task not found');
        }

        if (!$task->trashed()) {
            throw new \Exception('Task is not deleted, cannot restore');
        }

        // Restore the task
        $task->restore();

        return [
            'success' => true,
            'message' => 'Task restored successfully',
            'task' => $task
        ];
    }
}

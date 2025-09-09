<?php

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;

/**
 * Use Case: Force Delete Task (Admin only)
 * 
 * This use case handles the business logic for force deleting a task
 * following Clean Architecture principles
 */
class ForceDeleteTaskUseCase
{
    public function __construct(
        private PermissionService $permissionService
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

        // Force delete the task
        $task->forceDelete();

        return [
            'success' => true,
            'message' => 'Task force deleted successfully',
            'task_id' => $taskId
        ];
    }
}

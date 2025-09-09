<?php

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;

/**
 * Use Case: Get Task Detail for Admin (Admin only)
 * 
 * This use case handles the business logic for retrieving task detail with admin privileges
 * following Clean Architecture principles
 */
class GetTaskDetailUseCase
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Execute get task detail
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

        // Find task with relationships
        $task = Task::with(['receivers', 'files'])
            ->find($taskId);
        
        if (!$task) {
            throw new \Exception('Task not found');
        }

        return [
            'success' => true,
            'message' => 'Task detail retrieved successfully',
            'task' => $task
        ];
    }
}

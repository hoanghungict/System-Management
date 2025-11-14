<?php

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Transformers\TaskResource;

/**
 * Use Case: Get Task Detail for Admin (Admin only)
 * 
 * This use case handles the business logic for retrieving task detail with admin privileges
 * following Clean Architecture principles
 */
class GetTaskDetailUseCase
{
    public function __construct(
        private PermissionService $permissionService,
        private TaskServiceInterface $taskService
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

        // ✅ Use TaskService with cache instead of direct database query
        $task = $this->taskService->getTaskById($taskId);
        
        if (!$task) {
            throw new \Exception('Task not found');
        }

        // ✅ Load relationships (files và receivers) để đảm bảo có trong response
        if (!$task->relationLoaded('receivers')) {
            $task->load('receivers');
        }
        if (!$task->relationLoaded('files')) {
            $task->load('files');
        }

        // ✅ Sử dụng TaskResource để format response đúng chuẩn với files
        return [
            'success' => true,
            'message' => 'Task detail retrieved successfully',
            'data' => new TaskResource($task)
        ];
    }
}

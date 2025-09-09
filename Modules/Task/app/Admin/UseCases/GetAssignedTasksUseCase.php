<?php

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;

/**
 * Use Case: Get Assigned Tasks (Admin only)
 * 
 * This use case handles the business logic for retrieving all assigned tasks
 * following Clean Architecture principles
 */
class GetAssignedTasksUseCase
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Execute get assigned tasks
     * 
     * @param int $userId
     * @param string $userType
     * @param int $perPage
     * @return array
     * @throws \Exception
     */
    public function execute(int $userId, string $userType, int $perPage = 15): array
    {
        // Check admin permission
        $userContext = (object) [
            'id' => $userId,
            'user_type' => $userType
        ];
        
        if (!$this->permissionService->isAdmin($userContext)) {
            throw new \Exception('Unauthorized: Admin access required');
        }

        // Get assigned tasks with pagination
        $tasks = Task::with(['receivers', 'files'])
            ->whereHas('receivers')
            ->paginate($perPage);

        return [
            'success' => true,
            'message' => 'Assigned tasks retrieved successfully',
            'tasks' => $tasks->items(),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage()
            ]
        ];
    }
}

<?php

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Services\PermissionService;

/**
 * Use Case: Check Admin Role (Admin only)
 * 
 * This use case handles the business logic for checking admin role
 * following Clean Architecture principles
 */
class CheckAdminRoleUseCase
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Execute check admin role
     * 
     * @param int $userId
     * @param string $userType
     * @return array
     */
    public function execute(int $userId, string $userType): array
    {
        $userContext = (object) [
            'id' => $userId,
            'user_type' => $userType
        ];
        
        $isAdmin = $this->permissionService->isAdmin($userContext);

        return [
            'success' => true,
            'message' => 'Admin role check completed',
            'user_id' => $userId,
            'user_type' => $userType,
            'is_admin' => $isAdmin,
            'timestamp' => now()
        ];
    }
}

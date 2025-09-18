<?php

namespace Modules\Task\app\UseCases;

use Modules\Task\app\Repositories\Interfaces\CachedUserRepositoryInterface;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Lấy danh sách classes theo department
 * 
 * Tuân thủ Clean Architecture: Use Case chỉ chứa business logic
 */
class GetClassesByDepartmentUseCase
{
    protected $taskService;

    /**
     * Khởi tạo Use Case với dependency injection
     * 
     * @param TaskServiceInterface $taskService Service chứa business logic
     */
    public function __construct(TaskServiceInterface $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Thực hiện lấy danh sách classes theo department
     * 
     * @param object $user User object
     * @param int $departmentId ID của department
     * @return array Danh sách classes
     * @throws TaskException Nếu có lỗi validation hoặc business logic
     */
    public function execute(object $user, int $departmentId): array
    {
        // ✅ Validate input
        $this->validateInput($user, $departmentId);

        // ✅ Lấy classes từ service
        $classes = $this->taskService->getClassesByDepartmentForUser($user, $departmentId);

        // ✅ Log success
        Log::info('Classes retrieved by department', [
            'user_id' => $user->id ?? 'unknown',
            'user_type' => $user->user_type ?? 'unknown',
            'department_id' => $departmentId,
            'classes_count' => count($classes)
        ]);

        return $classes;
    }

    /**
     * ✅ Validate input parameters
     * 
     * @param object $user User object
     * @param int $departmentId Department ID cần validate
     * @throws TaskException Nếu validation fail
     */
    private function validateInput(object $user, int $departmentId): void
    {
        // Validate user object
        if (!$user) {
            throw TaskException::validationFailed(
                'user',
                'User object is required',
                ['user' => 'null']
            );
        }

        // Validate user has required properties
        if (!isset($user->id) || !isset($user->user_type)) {
            throw TaskException::validationFailed(
                'user',
                'User must have id and user_type properties',
                ['user_properties' => array_keys((array) $user)]
            );
        }

        // Validate user type
        $validUserTypes = ['admin', 'lecturer'];
        if (!in_array($user->user_type, $validUserTypes)) {
            throw TaskException::validationFailed(
                'user_type',
                'User type must be admin or lecturer',
                [
                    'provided_type' => $user->user_type,
                    'valid_types' => $validUserTypes
                ]
            );
        }

        // Validate department ID
        if ($departmentId <= 0) {
            throw TaskException::validationFailed(
                'department_id',
                'Department ID must be positive',
                ['department_id' => $departmentId]
            );
        }
    }
}

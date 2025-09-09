<?php

namespace Modules\Task\app\UseCases;

use Modules\Task\app\Repositories\Interfaces\CachedUserRepositoryInterface;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Lấy danh sách students theo class
 * 
 * Tuân thủ Clean Architecture: Use Case chứa business logic cụ thể
 * Tách biệt khỏi Controller và Service
 */
class GetStudentsByClassUseCase
{
    public function __construct(
        private CachedUserRepositoryInterface $userRepo
    ) {}

    /**
     * Thực hiện lấy danh sách students theo class
     * 
     * @param object $user User hiện tại
     * @param int $classId ID của class
     * @return array Danh sách students
     * @throws TaskException Nếu có lỗi
     */
    public function execute(object $user, int $classId): array
    {
        try {
            // Validate input
            $this->validateInput($user, $classId);
            
            // Lấy students thông qua cached repository
            $students = $this->userRepo->getStudentsByClassForUser($user, $classId);
            
            // Log success
            Log::info('Students retrieved successfully via UseCase', [
                'user_id' => $user->id,
                'user_type' => $user->user_type ?? 'unknown',
                'class_id' => $classId,
                'students_count' => count($students)
            ]);
            
            return $students;
        } catch (\Exception $e) {
            Log::error('Error retrieving students via UseCase: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate input
     * 
     * @param object $user User cần validate
     * @param int $classId Class ID cần validate
     * @throws TaskException Nếu input không hợp lệ
     */
    private function validateInput(object $user, int $classId): void
    {
        if (!isset($user->id)) {
            throw TaskException::businessRuleViolation(
                'User ID is required',
                ['user' => $user]
            );
        }

        if (!isset($user->user_type)) {
            throw TaskException::businessRuleViolation(
                'User type is required',
                ['user' => $user]
            );
        }

        if ($classId <= 0) {
            throw TaskException::businessRuleViolation(
                'Class ID must be positive',
                ['class_id' => $classId]
            );
        }
    }
}

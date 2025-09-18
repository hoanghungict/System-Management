<?php

namespace Modules\Task\app\UseCases;

use Modules\Task\app\Repositories\Interfaces\CachedUserRepositoryInterface;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Lấy danh sách lecturers
 * 
 * Tuân thủ Clean Architecture: Use Case chứa business logic cụ thể
 * Tách biệt khỏi Controller và Service
 */
class GetLecturersUseCase
{
    public function __construct(
        private CachedUserRepositoryInterface $userRepo
    ) {}

    /**
     * Thực hiện lấy danh sách lecturers
     * 
     * @param object $user User hiện tại
     * @return array Danh sách lecturers
     * @throws TaskException Nếu có lỗi
     */
    public function execute(object $user): array
    {
        try {
            // Validate user
            $this->validateUser($user);
            
            // Kiểm tra quyền truy cập
            $this->checkAccessPermission($user);
            
            // Lấy lecturers thông qua cached repository
            $lecturers = $this->userRepo->getLecturersForUser($user);
            
            // Log success
            Log::info('Lecturers retrieved successfully via UseCase', [
                'user_id' => $user->id,
                'user_type' => $user->user_type ?? 'unknown',
                'lecturers_count' => count($lecturers)
            ]);
            
            return $lecturers;
        } catch (\Exception $e) {
            Log::error('Error retrieving lecturers via UseCase: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate user
     * 
     * @param object $user User cần validate
     * @throws TaskException Nếu user không hợp lệ
     */
    private function validateUser(object $user): void
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
    }

    /**
     * Kiểm tra quyền truy cập
     * 
     * @param object $user User cần kiểm tra quyền
     * @throws TaskException Nếu không có quyền
     */
    private function checkAccessPermission(object $user): void
    {
        $userType = $user->user_type ?? 'unknown';
        
        // Admin có thể xem tất cả lecturers
        if ($userType === 'admin') {
            return;
        }
        
        // Lecturer có thể xem lecturers trong cùng department
        if ($userType === 'lecturer') {
            return; // Cho phép lecturer xem lecturers
        }
        
        // Student không có quyền xem lecturers
        throw TaskException::unauthorized(
            'lecturers list',
            'access',
            ['user_type' => $userType]
        );
    }
}

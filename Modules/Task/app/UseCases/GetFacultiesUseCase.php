<?php

namespace Modules\Task\app\UseCases;

use Modules\Task\app\Repositories\Interfaces\CachedUserRepositoryInterface;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Lấy danh sách faculties cho user với cache
 * 
 * Tuân thủ Clean Architecture: Use Case chứa business logic cụ thể
 * Tách biệt khỏi Controller và Service
 */
class GetFacultiesUseCase
{
    public function __construct(
        private CachedUserRepositoryInterface $userRepo
    ) {}

    /**
     * Thực hiện lấy danh sách faculties
     * 
     * @param object $user User hiện tại
     * @return array Danh sách faculties
     * @throws TaskException Nếu có lỗi
     */
    public function execute(object $user): array
    {
        try {
            // Validate user
            $this->validateUser($user);
            
            // Lấy faculties thông qua cached repository
            $faculties = $this->userRepo->getFacultiesForUser($user);
            
            // Log success
            Log::info('Faculties retrieved successfully via UseCase', [
                'user_id' => $user->id,
                'user_type' => $user->user_type ?? 'unknown',
                'faculties_count' => count($faculties)
            ]);
            
            return $faculties;
        } catch (\Exception $e) {
            Log::error('Error retrieving faculties via UseCase: ' . $e->getMessage());
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
}

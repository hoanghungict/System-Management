<?php

namespace Modules\Task\app\UseCases;

use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Tạo Task mới với phân quyền
 * 
 * Tuân thủ Clean Architecture: Use Case chứa business logic cụ thể
 * Tách biệt khỏi Controller và Service
 */
class CreateTaskWithPermissionsUseCase
{
    public function __construct(
        private TaskServiceInterface $taskService,
        private CreateTaskUseCase $createTaskUseCase
    ) {}

    /**
     * Thực hiện tạo task mới với kiểm tra phân quyền
     * 
     * @param object $user User hiện tại
     * @param array $taskData Dữ liệu task
     * @return mixed Task đã được tạo
     * @throws TaskException Nếu có lỗi
     */
    public function execute(object $user, array $taskData): mixed
    {
        try {
            // Validate user
            $this->validateUser($user);
            
            // Kiểm tra quyền tạo task
            $this->checkCreatePermission($user, $taskData);
            
            // Tạo task thông qua CreateTaskUseCase
            $task = $this->createTaskUseCase->execute($taskData);
            
            // Log success
            /* Log::info('Task created successfully with permissions via UseCase', [
                'user_id' => $user->id,
                'user_type' => $user->user_type ?? 'unknown',
                'task_id' => $task->id,
                'title' => $task->title,
                'creator_id' => $task->creator_id
            ]); */
            
            return $task;
        } catch (\Exception $e) {
            Log::error('Error creating task with permissions via UseCase: ' . $e->getMessage());
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
     * Kiểm tra quyền tạo task
     * 
     * @param object $user User cần kiểm tra quyền
     * @param array $taskData Dữ liệu task
     * @throws TaskException Nếu không có quyền
     */
    private function checkCreatePermission(object $user, array $taskData): void
    {
        // Validate receivers data
        $receivers = $taskData['receivers'] ?? [];
        if (empty($receivers)) {
            throw TaskException::validationFailed(
                'receivers',
                'At least one receiver is required',
                ['user_id' => $user->id, 'user_type' => $user->user_type ?? 'unknown']
            );
        }

        if (!$this->taskService->canCreateTaskForReceiver($user, $taskData)) {
            throw TaskException::businessRuleViolation(
                'Bạn không có quyền tạo task cho người nhận này',
                [
                    'user_id' => $user->id,
                    'user_type' => $user->user_type ?? 'unknown',
                    'receivers' => $receivers
                ]
            );
        }
    }
}

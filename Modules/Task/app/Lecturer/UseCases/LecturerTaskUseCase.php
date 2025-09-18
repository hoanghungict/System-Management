<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\DTOs\TaskDTO;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Lecturer Task Use Case
 * 
 * Use Case dành riêng cho Giảng viên để quản lý tasks
 * Tuân theo Clean Architecture
 */
class LecturerTaskUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    /**
     * Lấy task theo ID với kiểm tra quyền
     */
    public function getTaskById($taskId, $lecturerId, $userType)
    {
        try {
            $task = $this->lecturerTaskRepository->findById($taskId);
            
            if (!$task) {
                throw new LecturerTaskException('Task not found', 404);
            }

            // Kiểm tra quyền truy cập
            if (!$this->hasAccessToTask($task, $lecturerId, $userType)) {
                throw new LecturerTaskException('Access denied to this task', 403);
            }

            return $task;
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Soft delete task với kiểm tra quyền
     */
    public function softDeleteTask($taskId, $lecturerId, $userType)
    {
        try {
            $task = $this->getTaskById($taskId, $lecturerId, $userType);
            
            // Kiểm tra quyền xóa
            if (!$this->canDeleteTask($task, $lecturerId, $userType)) {
                throw new LecturerTaskException('You do not have permission to delete this task', 403);
            }

            $this->lecturerTaskRepository->softDelete($taskId);
            
            return true;
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to delete task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Kiểm tra quyền truy cập task
     */
    protected function hasAccessToTask($task, $lecturerId, $userType)
    {
        // Giảng viên có thể truy cập task nếu:
        // 1. Là người tạo task
        // 2. Là người được giao task
        // 3. Là admin
        
        if ($task->creator_id == $lecturerId && $task->creator_type == 'lecturer') {
            return true;
        }

        // Kiểm tra xem có phải là receiver không
        foreach ($task->receivers as $receiver) {
            if ($receiver->receiver_id == $lecturerId && $receiver->receiver_type == 'lecturer') {
                return true;
            }
        }

        return false;
    }

    /**
     * Kiểm tra quyền xóa task
     */
    protected function canDeleteTask($task, $lecturerId, $userType)
    {
        // Chỉ người tạo task mới có thể xóa
        return $task->creator_id == $lecturerId && $task->creator_type == 'lecturer';
    }
}

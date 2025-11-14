<?php

declare(strict_types=1);

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Get Task Submissions Use Case
 * 
 * Cho phép lecturer xem danh sách submissions của task
 * Chỉ được xem submissions của task mà lecturer đã tạo
 */
class GetTaskSubmissionsUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    /**
     * Execute use case - lấy danh sách submissions
     * 
     * @param int $taskId - Task ID
     * @param int $lecturerId - Lecturer ID (từ JWT)
     * @param array $pagination - Pagination params (page, per_page)
     * @return array Returns submissions data with pagination
     * @throws LecturerTaskException Nếu không có quyền hoặc lỗi
     */
    public function execute(int $taskId, int $lecturerId, array $pagination = []): array
    {
        try {
            return $this->lecturerTaskRepository->getTaskSubmissions($taskId, $lecturerId, $pagination);
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Get task submissions error', [
                'task_id' => $taskId,
                'lecturer_id' => $lecturerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new LecturerTaskException(
                'Failed to retrieve task submissions: ' . $e->getMessage(),
                500
            );
        }
    }
}


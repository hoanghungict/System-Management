<?php

namespace Modules\Task\app\Services\Task;

use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service xử lý query/filter tasks
 */
class TaskQueryService
{
    protected $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Lấy tất cả tasks với filters (admin)
     */
    public function getAllTasks(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getAllTasksWithFilters($filters, $perPage);
    }

    /**
     * Lấy tasks với bộ lọc
     */
    public function getTasksWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getTasksWithFilters($filters, $perPage);
    }

    /**
     * Lấy tasks theo người nhận
     */
    public function getTasksByReceiver(int $receiverId, string $receiverType)
    {
        return $this->taskRepository->getTasksByReceiver($receiverId, $receiverType);
    }

    /**
     * Lấy tasks theo người tạo
     */
    public function getTasksByCreator(int $creatorId, string $creatorType)
    {
        return $this->taskRepository->getTasksByCreator($creatorId, $creatorType);
    }

    /**
     * Lấy tasks cho một user cụ thể
     */
    public function getTasksForUser(int $userId, string $userType)
    {
        return $this->taskRepository->getTasksForUser($userId, $userType);
    }

    /**
     * Lấy danh sách tasks cho người dùng hiện tại
     */
    public function getTasksForCurrentUser($user, int $perPage = 15): LengthAwarePaginator
    {
        if (!$user || !isset($user->id)) {
            return new LengthAwarePaginator([], 0, $perPage);
        }
        
        $userType = $this->getUserType($user);
        return $this->taskRepository->getTasksForUser($user->id, $userType, $perPage);
    }

    /**
     * Lấy danh sách tasks đã tạo bởi người dùng
     */
    public function getTasksCreatedByUser($user, int $perPage = 15): LengthAwarePaginator
    {
        if (!$user || !isset($user->id)) {
            return new LengthAwarePaginator([], 0, $perPage);
        }
        
        $userType = $this->getUserType($user);
        return $this->taskRepository->getTasksCreatedByUser($user->id, $userType, $perPage);
    }

    /**
     * Lấy loại user
     */
    private function getUserType($user): string
    {
        if (isset($user->user_type)) {
            return $user->user_type;
        }
        
        if ($user instanceof \Modules\Auth\app\Models\Lecturer) {
            return 'lecturer';
        } elseif ($user instanceof \Modules\Auth\app\Models\Student) {
            return 'student';
        }
        
        return 'unknown';
    }
}

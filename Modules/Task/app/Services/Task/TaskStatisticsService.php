<?php

namespace Modules\Task\app\Services\Task;

use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;

/**
 * Service xử lý thống kê task
 */
class TaskStatisticsService
{
    protected $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Lấy thống kê task tổng quan
     */
    public function getTaskStatistics(): array
    {
        return $this->taskRepository->getTaskStatistics();
    }

    /**
     * Lấy thống kê tasks của người dùng
     */
    public function getUserTaskStatistics($user): array
    {
        if (!$user || !isset($user->id)) {
            return $this->emptyStats();
        }
        
        $userType = $this->getUserType($user);
        return $this->taskRepository->getUserTaskStatistics($user->id, $userType);
    }

    /**
     * Lấy thống kê tasks đã tạo
     */
    public function getCreatedTaskStatistics($user): array
    {
        if (!$user || !isset($user->id)) {
            return $this->emptyStats();
        }
        
        $userType = $this->getUserType($user);
        return $this->taskRepository->getCreatedTaskStatistics($user->id, $userType);
    }

    /**
     * Lấy thống kê tổng quan (admin)
     */
    public function getOverviewTaskStatistics(): array
    {
        return $this->taskRepository->getOverviewTaskStatistics();
    }

    /**
     * Trả về stats rỗng
     */
    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];
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

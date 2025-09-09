<?php

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Repositories\Interfaces\CachedTaskRepositoryInterface;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Modules\Task\app\Services\Interfaces\CacheServiceInterface;
use Modules\Task\app\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Cached Task Repository Implementation
 * 
 * Tuân thủ Clean Architecture: Implementation của CachedTaskRepositoryInterface
 * Kết hợp cache với database operations
 */
class CachedTaskRepository implements CachedTaskRepositoryInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private CacheServiceInterface $cacheService
    ) {}

    /**
     * Lấy task theo ID với cache
     * 
     * @param int $id Task ID
     * @return Task|null
     */
    public function findById(int $id): ?Task
    {
        $cacheKey = $this->cacheService->generateKey('task', ['id' => $id]);
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () use ($id) {
            return $this->taskRepository->findById($id);
        }, 'normal');
    }

    /**
     * Lấy tất cả tasks với cache và pagination
     * 
     * @param int $perPage Số lượng per page
     * @return LengthAwarePaginator
     */
    public function getAllTasks(int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->cacheService->generateKey('tasks_all', ['per_page' => $perPage]);
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () use ($perPage) {
            return $this->taskRepository->getAllTasks($perPage);
        }, 'important');
    }

    /**
     * Lấy tasks với filters và cache
     * 
     * @param array $filters Filters
     * @param int $perPage Số lượng per page
     * @return LengthAwarePaginator
     */
    public function getTasksWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->cacheService->generateKey('tasks_filters', [
            'filters' => $filters,
            'per_page' => $perPage
        ]);
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () use ($filters, $perPage) {
            return $this->taskRepository->getTasksWithFilters($filters, $perPage);
        }, 'important');
    }

    /**
     * Lấy tasks cho user với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param int $perPage Số lượng per page
     * @return LengthAwarePaginator
     */
    public function getTasksForUser(int $userId, string $userType, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->cacheService->generateKey('tasks_user', [
            'user_id' => $userId,
            'user_type' => $userType,
            'per_page' => $perPage
        ]);
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () use ($userId, $userType, $perPage) {
            return $this->taskRepository->getTasksForUser($userId, $userType, $perPage);
        }, 'normal');
    }

    /**
     * Lấy tasks đã tạo bởi user với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param int $perPage Số lượng per page
     * @return LengthAwarePaginator
     */
    public function getTasksCreatedByUser(int $userId, string $userType, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->cacheService->generateKey('tasks_created', [
            'user_id' => $userId,
            'user_type' => $userType,
            'per_page' => $perPage
        ]);
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () use ($userId, $userType, $perPage) {
            return $this->taskRepository->getTasksCreatedByUser($userId, $userType, $perPage);
        }, 'normal');
    }

    /**
     * Lấy task statistics với cache
     * 
     * @return array
     */
    public function getTaskStatistics(): array
    {
        $cacheKey = $this->cacheService->generateKey('task_statistics');
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () {
            return $this->taskRepository->getTaskStatistics();
        }, 'critical');
    }

    /**
     * Lấy upcoming tasks với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param int $days Số ngày
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUpcomingTasks(int $userId, string $userType, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->cacheService->generateKey('tasks_upcoming', [
            'user_id' => $userId,
            'user_type' => $userType,
            'days' => $days
        ]);
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () use ($userId, $userType, $days) {
            return Task::where('deadline', '>=', now())
                ->where('deadline', '<=', now()->addDays($days))
                ->whereHas('receivers', function ($q) use ($userId, $userType) {
                    $q->where('receiver_id', $userId)
                      ->where('receiver_type', $userType);
                })
                ->with(['receivers'])
                ->orderBy('deadline')
                ->get();
        }, 'important');
    }

    /**
     * Lấy overdue tasks với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueTasks(int $userId, string $userType): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->cacheService->generateKey('tasks_overdue', [
            'user_id' => $userId,
            'user_type' => $userType
        ]);
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () use ($userId, $userType) {
            return Task::where('deadline', '<', now())
                ->whereHas('receivers', function ($q) use ($userId, $userType) {
                    $q->where('receiver_id', $userId)
                      ->where('receiver_type', $userType);
                })
                ->with(['receivers'])
                ->orderBy('deadline')
                ->get();
        }, 'important');
    }

    /**
     * Lấy tasks theo date range với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksByDateRange(int $userId, string $userType, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->cacheService->generateKey('tasks_date_range', [
            'user_id' => $userId,
            'user_type' => $userType,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () use ($userId, $userType, $startDate, $endDate) {
            return Task::whereBetween('deadline', [$startDate, $endDate])
                ->whereHas('receivers', function ($q) use ($userId, $userType) {
                    $q->where('receiver_id', $userId)
                      ->where('receiver_type', $userType);
                })
                ->with(['receivers'])
                ->orderBy('deadline')
                ->get();
        }, 'normal');
    }

    /**
     * Lấy tasks theo status với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param string $status Status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksByStatus(int $userId, string $userType, string $status): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->cacheService->generateKey('tasks_status', [
            'user_id' => $userId,
            'user_type' => $userType,
            'status' => $status
        ]);
        
        return $this->cacheService->rememberWithLevel($cacheKey, function () use ($userId, $userType, $status) {
            return Task::where('status', $status)
                ->whereHas('receivers', function ($q) use ($userId, $userType) {
                    $q->where('receiver_id', $userId)
                      ->where('receiver_type', $userType);
                })
                ->with(['receivers'])
                ->orderBy('deadline')
                ->get();
        }, 'normal');
    }

    /**
     * Xóa cache cho task
     * 
     * @param int $taskId Task ID
     * @return bool
     */
    public function clearTaskCache(int $taskId): bool
    {
        $patterns = [
            'task:' . $taskId,
            'tasks_*',
            'task_statistics'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('Task cache cleared', ['task_id' => $taskId]);
        
        return true;
    }

    /**
     * Xóa cache cho user tasks
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @return bool
     */
    public function clearUserTasksCache(int $userId, string $userType): bool
    {
        $patterns = [
            'tasks_user:*user_id=' . $userId . '*',
            'tasks_created:*user_id=' . $userId . '*',
            'tasks_upcoming:*user_id=' . $userId . '*',
            'tasks_overdue:*user_id=' . $userId . '*',
            'tasks_date_range:*user_id=' . $userId . '*',
            'tasks_status:*user_id=' . $userId . '*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('User tasks cache cleared', [
            'user_id' => $userId,
            'user_type' => $userType
        ]);
        
        return true;
    }

    /**
     * Xóa tất cả task cache
     * 
     * @return bool
     */
    public function clearAllTaskCache(): bool
    {
        $patterns = [
            'task:*',
            'tasks_*',
            'task_statistics'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('All task cache cleared');
        
        return true;
    }
}

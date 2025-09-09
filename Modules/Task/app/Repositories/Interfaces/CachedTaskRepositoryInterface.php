<?php

namespace Modules\Task\app\Repositories\Interfaces;

use Modules\Task\app\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface cho Cached Task Repository
 * 
 * Tuân thủ Clean Architecture: Interface định nghĩa contract cho cached task operations
 */
interface CachedTaskRepositoryInterface
{
    /**
     * Lấy task theo ID với cache
     * 
     * @param int $id Task ID
     * @return Task|null
     */
    public function findById(int $id): ?Task;

    /**
     * Lấy tất cả tasks với cache và pagination
     * 
     * @param int $perPage Số lượng per page
     * @return LengthAwarePaginator
     */
    public function getAllTasks(int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy tasks với filters và cache
     * 
     * @param array $filters Filters
     * @param int $perPage Số lượng per page
     * @return LengthAwarePaginator
     */
    public function getTasksWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy tasks cho user với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param int $perPage Số lượng per page
     * @return LengthAwarePaginator
     */
    public function getTasksForUser(int $userId, string $userType, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy tasks đã tạo bởi user với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param int $perPage Số lượng per page
     * @return LengthAwarePaginator
     */
    public function getTasksCreatedByUser(int $userId, string $userType, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy task statistics với cache
     * 
     * @return array
     */
    public function getTaskStatistics(): array;

    /**
     * Lấy upcoming tasks với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param int $days Số ngày
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUpcomingTasks(int $userId, string $userType, int $days = 7): \Illuminate\Database\Eloquent\Collection;

    /**
     * Lấy overdue tasks với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueTasks(int $userId, string $userType): \Illuminate\Database\Eloquent\Collection;

    /**
     * Lấy tasks theo date range với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksByDateRange(int $userId, string $userType, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection;

    /**
     * Lấy tasks theo status với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param string $status Status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksByStatus(int $userId, string $userType, string $status): \Illuminate\Database\Eloquent\Collection;

    /**
     * Xóa cache cho task
     * 
     * @param int $taskId Task ID
     * @return bool
     */
    public function clearTaskCache(int $taskId): bool;

    /**
     * Xóa cache cho user tasks
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @return bool
     */
    public function clearUserTasksCache(int $userId, string $userType): bool;

    /**
     * Xóa tất cả task cache
     * 
     * @return bool
     */
    public function clearAllTaskCache(): bool;
}

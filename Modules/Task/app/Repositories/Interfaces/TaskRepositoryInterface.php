<?php

namespace Modules\Task\app\Repositories\Interfaces;

use Modules\Task\app\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface cho TaskRepository
 * 
 * Tuân thủ Clean Architecture: Dependency Inversion Principle
 * Service phụ thuộc vào abstraction, không phụ thuộc vào concrete implementation
 */
interface TaskRepositoryInterface
{
    /**
     * Tìm task theo ID
     */
    public function findById(int $id): ?Task;

    /**
     * Lấy tất cả tasks với phân trang
     */
    public function getAllTasks(int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy tasks cho user cụ thể
     */
    public function getTasksForUser(int $userId, string $userType, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy tasks đã tạo bởi user
     */
    public function getTasksCreatedByUser(int $userId, string $userType, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy tasks với bộ lọc
     */
    public function getTasksWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Tạo task mới
     */
    public function create(array $data): Task;

    /**
     * Cập nhật task
     */
    public function update(Task $task, array $data): Task;

    /**
     * Xóa task
     */
    public function delete(Task $task): bool;

    /**
     * Kiểm tra user có phải là receiver của task không
     */
    public function isTaskReceiver(Task $task, int $userId, string $userType): bool;

    /**
     * Lấy thống kê tasks của user
     */
    public function getUserTaskStatistics(int $userId, string $userType): array;

    /**
     * Lấy thống kê tasks đã tạo
     */
    public function getCreatedTaskStatistics(int $userId, string $userType): array;

    /**
     * Lấy thống kê tổng quan
     */
    public function getOverviewTaskStatistics(): array;

    /**
     * Thêm receiver cho task
     */
    public function addReceiverToTask(array $receiverData): void;

    /**
     * Xóa tất cả receivers của task
     */
    public function deleteAllTaskReceivers(int $taskId): bool;

    /**
     * Tạo task file
     */
    public function createTaskFile(array $fileData): mixed;

    /**
     * Tìm task file
     */
    public function findTaskFile(int $fileId): mixed;

    /**
     * Xóa task file
     */
    public function deleteTaskFile(int $fileId): bool;

    /**
     * Force delete task
     */
    public function forceDelete(Task $task): bool;

    /**
     * Restore task
     */
    public function restore(Task $task): bool;

    /**
     * Lấy tasks được gán cho user
     */
    public function getTasksAssignedToUser(int $userId, string $userType): Collection;

    /**
     * Lấy tasks cho class của user
     */
    public function getTasksForUserClass(int $userId, string $userType): Collection;

    /**
     * Lấy tasks cho department của user
     */
    public function getTasksForUserDepartment(int $userId, string $userType): Collection;

    /**
     * Kiểm tra xem user có phải là receiver cho classes không
     */
    public function isTaskReceiverForClasses(Task $task, int $userId, string $userType): bool;

    /**
     * Lấy tasks theo người nhận (cũ - deprecated)
     */
    public function getTasksByReceiver(int $receiverId, string $receiverType): Collection;

    /**
     * Lấy tasks theo người tạo
     */
    public function getTasksByCreator(int $creatorId, string $creatorType): Collection;

    /**
     * Lấy tasks có files
     */
    public function getTasksWithFiles(): Collection;

    /**
     * Lấy tasks có calendar events
     */
    public function getTasksWithCalendarEvents(): Collection;

    /**
     * Lấy thống kê task
     */
    public function getTaskStatistics(): array;

    /**
     * Lấy tất cả tasks với filters (admin)
     */
    public function getAllTasksWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
}

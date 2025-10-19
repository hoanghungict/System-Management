<?php

namespace Modules\Task\app\Repositories\Interfaces;

use Modules\Task\app\Models\TaskDependency;
use Illuminate\Database\Eloquent\Collection;

interface TaskDependencyRepositoryInterface
{
    /**
     * Tạo dependency mới
     */
    public function create(array $data): TaskDependency;

    /**
     * Lấy dependency theo ID
     */
    public function findById(int $id): ?TaskDependency;

    /**
     * Lấy dependencies của một task
     */
    public function getTaskDependencies(int $taskId): Collection;

    /**
     * Lấy dependencies với thông tin chi tiết
     */
    public function getTaskDependenciesWithDetails(int $taskId): Collection;

    /**
     * Lấy tasks bị block bởi task hiện tại
     */
    public function getBlockedTasks(int $taskId): Collection;

    /**
     * Cập nhật dependency
     */
    public function update(int $id, array $data): bool;

    /**
     * Xóa dependency
     */
    public function delete(int $id): bool;

    /**
     * Kiểm tra circular dependency
     */
    public function checkCircularDependency(int $predecessorId, int $successorId): bool;

    /**
     * Kiểm tra xem task có thể bắt đầu không
     */
    public function canTaskStart(int $taskId): bool;

    /**
     * Lấy dependency chain của task
     */
    public function getDependencyChain(int $taskId): array;

    /**
     * Lấy tất cả dependencies
     */
    public function getAll(): Collection;

    /**
     * Lấy dependencies theo type
     */
    public function getByType(string $type): Collection;

    /**
     * Đếm số dependencies của task
     */
    public function countTaskDependencies(int $taskId): int;

    /**
     * Kiểm tra dependency tồn tại
     */
    public function dependencyExists(int $predecessorId, int $successorId): bool;

    /**
     * Lấy thống kê dependencies
     */
    public function getDependencyStatistics(): array;
}
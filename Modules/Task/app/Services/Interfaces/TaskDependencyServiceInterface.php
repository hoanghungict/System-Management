<?php

namespace Modules\Task\app\Services\Interfaces;

use Modules\Task\app\Models\TaskDependency;
use Illuminate\Database\Eloquent\Collection;

interface TaskDependencyServiceInterface
{
    /**
     * Tạo dependency mới
     */
    public function createDependency(array $data): TaskDependency;

    /**
     * Lấy dependency theo ID
     */
    public function getDependencyById(int $id): ?TaskDependency;

    /**
     * Lấy dependencies của task
     */
    public function getTaskDependencies(int $taskId): Collection;

    /**
     * Lấy dependencies với thông tin chi tiết
     */
    public function getTaskDependenciesWithDetails(int $taskId): Collection;

    /**
     * Cập nhật dependency
     */
    public function updateDependency(int $id, array $data): TaskDependency;

    /**
     * Xóa dependency
     */
    public function deleteDependency(int $id): bool;

    /**
     * Validate dependency trước khi tạo
     */
    public function validateDependency(array $data): array;

    /**
     * Kiểm tra circular dependency
     */
    public function checkCircularDependency(int $predecessorId, int $successorId): bool;

    /**
     * Kiểm tra xem task có thể bắt đầu không
     */
    public function canTaskStart(int $taskId): bool;

    /**
     * Lấy tasks bị block bởi task hiện tại
     */
    public function getBlockedTasks(int $taskId): Collection;

    /**
     * Lấy dependency chain
     */
    public function getDependencyChain(int $taskId): array;

    /**
     * Lấy thống kê dependencies
     */
    public function getDependencyStatistics(): array;

    /**
     * Lấy task với tất cả dependencies
     */
    public function getTaskWithDependencies(int $taskId): ?array;

    /**
     * Lấy tất cả dependencies
     */
    public function getAllDependencies(): Collection;
}

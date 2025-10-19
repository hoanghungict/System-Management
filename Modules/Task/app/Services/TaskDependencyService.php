<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Models\TaskDependency;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Repositories\Interfaces\TaskDependencyRepositoryInterface;
use Modules\Task\app\Services\Interfaces\TaskDependencyServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskDependencyService implements TaskDependencyServiceInterface
{
    protected $repository;

    public function __construct(TaskDependencyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Tạo dependency mới
     */
    public function createDependency(array $data): TaskDependency
    {
        // Validate trước khi tạo
        $validation = $this->validateDependency($data);
        if (!$validation['isValid']) {
            throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
        }

        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    /**
     * Lấy dependency theo ID
     */
    public function getDependencyById(int $id): ?TaskDependency
    {
        return $this->repository->findById($id);
    }

    /**
     * Lấy dependencies của task
     */
    public function getTaskDependencies(int $taskId): Collection
    {
        return $this->repository->getTaskDependencies($taskId);
    }

    /**
     * Lấy dependencies với thông tin chi tiết
     */
    public function getTaskDependenciesWithDetails(int $taskId): Collection
    {
        return $this->repository->getTaskDependenciesWithDetails($taskId);
    }

    /**
     * Cập nhật dependency
     */
    public function updateDependency(int $id, array $data): TaskDependency
    {
        $dependency = $this->repository->findById($id);
        if (!$dependency) {
            throw new \Exception('Dependency not found');
        }

        // Validate nếu có thay đổi về task IDs
        if (isset($data['predecessor_task_id']) || isset($data['successor_task_id'])) {
            $validationData = array_merge($dependency->toArray(), $data);
            $validation = $this->validateDependency($validationData);
            if (!$validation['isValid']) {
                throw new \Exception('Validation failed: ' . implode(', ', $validation['errors']));
            }
        }

        $updated = $this->repository->update($id, $data);
        if (!$updated) {
            throw new \Exception('Failed to update dependency');
        }

        return $this->repository->findById($id);
    }

    /**
     * Xóa dependency
     */
    public function deleteDependency(int $id): bool
    {
        $dependency = $this->repository->findById($id);
        if (!$dependency) {
            throw new \Exception('Dependency not found');
        }

        return $this->repository->delete($id);
    }

    /**
     * Validate dependency trước khi tạo
     */
    public function validateDependency(array $data): array
    {
        $errors = [];
        $warnings = [];

        // Kiểm tra required fields
        if (!isset($data['predecessor_task_id']) || !isset($data['successor_task_id'])) {
            $errors[] = 'Predecessor task ID and successor task ID are required';
            return ['isValid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $predecessorId = $data['predecessor_task_id'];
        $successorId = $data['successor_task_id'];

        // Không thể phụ thuộc vào chính mình
        if ($predecessorId === $successorId) {
            $errors[] = 'Task cannot depend on itself';
            return ['isValid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Kiểm tra tasks tồn tại
        $predecessorTask = Task::find($predecessorId);
        $successorTask = Task::find($successorId);

        if (!$predecessorTask) {
            $errors[] = 'Predecessor task not found';
        }

        if (!$successorTask) {
            $errors[] = 'Successor task not found';
        }

        // Kiểm tra circular dependency
        if ($this->checkCircularDependency($predecessorId, $successorId)) {
            $errors[] = 'This dependency would create a circular dependency';
        }

        // Kiểm tra dependency đã tồn tại
        if ($this->repository->dependencyExists($predecessorId, $successorId)) {
            $errors[] = 'Dependency already exists between these tasks';
        }

        // Kiểm tra dependency type
        if (isset($data['dependency_type'])) {
            $validTypes = ['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'];
            if (!in_array($data['dependency_type'], $validTypes)) {
                $errors[] = 'Invalid dependency type';
            }
        }

        // Kiểm tra lag_days
        if (isset($data['lag_days']) && $data['lag_days'] < 0) {
            $warnings[] = 'Negative lag days may cause unexpected behavior';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Kiểm tra circular dependency
     */
    public function checkCircularDependency(int $predecessorId, int $successorId): bool
    {
        return $this->repository->checkCircularDependency($predecessorId, $successorId);
    }

    /**
     * Kiểm tra xem task có thể bắt đầu không
     */
    public function canTaskStart(int $taskId): bool
    {
        return $this->repository->canTaskStart($taskId);
    }

    /**
     * Lấy tasks bị block bởi task hiện tại
     */
    public function getBlockedTasks(int $taskId): Collection
    {
        return $this->repository->getBlockedTasks($taskId);
    }

    /**
     * Lấy dependency chain
     */
    public function getDependencyChain(int $taskId): array
    {
        return $this->repository->getDependencyChain($taskId);
    }

    /**
     * Lấy thống kê dependencies
     */
    public function getDependencyStatistics(): array
    {
        return $this->repository->getDependencyStatistics();
    }

    /**
     * Lấy task với tất cả dependencies
     */
    public function getTaskWithDependencies(int $taskId): ?array
    {
        $task = Task::find($taskId);
        if (!$task) {
            return null;
        }

        $dependencies = $this->getTaskDependenciesWithDetails($taskId);
        $blockedTasks = $this->getBlockedTasks($taskId);
        $canStart = $this->canTaskStart($taskId);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
            'priority' => $task->priority,
            'deadline' => $task->deadline,
            'description' => $task->description,
            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at,
            'dependencies' => $dependencies->map(function ($dependency) {
                return [
                    'id' => $dependency->id,
                    'depends_on_task_id' => $dependency->predecessor_task_id,
                    'dependency_type' => $dependency->dependency_type,
                    'lag_days' => $dependency->lag_days,
                    'task_title' => $dependency->predecessorTask?->title,
                    'task_status' => $dependency->predecessorTask?->status,
                ];
            })->toArray(),
            'dependents' => $blockedTasks->map(function ($dependency) {
                return [
                    'id' => $dependency->id,
                    'task_id' => $dependency->successor_task_id,
                    'dependency_type' => $dependency->dependency_type,
                    'lag_days' => $dependency->lag_days,
                    'task_title' => $dependency->successorTask?->title,
                    'task_status' => $dependency->successorTask?->status,
                ];
            })->toArray(),
            'canStart' => $canStart,
            'blockedBy' => $dependencies->pluck('predecessor_task_id')->toArray(),
            'isBlocked' => !$canStart
        ];
    }

    /**
     * Lấy tất cả dependencies
     */
    public function getAllDependencies(): Collection
    {
        return $this->repository->getAll();
    }
}
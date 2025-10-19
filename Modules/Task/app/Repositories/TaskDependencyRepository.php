<?php

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Models\TaskDependency;
use Modules\Task\app\Repositories\Interfaces\TaskDependencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TaskDependencyRepository implements TaskDependencyRepositoryInterface
{
    protected $model;

    public function __construct(TaskDependency $model)
    {
        $this->model = $model;
    }

    /**
     * Tạo dependency mới
     */
    public function create(array $data): TaskDependency
    {
        return $this->model->create($data);
    }

    /**
     * Lấy dependency theo ID
     */
    public function findById(int $id): ?TaskDependency
    {
        return $this->model->with(['predecessorTask', 'successorTask'])->find($id);
    }

    /**
     * Lấy dependencies của một task
     */
    public function getTaskDependencies(int $taskId): Collection
    {
        return $this->model->forTask($taskId)
            ->with(['predecessorTask'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy dependencies với thông tin chi tiết
     */
    public function getTaskDependenciesWithDetails(int $taskId): Collection
    {
        return $this->model->forTask($taskId)
            ->with([
                'predecessorTask' => function ($query) {
                    $query->select('id', 'title', 'status', 'priority', 'deadline', 'description');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy tasks bị block bởi task hiện tại
     */
    public function getBlockedTasks(int $taskId): Collection
    {
        return $this->model->asPredecessor($taskId)
            ->with(['successorTask'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Cập nhật dependency
     */
    public function update(int $id, array $data): bool
    {
        $dependency = $this->model->find($id);
        if (!$dependency) {
            return false;
        }
        return $dependency->update($data);
    }

    /**
     * Xóa dependency
     */
    public function delete(int $id): bool
    {
        $dependency = $this->model->find($id);
        if (!$dependency) {
            return false;
        }
        return $dependency->delete();
    }

    /**
     * Kiểm tra circular dependency
     */
    public function checkCircularDependency(int $predecessorId, int $successorId): bool
    {
        // Không thể phụ thuộc vào chính mình
        if ($predecessorId === $successorId) {
            return true;
        }

        // Sử dụng DFS để kiểm tra circular dependency
        $visited = [];
        $recursionStack = [];

        return $this->hasCycle($successorId, $predecessorId, $visited, $recursionStack);
    }

    /**
     * DFS để kiểm tra cycle
     */
    private function hasCycle(int $currentTaskId, int $targetTaskId, array &$visited, array &$recursionStack): bool
    {
        if (in_array($currentTaskId, $recursionStack)) {
            return true;
        }

        if (in_array($currentTaskId, $visited)) {
            return false;
        }

        $visited[] = $currentTaskId;
        $recursionStack[] = $currentTaskId;

        // Lấy tất cả dependencies của task hiện tại
        $dependencies = $this->model->forTask($currentTaskId)->get();

        foreach ($dependencies as $dependency) {
            if ($this->hasCycle($dependency->predecessor_task_id, $targetTaskId, $visited, $recursionStack)) {
                return true;
            }
        }

        array_pop($recursionStack);
        return false;
    }

    /**
     * Kiểm tra xem task có thể bắt đầu không
     */
    public function canTaskStart(int $taskId): bool
    {
        $dependencies = $this->getTaskDependencies($taskId);

        foreach ($dependencies as $dependency) {
            if (!$dependency->canTaskStart()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Lấy dependency chain của task
     */
    public function getDependencyChain(int $taskId): array
    {
        $chain = [];
        $visited = [];

        $this->buildDependencyChain($taskId, $chain, $visited, 0);

        return $chain;
    }

    /**
     * Xây dựng dependency chain
     */
    private function buildDependencyChain(int $taskId, array &$chain, array &$visited, int $level): void
    {
        if (in_array($taskId, $visited)) {
            return;
        }

        $visited[] = $taskId;
        $dependencies = $this->getTaskDependencies($taskId);

        foreach ($dependencies as $dependency) {
            $chain[] = [
                'taskId' => $dependency->predecessor_task_id,
                'title' => $dependency->predecessorTask?->title ?? 'Unknown Task',
                'level' => $level + 1,
                'dependencyType' => $dependency->dependency_type,
                'lagDays' => $dependency->lag_days
            ];

            $this->buildDependencyChain($dependency->predecessor_task_id, $chain, $visited, $level + 1);
        }
    }

    /**
     * Lấy tất cả dependencies
     */
    public function getAll(): Collection
    {
        return $this->model->with(['predecessorTask', 'successorTask'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy dependencies theo type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->byType($type)
            ->with(['predecessorTask', 'successorTask'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Đếm số dependencies của task
     */
    public function countTaskDependencies(int $taskId): int
    {
        return $this->model->forTask($taskId)->count();
    }

    /**
     * Kiểm tra dependency tồn tại
     */
    public function dependencyExists(int $predecessorId, int $successorId): bool
    {
        return $this->model->where('predecessor_task_id', $predecessorId)
            ->where('successor_task_id', $successorId)
            ->exists();
    }

    /**
     * Lấy thống kê dependencies
     */
    public function getDependencyStatistics(): array
    {
        $totalDependencies = $this->model->count();
        $byType = $this->model->selectRaw('dependency_type, COUNT(*) as count')
            ->groupBy('dependency_type')
            ->pluck('count', 'dependency_type')
            ->toArray();

        $tasksWithDependencies = $this->model->distinct('successor_task_id')->count('successor_task_id');
        $averageDependenciesPerTask = $tasksWithDependencies > 0 ? round($totalDependencies / $tasksWithDependencies, 2) : 0;

        return [
            'totalDependencies' => $totalDependencies,
            'byType' => [
                'finish_to_start' => $byType['finish_to_start'] ?? 0,
                'start_to_start' => $byType['start_to_start'] ?? 0,
                'finish_to_finish' => $byType['finish_to_finish'] ?? 0,
                'start_to_finish' => $byType['start_to_finish'] ?? 0,
            ],
            'tasksWithDependencies' => $tasksWithDependencies,
            'averageDependenciesPerTask' => $averageDependenciesPerTask,
            'circularDependencies' => 0, // TODO: Implement circular dependency detection
            'blockedTasks' => $this->model->whereHas('predecessorTask', function ($query) {
                $query->where('status', '!=', 'completed');
            })->count()
        ];
    }
}
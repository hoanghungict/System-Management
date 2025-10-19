<?php

declare(strict_types=1);

namespace Modules\Task\app\Services;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Models\TaskDependency;
use Modules\Task\app\Admin\UseCases\TaskCacheEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Task Status Management Service
 * 
 * Handles automatic status transitions based on dependencies
 */
class TaskStatusService
{
    /**
     * Check and update task status based on dependencies
     * 
     * @param int $taskId
     * @return bool True if status was updated
     */
    public function checkAndUpdateTaskStatus(int $taskId): bool
    {
        $task = Task::find($taskId);
        if (!$task) {
            return false;
        }

        // Only update if task is pending
        if ($task->status !== 'pending') {
            return false;
        }

        // Check if all dependencies are completed
        $canStart = $this->canTaskStart($taskId);

        if ($canStart) {
            return $this->updateTaskStatusToInProgress($taskId);
        }

        return false;
    }

    /**
     * Update task status to in_progress
     * 
     * @param int $taskId
     * @return bool
     */
    public function updateTaskStatusToInProgress(int $taskId): bool
    {
        try {
            DB::beginTransaction();

            $task = Task::find($taskId);
            if (!$task) {
                return false;
            }

            $task->update(['status' => 'in_progress']);

            // Dispatch cache invalidation event
            event(new TaskCacheEvent('task_status_updated', [
                'task_id' => $taskId,
                'old_status' => 'pending',
                'new_status' => 'in_progress',
                'reason' => 'dependencies_completed'
            ]));

            DB::commit();

            Log::info('Task status updated to in_progress', [
                'task_id' => $taskId,
                'reason' => 'dependencies_completed'
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update task status to in_progress', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if task can start (all dependencies completed)
     * 
     * @param int $taskId
     * @return bool
     */
    public function canTaskStart(int $taskId): bool
    {
        $dependencies = TaskDependency::where('successor_task_id', $taskId)
            ->with('predecessorTask')
            ->get();

        if ($dependencies->isEmpty()) {
            return true; // No dependencies, can start
        }

        // Check if all predecessor tasks are completed
        foreach ($dependencies as $dependency) {
            if (!$dependency->predecessorTask || $dependency->predecessorTask->status !== 'completed') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a task can be completed (all dependencies must be completed)
     * 
     * @param int $taskId
     * @return bool
     */
    public function canTaskBeCompleted(int $taskId): bool
    {
        Log::info("TaskStatusService: Checking if task {$taskId} can be completed...");

        $task = Task::find($taskId);
        if (!$task) {
            Log::warning("TaskStatusService: Task {$taskId} not found");
            return false;
        }

        // Get all dependencies for this task
        $dependencies = TaskDependency::where('successor_task_id', $taskId)->get();
        Log::info("TaskStatusService: Found {$dependencies->count()} dependencies for task {$taskId}");

        if ($dependencies->isEmpty()) {
            // No dependencies, can be completed
            Log::info("TaskStatusService: Task {$taskId} has no dependencies, can be completed");
            return true;
        }

        // Check if all dependencies are completed
        foreach ($dependencies as $dependency) {
            $depTask = Task::find($dependency->predecessor_task_id);
            if (!$depTask || $depTask->status !== 'completed') {
                Log::info("TaskStatusService: Task {$taskId} cannot be completed. Dependency {$dependency->predecessor_task_id} is not completed.", [
                    'task_id' => $taskId,
                    'dependency_id' => $dependency->predecessor_task_id,
                    'dependency_status' => $depTask ? $depTask->status : 'not_found'
                ]);
                return false;
            }
        }

        Log::info("TaskStatusService: Task {$taskId} can be completed. All dependencies are completed.", [
            'task_id' => $taskId,
            'dependencies_count' => $dependencies->count()
        ]);

        return true;
    }

    /**
     * Get tasks that can be started (dependencies completed)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksReadyToStart()
    {
        return Task::where('status', 'pending')
            ->whereHas('dependencies', function ($query) {
                $query->whereHas('predecessorTask', function ($subQuery) {
                    $subQuery->where('status', 'completed');
                });
            })
            ->orWhereDoesntHave('dependencies')
            ->get();
    }

    /**
     * Process all pending tasks and update their status if ready
     * 
     * @return int Number of tasks updated
     */
    public function processPendingTasks(): int
    {
        $updatedCount = 0;
        $readyTasks = $this->getTasksReadyToStart();

        foreach ($readyTasks as $task) {
            if ($this->canTaskStart($task->id)) {
                if ($this->updateTaskStatusToInProgress($task->id)) {
                    $updatedCount++;
                }
            }
        }

        Log::info('Processed pending tasks', [
            'total_checked' => $readyTasks->count(),
            'updated_count' => $updatedCount
        ]);

        return $updatedCount;
    }
}
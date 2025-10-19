<?php

declare(strict_types=1);

namespace Modules\Task\app\Listeners;

use Modules\Task\app\Services\TaskStatusService;
use Modules\Task\app\Admin\UseCases\TaskCacheEvent;
use Illuminate\Support\Facades\Log;

/**
 * Task Status Event Listener
 * 
 * Handles automatic status transitions when tasks are completed
 */
class TaskStatusEventListener
{
    protected TaskStatusService $taskStatusService;

    public function __construct(TaskStatusService $taskStatusService)
    {
        $this->taskStatusService = $taskStatusService;
    }

    /**
     * Handle task completion event
     * 
     * @param TaskCacheEvent $event
     * @return void
     */
    public function handleTaskCompleted(TaskCacheEvent $event): void
    {
        // Only process task_updated events with completed status
        if ($event->key !== 'task_updated') {
            return;
        }

        $metadata = $event->metadata;
        if (!isset($metadata['task_id']) || !isset($metadata['updated_fields'])) {
            return;
        }

        // Check if status was updated to completed
        if (!in_array('status', $metadata['updated_fields'])) {
            return;
        }

        $taskId = $metadata['task_id'];

        // Get the task to check its current status
        $task = \Modules\Task\app\Models\Task::find($taskId);
        if (!$task || $task->status !== 'completed') {
            return;
        }

        Log::info('Task completed, checking dependent tasks', [
            'completed_task_id' => $taskId
        ]);

        // Find all tasks that depend on this completed task
        $dependentTasks = \Modules\Task\app\Models\TaskDependency::where('predecessor_task_id', $taskId)
            ->with('successorTask')
            ->get();

        foreach ($dependentTasks as $dependency) {
            $successorTask = $dependency->successorTask;
            if (!$successorTask) {
                continue;
            }

            // Check if the dependent task can now start
            if ($this->taskStatusService->checkAndUpdateTaskStatus($successorTask->id)) {
                Log::info('Dependent task status updated', [
                    'dependent_task_id' => $successorTask->id,
                    'dependent_task_title' => $successorTask->title,
                    'completed_task_id' => $taskId
                ]);
            }
        }
    }

    /**
     * Handle task status update event
     * 
     * @param TaskCacheEvent $event
     * @return void
     */
    public function handleTaskStatusUpdated(TaskCacheEvent $event): void
    {
        if ($event->key !== 'task_status_updated') {
            return;
        }

        $metadata = $event->metadata;
        if (!isset($metadata['task_id']) || !isset($metadata['new_status'])) {
            return;
        }

        Log::info('Task status updated', [
            'task_id' => $metadata['task_id'],
            'old_status' => $metadata['old_status'] ?? 'unknown',
            'new_status' => $metadata['new_status'],
            'reason' => $metadata['reason'] ?? 'manual'
        ]);
    }
}
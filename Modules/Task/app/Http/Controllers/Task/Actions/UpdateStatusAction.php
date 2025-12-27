<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Task\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;

/**
 * Action: Update task status
 */
class UpdateStatusAction extends BaseTaskAction
{
    public function __construct(
        private readonly TaskServiceInterface $taskService
    ) {}

    public function __invoke(Request $request, int $taskId): JsonResponse
    {
        try {
            $userData = $this->getUserData($request);
            
            if (!$userData) {
                return $this->unauthorizedResponse();
            }

            $task = $this->taskService->getTaskById($taskId);

            if (!$task) {
                return $this->notFoundResponse('Task');
            }

            $validated = $request->validate([
                'status' => 'required|string|in:pending,in_progress,completed,cancelled'
            ]);

            $updatedTask = $this->taskService->updateTaskStatus($task, $validated['status'], $userData);

            return $this->successResponse($updatedTask, 'Task status updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update task status', $e);
        }
    }
}

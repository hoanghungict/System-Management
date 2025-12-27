<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;

/**
 * Action: Delete a task
 */
class DeleteTaskAction extends BaseLecturerAction
{
    public function __construct(
        private readonly TaskServiceInterface $taskService
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            $userData = $this->getUserData($request);
            
            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return $this->notFoundResponse('Task');
            }

            // Check if lecturer created this task
            if ($task->creator_id !== $userId || $task->creator_type !== 'lecturer') {
                return $this->accessDeniedResponse();
            }

            $this->taskService->deleteTask($task, $userData);

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete task', $e);
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;

/**
 * Action: Duplicate a task
 */
class DuplicateTaskAction extends BaseLecturerAction
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

            $data = $task->toArray();
            unset($data['id'], $data['created_at'], $data['updated_at']);
            
            $data['title'] = $data['title'] . ' (Copy)';
            $data['status'] = 'pending';
            $data['creator_id'] = $userId;
            $data['creator_type'] = 'lecturer';

            $newTask = $this->taskService->createTask($data, $userData);

            return $this->successResponse($newTask, 'Task duplicated successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to duplicate task', $e);
        }
    }
}

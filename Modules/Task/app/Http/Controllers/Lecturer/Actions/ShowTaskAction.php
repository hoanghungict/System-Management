<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Transformers\TaskResource;

/**
 * Action: Show a specific task
 */
class ShowTaskAction extends BaseLecturerAction
{
    public function __construct(
        private readonly TaskServiceInterface $taskService
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        try {
            $request = request();
            $userId = $this->getUserId($request);
            
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

            // Load relationships
            if (!$task->relationLoaded('receivers')) {
                $task->load('receivers');
            }
            if (!$task->relationLoaded('files')) {
                $task->load('files');
            }

            return $this->successResponse(
                new TaskResource($task), 
                'Task retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve task', $e);
        }
    }
}

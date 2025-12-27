<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Modules\Task\app\Http\Requests\TaskRequest;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;

/**
 * Action: Create a new task
 */
class CreateTaskAction extends BaseLecturerAction
{
    public function __construct(
        private readonly TaskServiceInterface $taskService
    ) {}

    public function __invoke(TaskRequest $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            $userData = $this->getUserData($request);
            
            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $data = $request->validated();
            $data['creator_id'] = $userId;
            $data['creator_type'] = 'lecturer';

            $task = $this->taskService->createTask($data, $userData);

            return $this->successResponse($task, 'Task created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create task', $e);
        }
    }
}

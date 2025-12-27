<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Http\Requests\TaskRequest;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\PermissionService;

/**
 * Action: Update an existing task
 */
class UpdateTaskAction extends BaseLecturerAction
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly PermissionService $permissionService
    ) {}

    public function __invoke(TaskRequest $request, int $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            $userType = $this->getUserType($request);
            
            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return $this->notFoundResponse('Task');
            }

            $data = $request->validated();
            unset($data['creator_id'], $data['creator_type']);
            
            $userContext = $this->createUserContext($request);
            
            // Clear permission cache and check
            $this->permissionService->clearPermissionCache($userContext, $id);
            
            Log::info('Lecturer update task - Permission check', [
                'task_id' => $id,
                'lecturer_id' => $userId,
                'is_creator' => $task->creator_id == $userId && $task->creator_type == ($userType ?? 'lecturer'),
            ]);
            
            if (!$this->permissionService->canEditTask($userContext, $id)) {
                Log::warning('Lecturer update task - Permission denied', [
                    'task_id' => $id,
                    'lecturer_id' => $userId,
                ]);
                
                return $this->accessDeniedResponse(
                    'Access denied. You can only update tasks you created or tasks assigned to you.'
                );
            }
            
            $updatedTask = $this->taskService->updateTask($task, $data, $userContext);

            return $this->successResponse($updatedTask, 'Task updated successfully');
        } catch (\Exception $e) {
            Log::error('Lecturer update task error', [
                'task_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to update task', $e);
        }
    }
}

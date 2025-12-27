<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Task\Actions;

use Illuminate\Http\JsonResponse;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Transformers\TaskResource;

/**
 * Action: Show a specific task
 */
class ShowAction extends BaseTaskAction
{
    public function __construct(
        private readonly TaskServiceInterface $taskService
    ) {}

    public function __invoke(int $taskId): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($taskId);

            if (!$task) {
                return $this->notFoundResponse('Task');
            }

            $task->load(['files', 'receivers']);

            return response()->json([
                'success' => true,
                'message' => 'Task retrieved successfully',
                'data' => new TaskResource($task)
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve task', $e);
        }
    }
}

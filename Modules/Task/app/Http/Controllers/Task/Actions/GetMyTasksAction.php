<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Task\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Transformers\TaskCollection;

/**
 * Action: Get current user's tasks
 */
class GetMyTasksAction extends BaseTaskAction
{
    public function __construct(
        private readonly TaskServiceInterface $taskService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userData = $this->getUserData($request);
            
            if (!$userData) {
                return $this->unauthorizedResponse();
            }

            $perPage = (int) $request->get('per_page', 15);
            $tasks = $this->taskService->getTasksForCurrentUser($userData, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'My tasks retrieved successfully',
                'data' => new TaskCollection($tasks)
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve my tasks', $e);
        }
    }
}

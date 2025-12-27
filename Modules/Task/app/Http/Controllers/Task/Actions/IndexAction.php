<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Task\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Transformers\TaskResource;
use Modules\Task\app\Transformers\TaskCollection;

/**
 * Action: List all tasks with filters
 */
class IndexAction extends BaseTaskAction
{
    public function __construct(
        private readonly TaskServiceInterface $taskService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userData = $this->getUserData($request);
            $filters = $request->only(['status', 'priority', 'search', 'date_from', 'date_to']);
            $perPage = (int) $request->get('per_page', 15);

            $tasks = $this->taskService->getAllTasks($filters, $perPage, $userData);

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
                'data' => new TaskCollection($tasks)
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve tasks', $e);
        }
    }
}

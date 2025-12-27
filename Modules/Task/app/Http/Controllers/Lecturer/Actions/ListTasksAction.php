<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;

/**
 * Action: List tasks created by the lecturer
 */
class ListTasksAction extends BaseLecturerAction
{
    public function __construct(
        private readonly TaskServiceInterface $taskService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $filters = $request->only([
                'status', 'priority', 'class_id', 'date_from', 'date_to', 'search'
            ]);

            $filters['creator_id'] = $userId;
            $filters['creator_type'] = 'lecturer';

            $tasks = $this->taskService->getTasksByCreator(
                $userId, 
                'lecturer', 
                $filters, 
                $request->get('limit', 20)
            );

            return $this->successResponse($tasks, 'Lecturer tasks retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve tasks', $e);
        }
    }
}

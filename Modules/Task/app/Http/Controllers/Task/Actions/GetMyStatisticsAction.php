<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Task\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;

/**
 * Action: Get user's task statistics
 */
class GetMyStatisticsAction extends BaseTaskAction
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

            $statistics = $this->taskService->getUserTaskStatistics($userData);

            return $this->successResponse($statistics, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics', $e);
        }
    }
}

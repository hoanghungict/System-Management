<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Lecturer\UseCases\GetTaskSubmissionsUseCase;

/**
 * Action: Get task submissions for grading
 */
class GetSubmissionsAction extends BaseLecturerAction
{
    public function __construct(
        private readonly GetTaskSubmissionsUseCase $getTaskSubmissionsUseCase
    ) {}

    public function __invoke(Request $request, int $taskId): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $submissions = $this->getTaskSubmissionsUseCase->execute($taskId, $userId);

            return $this->successResponse($submissions, 'Task submissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve submissions', $e);
        }
    }
}

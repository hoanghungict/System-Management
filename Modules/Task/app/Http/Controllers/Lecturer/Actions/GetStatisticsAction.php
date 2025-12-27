<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Lecturer\UseCases\GetLecturerTasksUseCase;

/**
 * Action: Get lecturer statistics
 */
class GetStatisticsAction extends BaseLecturerAction
{
    public function __construct(
        private readonly GetLecturerTasksUseCase $getLecturerTasksUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $statistics = $this->getLecturerTasksUseCase->getLecturerStatistics($userId);

            return response()->json([
                'success' => true,
                'message' => 'Lecturer statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Modules\Task\app\Lecturer\Exceptions\LecturerTaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?? 500);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve lecturer statistics', $e);
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Lecturer\UseCases\GradeTaskSubmissionUseCase;

/**
 * Action: Grade a task submission
 */
class GradeSubmissionAction extends BaseLecturerAction
{
    public function __construct(
        private readonly GradeTaskSubmissionUseCase $gradeTaskSubmissionUseCase
    ) {}

    public function __invoke(Request $request, int $taskId, int $submissionId): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $data = $request->validate([
                'grade' => 'required|numeric|min:0|max:100',
                'feedback' => 'nullable|string|max:2000',
            ]);

            $result = $this->gradeTaskSubmissionUseCase->execute(
                $taskId,
                $submissionId,
                $userId,
                $data['grade'],
                $data['feedback'] ?? null
            );

            return $this->successResponse($result, 'Submission graded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to grade submission', $e);
        }
    }
}

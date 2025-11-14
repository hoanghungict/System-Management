<?php

declare(strict_types=1);

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Grade Task Submission Use Case
 * 
 * Cho phép lecturer chấm điểm bài nộp của sinh viên
 * Chỉ được chấm bài nộp của task mà lecturer đã tạo
 */
class GradeTaskSubmissionUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    /**
     * Execute use case - chấm điểm bài nộp
     * 
     * @param int $taskId - Task ID
     * @param int $submissionId - Submission ID
     * @param array $data - Data chứa grade, feedback, status
     * @param int $lecturerId - Lecturer ID (từ JWT)
     * @return array Returns graded submission data
     * @throws LecturerTaskException Nếu không có quyền hoặc lỗi
     */
    public function execute(int $taskId, int $submissionId, array $data, int $lecturerId): array
    {
        try {
            // Kiểm tra task có tồn tại và lecturer có quyền không
            $task = $this->lecturerTaskRepository->findById($taskId);
            
            if (!$task) {
                throw LecturerTaskException::taskNotFound($taskId);
            }

            // Kiểm tra lecturer có phải là creator của task không
            if ($task->creator_id != $lecturerId || $task->creator_type != 'lecturer') {
                throw LecturerTaskException::accessDenied($taskId);
            }

            // Grade submission
            $gradedSubmission = $this->lecturerTaskRepository->gradeTaskSubmission(
                $taskId,
                $submissionId,
                $data,
                $lecturerId
            );

            return $gradedSubmission;
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Grade submission error', [
                'task_id' => $taskId,
                'submission_id' => $submissionId,
                'lecturer_id' => $lecturerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new LecturerTaskException(
                'Failed to grade task submission: ' . $e->getMessage(),
                500
            );
        }
    }
}


<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\Repositories\StudentTaskRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Get Task Submission Use Case
 * 
 * Lấy submission với files và grade được format đúng
 */
class GetTaskSubmissionUseCase
{
    protected $studentTaskRepository;

    public function __construct(StudentTaskRepository $studentTaskRepository)
    {
        $this->studentTaskRepository = $studentTaskRepository;
    }

    /**
     * Execute use case - lấy submission với đầy đủ thông tin
     * 
     * @param int $taskId
     * @param int $studentId
     * @return array|null Returns null nếu không có submission (không throw exception)
     * @throws StudentTaskException Chỉ throw khi có lỗi hệ thống
     */
    public function execute($taskId, $studentId)
    {
        try {
            // Lấy submission với files và grade
            $submission = $this->studentTaskRepository->getTaskSubmissionWithFiles($taskId, $studentId);
            
            // Return null nếu không có submission (không throw exception)
            // Controller sẽ handle và return 404
            if (!$submission) {
                return null;
            }

            return $submission;
        } catch (StudentTaskException $e) {
            // Re-throw StudentTaskException để controller handle đúng status code
            throw $e;
        } catch (\Exception $e) {
            // Log error và throw exception với status 500
            \Log::error('Get submission error', [
                'task_id' => $taskId,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new StudentTaskException('Failed to retrieve task submission: ' . $e->getMessage(), 500);
        }
    }
}

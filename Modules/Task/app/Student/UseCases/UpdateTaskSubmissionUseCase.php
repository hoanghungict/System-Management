<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\Repositories\StudentTaskRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Update Task Submission Use Case
 */
class UpdateTaskSubmissionUseCase
{
    protected $studentTaskRepository;

    public function __construct(StudentTaskRepository $studentTaskRepository)
    {
        $this->studentTaskRepository = $studentTaskRepository;
    }

    public function execute($taskId, $data, $studentId)
    {
        try {
            $submission = $this->studentTaskRepository->getTaskSubmission($taskId, $studentId);
            if (!$submission) {
                throw StudentTaskException::submissionNotFound($taskId, $studentId);
            }

            $updatedSubmission = $this->studentTaskRepository->updateTaskSubmission($taskId, $data, $studentId);
            return $updatedSubmission;
        } catch (StudentTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to update task submission: ' . $e->getMessage(), 500);
        }
    }
}

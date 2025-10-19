<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\Repositories\StudentTaskRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Get Task Submission Use Case
 */
class GetTaskSubmissionUseCase
{
    protected $studentTaskRepository;

    public function __construct(StudentTaskRepository $studentTaskRepository)
    {
        $this->studentTaskRepository = $studentTaskRepository;
    }

    public function execute($taskId, $studentId)
    {
        try {
            $submission = $this->studentTaskRepository->getTaskSubmission($taskId, $studentId);
            if (!$submission) {
                throw StudentTaskException::submissionNotFound($taskId, $studentId);
            }

            return $submission;
        } catch (StudentTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve task submission: ' . $e->getMessage(), 500);
        }
    }
}

<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\Repositories\StudentTaskRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Delete Task File Use Case
 */
class DeleteTaskFileUseCase
{
    protected $studentTaskRepository;

    public function __construct(StudentTaskRepository $studentTaskRepository)
    {
        $this->studentTaskRepository = $studentTaskRepository;
    }

    public function execute($taskId, $fileId, $studentId)
    {
        try {
            $this->studentTaskRepository->deleteTaskFile($fileId, $studentId);
            return true;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to delete task file: ' . $e->getMessage(), 500);
        }
    }
}

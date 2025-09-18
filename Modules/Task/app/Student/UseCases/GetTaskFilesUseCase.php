<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\Repositories\StudentTaskRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Get Task Files Use Case
 */
class GetTaskFilesUseCase
{
    protected $studentTaskRepository;

    public function __construct(StudentTaskRepository $studentTaskRepository)
    {
        $this->studentTaskRepository = $studentTaskRepository;
    }

    public function execute($taskId, $studentId)
    {
        try {
            $files = $this->studentTaskRepository->getTaskFiles($taskId, $studentId);
            return $files;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve task files: ' . $e->getMessage(), 500);
        }
    }
}

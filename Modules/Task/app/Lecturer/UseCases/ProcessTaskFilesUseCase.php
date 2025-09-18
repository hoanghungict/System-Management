<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Process Task Files Use Case
 */
class ProcessTaskFilesUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    public function execute($taskId, $data, $lecturerId, $userType)
    {
        try {
            $result = $this->lecturerTaskRepository->processTaskFiles($taskId, $data, $lecturerId, $userType);
            return $result;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to process task files: ' . $e->getMessage(), 500);
        }
    }
}

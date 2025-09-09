<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Assign Task Use Case
 */
class AssignTaskUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    public function execute($taskId, $data, $lecturerId, $userType)
    {
        try {
            $result = $this->lecturerTaskRepository->assignTask($taskId, $data, $lecturerId, $userType);
            return $result;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to assign task: ' . $e->getMessage(), 500);
        }
    }
}

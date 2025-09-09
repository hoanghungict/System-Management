<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\DTOs\CreateTaskDTO;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Update Task Use Case
 */
class UpdateTaskUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    public function execute($taskId, $data, $lecturerId, $userType)
    {
        try {
            $task = $this->lecturerTaskRepository->update($taskId, $data, $lecturerId, $userType);
            return $task;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to update task: ' . $e->getMessage(), 500);
        }
    }
}

<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Create Recurring Task Use Case
 */
class CreateRecurringTaskUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    public function execute($data)
    {
        try {
            $task = $this->lecturerTaskRepository->createRecurringTask($data);
            return $task;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to create recurring task: ' . $e->getMessage(), 500);
        }
    }
}

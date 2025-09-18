<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Revoke Task Use Case
 */
class RevokeTaskUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    public function execute($taskId, $data, $lecturerId, $userType)
    {
        try {
            $result = $this->lecturerTaskRepository->revokeTask($taskId, $data, $lecturerId, $userType);
            return $result;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to revoke task: ' . $e->getMessage(), 500);
        }
    }
}

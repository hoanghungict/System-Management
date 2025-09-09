<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Send Report Email Use Case
 */
class SendReportEmailUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    public function execute($lecturerId, $data)
    {
        try {
            $result = $this->lecturerTaskRepository->sendReportEmail($lecturerId, $data);
            return $result;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to send report email: ' . $e->getMessage(), 500);
        }
    }
}

<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Generate Report Use Case
 */
class GenerateReportUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    public function execute($lecturerId, $data)
    {
        try {
            $report = $this->lecturerTaskRepository->generateReport($lecturerId, $data);
            return $report;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to generate report: ' . $e->getMessage(), 500);
        }
    }
}

<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\DTOs\StudentStatisticsDTO;
use Modules\Task\app\Student\Repositories\StudentTaskRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Get Task Statistics Use Case
 */
class GetTaskStatisticsUseCase
{
    protected $studentTaskRepository;

    public function __construct(StudentTaskRepository $studentTaskRepository)
    {
        $this->studentTaskRepository = $studentTaskRepository;
    }

    public function execute($studentId)
    {
        try {
            $statistics = $this->studentTaskRepository->getStudentStatistics($studentId);
            $statisticsDTO = new StudentStatisticsDTO($statistics);
            $statisticsDTO->calculateRates();
            
            return $statisticsDTO->toArray();
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student statistics: ' . $e->getMessage(), 500);
        }
    }
}

<?php

namespace Modules\Task\app\Student\DTOs;

/**
 * Student Statistics DTO
 */
class StudentStatisticsDTO
{
    public $total_tasks;
    public $pending_tasks;
    public $submitted_tasks;
    public $overdue_tasks;
    public $completed_tasks;
    public $average_score;
    public $total_submissions;
    public $on_time_submissions;
    public $late_submissions;
    public $completion_rate;
    public $overdue_rate;

    public function __construct(array $data = [])
    {
        $this->total_tasks = $data['total_tasks'] ?? 0;
        $this->pending_tasks = $data['pending_tasks'] ?? 0;
        $this->submitted_tasks = $data['submitted_tasks'] ?? 0;
        $this->overdue_tasks = $data['overdue_tasks'] ?? 0;
        $this->completed_tasks = $data['completed_tasks'] ?? 0;
        $this->average_score = $data['average_score'] ?? 0;
        $this->total_submissions = $data['total_submissions'] ?? 0;
        $this->on_time_submissions = $data['on_time_submissions'] ?? 0;
        $this->late_submissions = $data['late_submissions'] ?? 0;
        $this->completion_rate = $data['completion_rate'] ?? 0;
        $this->overdue_rate = $data['overdue_rate'] ?? 0;
    }

    public function toArray(): array
    {
        return [
            'total_tasks' => $this->total_tasks,
            'pending_tasks' => $this->pending_tasks,
            'submitted_tasks' => $this->submitted_tasks,
            'overdue_tasks' => $this->overdue_tasks,
            'completed_tasks' => $this->completed_tasks,
            'average_score' => $this->average_score,
            'total_submissions' => $this->total_submissions,
            'on_time_submissions' => $this->on_time_submissions,
            'late_submissions' => $this->late_submissions,
            'completion_rate' => $this->completion_rate,
            'overdue_rate' => $this->overdue_rate,
        ];
    }

    public function calculateRates(): void
    {
        if ($this->total_tasks > 0) {
            $this->completion_rate = round(($this->completed_tasks / $this->total_tasks) * 100, 2);
            $this->overdue_rate = round(($this->overdue_tasks / $this->total_tasks) * 100, 2);
        }
    }
}

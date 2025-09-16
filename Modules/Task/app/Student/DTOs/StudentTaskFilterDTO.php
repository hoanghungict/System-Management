<?php

namespace Modules\Task\app\Student\DTOs;

/**
 * Task Filter DTO for Student
 */
class StudentTaskFilterDTO
{
    public $status;
    public $priority;
    public $due_date_from;
    public $due_date_to;
    public $submitted;
    public $overdue;
    public $class_id;
    public $lecturer_id;
    public $search;
    public $sort_by;
    public $sort_order;
    public $per_page;
    public $page;

    public function __construct(array $data = [])
    {
        $this->status = $data['status'] ?? null;
        $this->priority = $data['priority'] ?? null;
        $this->due_date_from = $data['due_date_from'] ?? null;
        $this->due_date_to = $data['due_date_to'] ?? null;
        $this->submitted = $data['submitted'] ?? null;
        $this->overdue = $data['overdue'] ?? null;
        $this->class_id = $data['class_id'] ?? null;
        $this->lecturer_id = $data['lecturer_id'] ?? null;
        $this->search = $data['search'] ?? null;
        $this->sort_by = $data['sort_by'] ?? 'created_at';
        $this->sort_order = $data['sort_order'] ?? 'desc';
        $this->per_page = $data['per_page'] ?? 15;
        $this->page = $data['page'] ?? 1;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date_from' => $this->due_date_from,
            'due_date_to' => $this->due_date_to,
            'submitted' => $this->submitted,
            'overdue' => $this->overdue,
            'class_id' => $this->class_id,
            'lecturer_id' => $this->lecturer_id,
            'search' => $this->search,
            'sort_by' => $this->sort_by,
            'sort_order' => $this->sort_order,
            'per_page' => $this->per_page,
            'page' => $this->page,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->status && !in_array($this->status, ['pending', 'in_progress', 'completed', 'overdue'])) {
            $errors[] = 'Status must be one of: pending, in_progress, completed, overdue';
        }

        if ($this->priority && !in_array($this->priority, ['low', 'medium', 'high', 'urgent'])) {
            $errors[] = 'Priority must be one of: low, medium, high, urgent';
        }

        if ($this->sort_order && !in_array($this->sort_order, ['asc', 'desc'])) {
            $errors[] = 'Sort order must be asc or desc';
        }

        return $errors;
    }
}

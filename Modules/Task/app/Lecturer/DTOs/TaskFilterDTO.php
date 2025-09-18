<?php

namespace Modules\Task\app\Lecturer\DTOs;

/**
 * Task Filter DTO
 */
class TaskFilterDTO
{
    public $status;
    public $priority;
    public $department_id;
    public $class_id;
    public $lecturer_id;
    public $student_id;
    public $date_from;
    public $date_to;
    public $search;
    public $per_page;
    public $page;
    public $sort_by;
    public $sort_order;

    public function __construct(array $data = [])
    {
        $this->status = $data['status'] ?? null;
        $this->priority = $data['priority'] ?? null;
        $this->department_id = $data['department_id'] ?? null;
        $this->class_id = $data['class_id'] ?? null;
        $this->lecturer_id = $data['lecturer_id'] ?? null;
        $this->student_id = $data['student_id'] ?? null;
        $this->date_from = $data['date_from'] ?? null;
        $this->date_to = $data['date_to'] ?? null;
        $this->search = $data['search'] ?? null;
        $this->per_page = $data['per_page'] ?? 15;
        $this->page = $data['page'] ?? 1;
        $this->sort_by = $data['sort_by'] ?? 'created_at';
        $this->sort_order = $data['sort_order'] ?? 'desc';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'priority' => $this->priority,
            'department_id' => $this->department_id,
            'class_id' => $this->class_id,
            'lecturer_id' => $this->lecturer_id,
            'student_id' => $this->student_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'search' => $this->search,
            'per_page' => $this->per_page,
            'page' => $this->page,
            'sort_by' => $this->sort_by,
            'sort_order' => $this->sort_order,
        ];
    }
}

<?php

namespace Modules\Task\app\Lecturer\DTOs;

/**
 * Task Filter DTO
 * 
 * Data Transfer Object cho việc lọc tasks
 */
class TaskFilterDTO
{
    public $status;
    public $priority;
    public $department_id;
    public $class_id;
    public $creator_id;
    public $creator_type;
    public $receiver_id;
    public $receiver_type;
    public $search;
    public $sort_by;
    public $sort_order;
    public $per_page;
    public $page;

    public function __construct($data = [])
    {
        $this->status = $data['status'] ?? null;
        $this->priority = $data['priority'] ?? null;
        $this->department_id = $data['department_id'] ?? null;
        $this->class_id = $data['class_id'] ?? null;
        $this->creator_id = $data['creator_id'] ?? null;
        $this->creator_type = $data['creator_type'] ?? null;
        $this->receiver_id = $data['receiver_id'] ?? null;
        $this->receiver_type = $data['receiver_type'] ?? null;
        $this->search = $data['search'] ?? null;
        $this->sort_by = $data['sort_by'] ?? 'created_at';
        $this->sort_order = $data['sort_order'] ?? 'desc';
        $this->per_page = $data['per_page'] ?? 15;
        $this->page = $data['page'] ?? 1;
    }

    public function toArray()
    {
        return [
            'status' => $this->status,
            'priority' => $this->priority,
            'department_id' => $this->department_id,
            'class_id' => $this->class_id,
            'creator_id' => $this->creator_id,
            'creator_type' => $this->creator_type,
            'receiver_id' => $this->receiver_id,
            'receiver_type' => $this->receiver_type,
            'search' => $this->search,
            'sort_by' => $this->sort_by,
            'sort_order' => $this->sort_order,
            'per_page' => $this->per_page,
            'page' => $this->page,
        ];
    }
}

/**
 * Create Task DTO
 * 
 * Data Transfer Object cho việc tạo task
 */
class CreateTaskDTO
{
    public $title;
    public $description;
    public $priority;
    public $deadline;
    public $creator_id;
    public $creator_type;
    public $receivers;
    public $recurrence_pattern;
    public $recurrence_interval;
    public $permissions;

    public function __construct($data = [])
    {
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->priority = $data['priority'] ?? 'medium';
        $this->deadline = $data['deadline'] ?? null;
        $this->creator_id = $data['creator_id'] ?? null;
        $this->creator_type = $data['creator_type'] ?? 'lecturer';
        $this->receivers = $data['receivers'] ?? [];
        $this->recurrence_pattern = $data['recurrence_pattern'] ?? null;
        $this->recurrence_interval = $data['recurrence_interval'] ?? null;
        $this->permissions = $data['permissions'] ?? [];
    }

    public function toArray()
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'deadline' => $this->deadline,
            'creator_id' => $this->creator_id,
            'creator_type' => $this->creator_type,
            'receivers' => $this->receivers,
            'recurrence_pattern' => $this->recurrence_pattern,
            'recurrence_interval' => $this->recurrence_interval,
            'permissions' => $this->permissions,
        ];
    }
}

<?php

namespace Modules\Task\app\Lecturer\DTOs;

/**
 * Create Task DTO
 */
class CreateTaskDTO
{
    public $title;
    public $description;
    public $priority;
    public $deadline;
    public $due_date;
    public $creator_id;
    public $creator_type;
    public $receivers;
    public $files;
    public $permissions;
    public $recurring_config;

    public function __construct(array $data = [])
    {
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->priority = $data['priority'] ?? 'medium';
        $this->deadline = $data['deadline'] ?? null;
        $this->due_date = $data['due_date'] ?? null;
        $this->creator_id = $data['creator_id'] ?? null;
        $this->creator_type = $data['creator_type'] ?? 'lecturer';
        $this->receivers = $data['receivers'] ?? [];
        $this->files = $data['files'] ?? [];
        $this->permissions = $data['permissions'] ?? [];
        $this->recurring_config = $data['recurring_config'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'deadline' => $this->deadline,
            'due_date' => $this->due_date,
            'creator_id' => $this->creator_id,
            'creator_type' => $this->creator_type,
            'receivers' => $this->receivers,
            'files' => $this->files,
            'permissions' => $this->permissions,
            'recurring_config' => $this->recurring_config,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->title)) {
            $errors[] = 'Title is required';
        }

        if (empty($this->description)) {
            $errors[] = 'Description is required';
        }

        if (!in_array($this->priority, ['low', 'medium', 'high', 'urgent'])) {
            $errors[] = 'Priority must be one of: low, medium, high, urgent';
        }

        if (empty($this->creator_id)) {
            $errors[] = 'Creator ID is required';
        }

        if (!in_array($this->creator_type, ['lecturer', 'admin'])) {
            $errors[] = 'Creator type must be lecturer or admin';
        }

        return $errors;
    }
}

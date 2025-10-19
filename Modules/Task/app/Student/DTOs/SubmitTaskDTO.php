<?php

namespace Modules\Task\app\Student\DTOs;

/**
 * Submit Task DTO
 */
class SubmitTaskDTO
{
    public $task_id;
    public $student_id;
    public $submission_content;
    public $submission_files;
    public $submission_notes;
    public $submitted_at;

    public function __construct(array $data = [])
    {
        $this->task_id = $data['task_id'] ?? null;
        $this->student_id = $data['student_id'] ?? null;
        $this->submission_content = $data['submission_content'] ?? null;
        $this->submission_files = $data['submission_files'] ?? [];
        $this->submission_notes = $data['submission_notes'] ?? null;
        $this->submitted_at = $data['submitted_at'] ?? now();
    }

    public function toArray(): array
    {
        return [
            'task_id' => $this->task_id,
            'student_id' => $this->student_id,
            'submission_content' => $this->submission_content,
            'submission_files' => $this->submission_files,
            'submission_notes' => $this->submission_notes,
            'submitted_at' => $this->submitted_at,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->task_id)) {
            $errors[] = 'Task ID is required';
        }

        if (empty($this->student_id)) {
            $errors[] = 'Student ID is required';
        }

        if (empty($this->submission_content)) {
            $errors[] = 'Submission content is required';
        }

        return $errors;
    }
}

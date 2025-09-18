<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'task_submissions';

    protected $fillable = [
        'task_id',
        'student_id',
        'submission_content',
        'submission_files',
        'submitted_at',
        'status',
        'grade',
        'feedback',
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'submission_files' => 'array',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'grade' => 'decimal:2',
    ];

    /**
     * Relationship với Task
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Relationship với Student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Relationship với Lecturer (người chấm điểm)
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'graded_by');
    }

    /**
     * Scope: Lấy submissions theo status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Lấy submissions của student cụ thể
     */
    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope: Lấy submissions của task cụ thể
     */
    public function scopeByTask($query, int $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * Scope: Lấy submissions đã được chấm điểm
     */
    public function scopeGraded($query)
    {
        return $query->whereNotNull('graded_at');
    }

    /**
     * Scope: Lấy submissions chưa được chấm điểm
     */
    public function scopeUngraded($query)
    {
        return $query->whereNull('graded_at');
    }

    /**
     * Scope: Lấy submissions quá hạn
     */
    public function scopeOverdue($query)
    {
        return $query->whereHas('task', function ($q) {
            $q->where('deadline', '<', now())
              ->whereNull('submitted_at');
        });
    }

    /**
     * Accessor: Kiểm tra submission có quá hạn không
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->task || !$this->task->deadline) {
            return false;
        }

        return $this->submitted_at && $this->submitted_at->gt($this->task->deadline);
    }

    /**
     * Accessor: Lấy trạng thái submission
     */
    public function getSubmissionStatusAttribute(): string
    {
        if ($this->is_overdue) {
            return 'overdue';
        }

        if ($this->graded_at) {
            return 'graded';
        }

        if ($this->submitted_at) {
            return 'submitted';
        }

        return 'pending';
    }
}

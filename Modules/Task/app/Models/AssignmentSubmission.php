<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\AuditLog;
use Modules\Task\app\Traits\Gradable;

/**
 * AssignmentSubmission Model - Bài làm của sinh viên
 */
class AssignmentSubmission extends Model
{
    use SoftDeletes, Gradable;

    protected $table = 'assignment_submissions';

    protected $fillable = [
        'assignment_id',
        'student_id',
        'attempt',
        'started_at',
        'submitted_at',
        'auto_score',
        'manual_score',
        'total_score',
        'status',
        'graded_by',
        'graded_at',
        'feedback',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'auto_score' => 'decimal:2',
        'manual_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'attempt' => 'integer',
    ];

    protected $appends = [
        'remaining_time',
        'final_score',
    ];

    // ========== Relationships ==========

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'graded_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'submission_id');
    }

    /**
     * Câu hỏi đã được random cho submission này (nếu dùng question pool)
     */
    public function submissionQuestions(): HasMany
    {
        return $this->hasMany(SubmissionQuestion::class, 'submission_id')->orderBy('order_index');
    }

    /**
     * Lấy danh sách câu hỏi đã random (thông qua submissionQuestions)
     */
    public function getRandomizedQuestionsAttribute()
    {
        return $this->submissionQuestions->map(function ($sq) {
            return $sq->question;
        });
    }

    // ========== Scopes ==========

    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByAssignment($query, int $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    // ========== Boot for AuditLog ==========

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            AuditLog::log(
                'submission_started',
                $model->student_id,
                'AssignmentSubmission',
                $model->id,
                [
                    'assignment_id' => $model->assignment_id,
                    'attempt' => $model->attempt
                ]
            );
        });
    }

    // ========== Methods ==========

    /**
     * Submit assignment
     */
    public function submit(): bool
    {
        $this->submitted_at = now();
        $this->status = 'submitted';
        $saved = $this->save();

        if ($saved) {
            AuditLog::log(
                'submission_submitted',
                $this->student_id,
                'AssignmentSubmission',
                $this->id,
                [
                    'assignment_id' => $this->assignment_id,
                    'attempt' => $this->attempt
                ]
            );
        }

        return $saved;
    }

    /**
     * Calculate auto score from answers
     */
    public function calculateAutoScore(): float
    {
        $totalScore = 0;

        foreach ($this->answers as $answer) {
            if ($answer->question->is_auto_gradable && $answer->is_correct) {
                $totalScore += $answer->question->points;
            }
        }

        return $totalScore;
    }

    /**
     * Grade submission
     */
    public function grade(int $graderId, ?float $manualScore = null, ?string $feedback = null): bool
    {
        // Calculate auto score first if not already done
        if ($this->auto_score === null) {
            $this->auto_score = $this->calculateAutoScore();
        }
        
        // Use unified grading logic
        return $this->performManualGrading($graderId, $manualScore ?? 0, $feedback);
    }

    /**
     * Check if time limit exceeded
     */
    public function isTimeLimitExceeded(): bool
    {
        if (!$this->assignment->time_limit || !$this->started_at) {
            return false;
        }

        $endTime = $this->started_at->addMinutes($this->assignment->time_limit);
        return now()->gt($endTime);
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingTimeAttribute(): ?int
    {
        if (!$this->assignment->time_limit || !$this->started_at) {
            return null;
        }

        $endTime = $this->started_at->addMinutes($this->assignment->time_limit);
        $remaining = now()->diffInSeconds($endTime, false);
        
        return max(0, $remaining);
    }
}

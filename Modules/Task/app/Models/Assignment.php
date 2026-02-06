<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\AuditLog;

/**
 * Assignment Model - Bài tập/Đề bài
 */
class Assignment extends Model
{
    use SoftDeletes;

    protected $table = 'assignments';

    protected $fillable = [
        'course_id',
        'lecturer_id',
        'title',
        'description',
        'type',
        'deadline',
        'time_limit',
        'max_attempts',
        'show_answers',
        'shuffle_questions',
        'shuffle_options',
        'question_pool_enabled',  // Bật chế độ random đề thi
        'question_pool_config',   // Cấu hình số câu theo độ khó
        'question_pool_config',   // Cấu hình số câu theo độ khó
        'status',
        'slug',
        'grade_column', // Added for Gradebook
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'time_limit' => 'integer',
        'max_attempts' => 'integer',
        'show_answers' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'question_pool_enabled' => 'boolean',
        'question_pool_config' => 'array',
    ];

    // ========== Relationships ==========

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\app\Models\Attendance\Course::class, 'course_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'assignment_id')->orderBy('order_index');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(QuestionImportLog::class, 'assignment_id');
    }

    public function extensionRequests(): HasMany
    {
        return $this->hasMany(ExtensionRequest::class, 'assignment_id');
    }

    // ========== Scopes ==========

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByLecturer($query, int $lecturerId)
    {
        return $query->where('lecturer_id', $lecturerId);
    }

    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // ========== Accessors ==========

    public function getTotalPointsAttribute(): float
    {
        return $this->questions()->sum('points');
    }

    public function getQuestionCountAttribute(): int
    {
        return $this->questions()->count();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }

    // ========== AuditLog Methods ==========

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            AuditLog::log(
                'assignment_created',
                $model->lecturer_id,
                'Assignment',
                $model->id,
                ['title' => $model->title, 'type' => $model->type]
            );
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            
            if (!empty($changes)) {
                AuditLog::log(
                    'assignment_updated',
                    $model->lecturer_id,
                    'Assignment',
                    $model->id,
                    ['changes' => $changes]
                );
            }
        });

        static::deleted(function ($model) {
            AuditLog::log(
                'assignment_deleted',
                $model->lecturer_id,
                'Assignment',
                $model->id,
                ['title' => $model->title]
            );
        });
    }

    /**
     * Publish assignment
     */
    public function publish(): bool
    {
        $this->status = 'published';
        $saved = $this->save();

        if ($saved) {
            AuditLog::log(
                'assignment_published',
                $this->lecturer_id,
                'Assignment',
                $this->id,
                ['title' => $this->title]
            );
        }

        return $saved;
    }

    /**
     * Close assignment
     */
    public function close(): bool
    {
        $this->status = 'closed';
        return $this->save();
    }
}

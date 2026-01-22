<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\AuditLog;

/**
 * ExtensionRequest Model - Yêu cầu gia hạn deadline
 */
class ExtensionRequest extends Model
{
    protected $table = 'extension_requests';

    protected $fillable = [
        'assignment_id',
        'student_id',
        'reason',
        'new_deadline',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_note',
    ];

    protected $casts = [
        'new_deadline' => 'datetime',
        'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'reviewed_by');
    }

    // ========== Scopes ==========

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // ========== Boot for AuditLog ==========

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            AuditLog::log(
                'extension_requested',
                $model->student_id,
                'ExtensionRequest',
                $model->id,
                [
                    'assignment_id' => $model->assignment_id,
                    'new_deadline' => $model->new_deadline->toDateTimeString(),
                    'reason' => $model->reason
                ]
            );
        });
    }

    // ========== Methods ==========

    /**
     * Approve extension request
     */
    public function approve(int $reviewerId, ?string $note = null): bool
    {
        $this->status = 'approved';
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->reviewer_note = $note;
        $saved = $this->save();

        if ($saved) {
            AuditLog::log(
                'extension_approved',
                $reviewerId,
                'ExtensionRequest',
                $this->id,
                [
                    'assignment_id' => $this->assignment_id,
                    'student_id' => $this->student_id,
                    'new_deadline' => $this->new_deadline->toDateTimeString()
                ]
            );
        }

        return $saved;
    }

    /**
     * Reject extension request
     */
    public function reject(int $reviewerId, ?string $note = null): bool
    {
        $this->status = 'rejected';
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->reviewer_note = $note;
        $saved = $this->save();

        if ($saved) {
            AuditLog::log(
                'extension_rejected',
                $reviewerId,
                'ExtensionRequest',
                $this->id,
                [
                    'assignment_id' => $this->assignment_id,
                    'student_id' => $this->student_id,
                    'reason' => $note
                ]
            );
        }

        return $saved;
    }
}

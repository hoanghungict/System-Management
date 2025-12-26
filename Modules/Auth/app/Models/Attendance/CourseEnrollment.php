<?php

namespace Modules\Auth\app\Models\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\Student;

/**
 * Model CourseEnrollment - Đăng ký môn học của sinh viên
 */
class CourseEnrollment extends Model
{
    use HasFactory;

    protected $table = 'course_enrollments';

    protected $fillable = [
        'course_id',
        'student_id',
        'enrolled_at',
        'status',
        'note',
        'dropped_at',
        'drop_reason',
    ];

    protected $casts = [
        'enrolled_at' => 'date',
        'dropped_at' => 'date',
    ];

    // ==================== CONSTANTS ====================

    const STATUS_ACTIVE = 'active';
    const STATUS_DROPPED = 'dropped';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // ==================== RELATIONSHIPS ====================

    /**
     * Thuộc môn học nào
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Thuộc sinh viên nào
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Lấy đăng ký đang active
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Lấy theo môn học
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope: Lấy theo sinh viên
     */
    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // ==================== METHODS ====================

    /**
     * Hủy đăng ký môn học
     */
    public function drop(string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_DROPPED,
            'dropped_at' => now(),
            'drop_reason' => $reason,
        ]);
    }

    /**
     * Kiểm tra đang hoạt động
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Kiểm tra đăng ký muộn (sau khi môn học bắt đầu)
     */
    public function isLateEnrollment(): bool
    {
        return $this->enrolled_at > $this->course->start_date;
    }
}

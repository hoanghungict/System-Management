<?php

namespace Modules\Auth\app\Models\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\Department;
use Modules\Auth\app\Models\Student;

/**
 * Model Course - Môn học/Lớp học phần
 * 
 * Đây là đơn vị chính để điểm danh theo môn học
 */
class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'courses';

    protected $fillable = [
        'code',
        'name',
        'credits',
        'description',
        'semester_id',
        'lecturer_id',
        'department_id',
        'schedule_days',
        'start_time',
        'end_time',
        'room',
        'total_sessions',
        'max_absences',
        'absence_warning',
        'late_threshold_minutes',
        'start_date',
        'end_date',
        'status',
        'sessions_generated',
    ];

    protected $casts = [
        'schedule_days' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'sessions_generated' => 'boolean',
        'credits' => 'integer',
        'total_sessions' => 'integer',
        'max_absences' => 'integer',
        'absence_warning' => 'integer',
        'late_threshold_minutes' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Môn học thuộc học kỳ nào
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    /**
     * Giảng viên phụ trách môn học
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    /**
     * Khoa quản lý môn học
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Danh sách đăng ký môn học
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class, 'course_id');
    }

    /**
     * Danh sách sinh viên trong môn (qua enrollments)
     */
    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'course_enrollments',
            'course_id',
            'student_id'
        )->withPivot(['enrolled_at', 'status', 'note'])
         ->withTimestamps();
    }

    /**
     * Danh sách các buổi học
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'course_id');
    }

    /**
     * Chỉ lấy sinh viên đang active
     */
    public function activeStudents()
    {
        return $this->students()->wherePivot('status', 'active');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Lấy theo học kỳ
     */
    public function scopeBySemester($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    /**
     * Scope: Lấy theo giảng viên
     */
    public function scopeByLecturer($query, int $lecturerId)
    {
        return $query->where('lecturer_id', $lecturerId);
    }

    /**
     * Scope: Lấy môn đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Lấy theo trạng thái
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ==================== METHODS ====================

    /**
     * Lấy tên ngày học (VD: "Thứ 5, Thứ 7")
     */
    public function getScheduleDaysTextAttribute(): string
    {
        if (empty($this->schedule_days)) {
            return 'Chưa cấu hình';
        }

        $dayNames = [
            2 => 'Thứ 2',
            3 => 'Thứ 3',
            4 => 'Thứ 4',
            5 => 'Thứ 5',
            6 => 'Thứ 6',
            7 => 'Thứ 7',
            8 => 'Chủ nhật',
        ];

        $days = array_map(fn($d) => $dayNames[$d] ?? "Ngày $d", $this->schedule_days);
        return implode(', ', $days);
    }

    /**
     * Lấy thời gian học (VD: "07:30 - 09:30")
     */
    public function getScheduleTimeTextAttribute(): string
    {
        if (!$this->start_time || !$this->end_time) {
            return 'Chưa cấu hình';
        }
        return substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5);
    }

    /**
     * Đếm số sinh viên đang học
     */
    public function getActiveStudentCountAttribute(): int
    {
        return $this->enrollments()->where('status', 'active')->count();
    }

    /**
     * Đếm số buổi đã điểm danh
     */
    public function getCompletedSessionsCountAttribute(): int
    {
        return $this->sessions()->where('status', 'completed')->count();
    }

    /**
     * Kiểm tra đã tạo lịch học chưa
     */
    public function hasGeneratedSessions(): bool
    {
        return $this->sessions_generated && $this->sessions()->exists();
    }
}

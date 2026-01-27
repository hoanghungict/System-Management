<?php

namespace Modules\Auth\app\Models\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\app\Models\Lecturer;

/**
 * Model AttendanceSession - Buổi học
 * 
 * Mỗi buổi học được tự động tạo dựa trên thời khóa biểu của môn học
 */
class AttendanceSession extends Model
{
    use HasFactory;

    protected $table = 'attendance_sessions';

    protected $fillable = [
        'course_id',
        'session_number',
        'session_date',
        'day_of_week',
        'start_time',
        'end_time',
        'shift',
        'topic',
        'room',
        'notes',
        'status',
        'started_at',
        'completed_at',
        'marked_by',
    ];

    protected $casts = [
        'session_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'session_number' => 'integer',
        'day_of_week' => 'integer',
    ];

    // ==================== CONSTANTS ====================

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_HOLIDAY = 'holiday';

    // ==================== SHIFTS ====================

    const SHIFT_MORNING = 'morning';
    const SHIFT_AFTERNOON = 'afternoon';
    const SHIFT_EVENING = 'evening';

    /**
     * Lấy nhãn ca học
     */
    public static function getShiftLabels(): array
    {
        return [
            self::SHIFT_MORNING => 'Sáng',
            self::SHIFT_AFTERNOON => 'Chiều',
            self::SHIFT_EVENING => 'Tối',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Thuộc môn học nào
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Người điểm danh
     */
    public function markedByLecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'marked_by');
    }

    /**
     * Danh sách điểm danh của sinh viên trong buổi này
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'session_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Lấy theo môn học
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope: Lấy theo trạng thái
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Lấy buổi đã lên lịch (chưa điểm danh)
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope: Lấy buổi đã hoàn thành
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Lấy theo ngày
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('session_date', $date);
    }

    /**
     * Scope: Lấy buổi học trong khoảng thời gian
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('session_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Sắp xếp theo số buổi
     */
    public function scopeOrderBySession($query, string $direction = 'asc')
    {
        return $query->orderBy('session_number', $direction);
    }

    // ==================== METHODS ====================

    /**
     * Bắt đầu điểm danh
     */
    public function start(int $lecturerId): bool
    {
        if ($this->status !== self::STATUS_SCHEDULED) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'marked_by' => $lecturerId,
        ]);
    }

    /**
     * Hoàn thành điểm danh
     */
    public function complete(): bool
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Hủy buổi học
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Đánh dấu nghỉ lễ
     */
    public function markAsHoliday(): bool
    {
        return $this->update([
            'status' => self::STATUS_HOLIDAY,
        ]);
    }

    /**
     * Kiểm tra có thể sửa bởi GV không
     * GV chỉ được sửa khi CHƯA completed
     */
    public function canEditByLecturer(): bool
    {
        return $this->status !== self::STATUS_COMPLETED;
    }

    /**
     * Kiểm tra đã hoàn thành chưa
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Lấy tên thứ trong tuần
     */
    public function getDayOfWeekTextAttribute(): string
    {
        $dayNames = [
            2 => 'Thứ 2',
            3 => 'Thứ 3',
            4 => 'Thứ 4',
            5 => 'Thứ 5',
            6 => 'Thứ 6',
            7 => 'Thứ 7',
            8 => 'Chủ nhật',
        ];
        return $dayNames[$this->day_of_week] ?? "Ngày {$this->day_of_week}";
    }

    /**
     * Lấy tên ca học hiển thị
     */
    public function getShiftLabelAttribute(): string
    {
        return self::getShiftLabels()[$this->shift] ?? $this->shift;
    }

    /**
     * Lấy thống kê điểm danh của buổi
     */
    public function getAttendanceStats(): array
    {
        $attendances = $this->attendances;
        
        return [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', Attendance::STATUS_PRESENT)->count(),
            'absent' => $attendances->where('status', Attendance::STATUS_ABSENT)->count(),
            'late' => $attendances->where('status', Attendance::STATUS_LATE)->count(),
            'excused' => $attendances->where('status', Attendance::STATUS_EXCUSED)->count(),
            'not_marked' => $attendances->where('status', Attendance::STATUS_NOT_MARKED)->count(),
        ];
    }
}

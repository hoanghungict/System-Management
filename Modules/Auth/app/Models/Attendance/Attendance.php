<?php

namespace Modules\Auth\app\Models\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\Student;

/**
 * Model Attendance - Chi tiết điểm danh từng sinh viên
 * 
 * Mỗi record = 1 sinh viên trong 1 buổi học
 */
class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'session_id',
        'student_id',
        'status',
        'check_in_time',
        'minutes_late',
        'note',
        'excuse_reason',
        'excuse_document',
        'marked_by',
        'marked_at',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
        'minutes_late' => 'integer',
    ];

    // ==================== CONSTANTS ====================

    const STATUS_PRESENT = 'present';       // Có mặt
    const STATUS_ABSENT = 'absent';         // Vắng không phép
    const STATUS_LATE = 'late';             // Đến muộn
    const STATUS_EXCUSED = 'excused';       // Vắng có phép / Pass
    const STATUS_NOT_MARKED = 'not_marked'; // Chưa điểm danh

    /**
     * Danh sách trạng thái và nhãn
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PRESENT => 'Có mặt',
            self::STATUS_ABSENT => 'Vắng',
            self::STATUS_LATE => 'Đi muộn',
            self::STATUS_EXCUSED => 'Có phép',
            self::STATUS_NOT_MARKED => 'Chưa điểm danh',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Thuộc buổi học nào
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    /**
     * Thuộc sinh viên nào
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Ai điểm danh
     */
    public function markedByLecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'marked_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Lấy theo buổi học
     */
    public function scopeBySession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope: Lấy theo sinh viên
     */
    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope: Lấy theo trạng thái
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Lấy những bản ghi vắng (không có mặt)
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    /**
     * Scope: Lấy những bản ghi có mặt (present + late)
     */
    public function scopeAttended($query)
    {
        return $query->whereIn('status', [self::STATUS_PRESENT, self::STATUS_LATE]);
    }

    /**
     * Scope: Lấy những bản ghi chưa điểm danh
     */
    public function scopeNotMarked($query)
    {
        return $query->where('status', self::STATUS_NOT_MARKED);
    }

    // ==================== METHODS ====================

    /**
     * Đánh dấu có mặt
     */
    public function markPresent(int $lecturerId, ?string $checkInTime = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PRESENT,
            'check_in_time' => $checkInTime,
            'minutes_late' => 0,
            'marked_by' => $lecturerId,
            'marked_at' => now(),
        ]);
    }

    /**
     * Đánh dấu vắng
     */
    public function markAbsent(int $lecturerId, ?string $note = null): bool
    {
        return $this->update([
            'status' => self::STATUS_ABSENT,
            'note' => $note,
            'marked_by' => $lecturerId,
            'marked_at' => now(),
        ]);
    }

    /**
     * Đánh dấu đến muộn
     */
    public function markLate(int $lecturerId, int $minutesLate = 0, ?string $checkInTime = null): bool
    {
        return $this->update([
            'status' => self::STATUS_LATE,
            'check_in_time' => $checkInTime,
            'minutes_late' => $minutesLate,
            'marked_by' => $lecturerId,
            'marked_at' => now(),
        ]);
    }

    /**
     * Đánh dấu có phép
     */
    public function markExcused(int $lecturerId, ?string $reason = null, ?string $document = null): bool
    {
        return $this->update([
            'status' => self::STATUS_EXCUSED,
            'excuse_reason' => $reason,
            'excuse_document' => $document,
            'marked_by' => $lecturerId,
            'marked_at' => now(),
        ]);
    }

    /**
     * Lấy nhãn trạng thái
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    /**
     * Kiểm tra có được tính là có mặt không (present hoặc late)
     */
    public function isAttended(): bool
    {
        return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE]);
    }

    /**
     * Kiểm tra có được tính là vắng không (chỉ absent, không tính excused)
     */
    public function isAbsent(): bool
    {
        return $this->status === self::STATUS_ABSENT;
    }
}

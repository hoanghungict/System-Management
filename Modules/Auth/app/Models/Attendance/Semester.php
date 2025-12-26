<?php

namespace Modules\Auth\app\Models\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Semester - Học kỳ
 * 
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $academic_year
 * @property string $semester_type
 * @property string $start_date
 * @property string $end_date
 * @property bool $is_active
 * @property string|null $description
 */
class Semester extends Model
{
    use HasFactory;

    protected $table = 'semesters';

    protected $fillable = [
        'name',
        'code',
        'academic_year',
        'semester_type',
        'start_date',
        'end_date',
        'is_active',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Học kỳ có nhiều môn học
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'semester_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Lấy học kỳ đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Lấy theo năm học
     */
    public function scopeByAcademicYear($query, string $year)
    {
        return $query->where('academic_year', $year);
    }

    /**
     * Scope: Lấy học kỳ hiện tại (theo ngày)
     */
    public function scopeCurrent($query)
    {
        $today = now()->toDateString();
        return $query->where('start_date', '<=', $today)
                     ->where('end_date', '>=', $today);
    }

    // ==================== METHODS ====================

    /**
     * Kích hoạt học kỳ này và vô hiệu các học kỳ khác
     */
    public function activate(): bool
    {
        // Vô hiệu tất cả học kỳ khác
        self::where('id', '!=', $this->id)->update(['is_active' => false]);
        
        // Kích hoạt học kỳ này
        return $this->update(['is_active' => true]);
    }

    /**
     * Kiểm tra học kỳ có đang diễn ra không
     */
    public function isOngoing(): bool
    {
        $today = now()->toDateString();
        return $this->start_date <= $today && $this->end_date >= $today;
    }

    /**
     * Lấy tên đầy đủ học kỳ
     */
    public function getFullNameAttribute(): string
    {
        $type = match ($this->semester_type) {
            '1' => 'Học kỳ 1',
            '2' => 'Học kỳ 2',
            '3' => 'Học kỳ 3',
            default => 'Học kỳ ' . $this->semester_type,
        };
        return "{$type} - {$this->academic_year}";
    }
}

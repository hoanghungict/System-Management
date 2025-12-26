<?php

namespace Modules\Auth\app\Models\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Holiday - Ngày nghỉ lễ
 */
class Holiday extends Model
{
    use HasFactory;

    protected $table = 'holidays';

    protected $fillable = [
        'name',
        'date',
        'is_recurring',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    // ==================== SCOPES ====================

    /**
     * Scope: Lấy ngày lễ trong khoảng thời gian
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: Lấy ngày lễ lặp lại hàng năm
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    // ==================== METHODS ====================

    /**
     * Kiểm tra ngày có phải ngày lễ không
     */
    public static function isHoliday($date): bool
    {
        $date = is_string($date) ? $date : $date->toDateString();
        return self::whereDate('date', $date)->exists();
    }

    /**
     * Lấy danh sách ngày lễ trong năm
     */
    public static function getHolidaysInYear(int $year): array
    {
        return self::whereYear('date', $year)
            ->orWhere('is_recurring', true)
            ->pluck('date')
            ->map(fn($d) => $d->toDateString())
            ->toArray();
    }
}

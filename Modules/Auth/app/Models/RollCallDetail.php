<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RollCallDetail extends Model
{
    use HasFactory;

    protected $table = 'roll_call_details';

    protected $fillable = [
        'roll_call_id',
        'student_id',
        'status',
        'note',
        'checked_at'
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    /**
     * Relationship với điểm danh
     */
    public function rollCall(): BelongsTo
    {
        return $this->belongsTo(RollCall::class, 'roll_call_id');
    }

    /**
     * Relationship với sinh viên
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Scope: Lấy chi tiết theo trạng thái
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Lấy chi tiết đã điểm danh
     */
    public function scopeChecked($query)
    {
        return $query->whereNotNull('checked_at');
    }

    /**
     * Scope: Lấy chi tiết chưa điểm danh
     */
    public function scopeUnchecked($query)
    {
        return $query->whereNull('checked_at');
    }
}

<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RollCall extends Model
{
    use HasFactory;

    protected $table = 'roll_calls';

    protected $fillable = [
        'class_id',
        'title',
        'description',
        'date',
        'status',
        'created_by'
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * Relationship với lớp học
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Relationship với người tạo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'created_by');
    }

    /**
     * Relationship với chi tiết điểm danh
     */
    public function rollCallDetails()
    {
        return $this->hasMany(RollCallDetail::class, 'roll_call_id');
    }

    /**
     * Scope: Lấy điểm danh theo lớp
     */
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope: Lấy điểm danh theo ngày
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope: Lấy điểm danh đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

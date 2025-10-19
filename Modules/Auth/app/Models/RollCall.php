<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'created_by',
        'type',
        'expected_participants',
        'metadata'
    ];

    protected $casts = [
        'date' => 'datetime',
        'metadata' => 'array',
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
    public function rollCallDetails(): HasMany
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
     * Scope: Lấy điểm danh theo trạng thái
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Lấy điểm danh đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Lấy điểm danh theo type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Lấy điểm danh theo lớp (class-based)
     */
    public function scopeClassBased($query)
    {
        return $query->where('type', 'class_based');
    }

    /**
     * Scope: Lấy điểm danh manual
     */
    public function scopeManual($query)
    {
        return $query->where('type', 'manual');
    }

    /**
     * Check if roll call is class-based
     */
    public function isClassBased(): bool
    {
        return $this->type === 'class_based';
    }

    /**
     * Check if roll call is manual
     */
    public function isManual(): bool
    {
        return $this->type === 'manual';
    }
}

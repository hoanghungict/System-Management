<?php

declare(strict_types=1);

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'data',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'target_id' => 'integer',
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action
     * Note: user_id có thể là lecturer hoặc student, cần xử lý riêng
     */
    public function user(): ?BelongsTo
    {
        // Vì không có bảng users chung, nên không thể tạo relationship trực tiếp
        // Có thể tạo accessor để lấy user từ lecturer hoặc student
        return null;
    }

    /**
     * Get the target model (polymorphic relationship)
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get lecturer if user_id points to lecturer
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'user_id');
    }

    /**
     * Get student if user_id points to student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'user_id');
    }

    /**
     * Scope: Filter by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filter by target type
     */
    public function scopeByTargetType($query, string $targetType)
    {
        return $query->where('target_type', $targetType);
    }

    /**
     * Scope: Filter by target
     */
    public function scopeByTarget($query, string $targetType, int $targetId)
    {
        return $query->where('target_type', $targetType)
                     ->where('target_id', $targetId);
    }

    /**
     * Scope: Filter by user_id
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Get recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get the user model (lecturer or student) based on context
     * This is a helper method since we don't have a unified users table
     */
    public function getUserModel()
    {
        if (!$this->user_id) {
            return null;
        }

        // Try lecturer first
        $lecturer = Lecturer::find($this->user_id);
        if ($lecturer) {
            return $lecturer;
        }

        // Try student
        $student = Student::find($this->user_id);
        if ($student) {
            return $student;
        }

        return null;
    }

    /**
     * Static method to create audit log
     */
    public static function log(
        string $action,
        ?int $userId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $data = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'data' => $data,
        ]);
    }
}


<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskDependency extends Model
{
    use HasFactory;

    protected $table = 'task_dependencies';

    protected $fillable = [
        'predecessor_task_id',
        'successor_task_id',
        'dependency_type',
        'lag_days',
        'metadata',
        'created_by',
        'created_by_type'
    ];

    protected $casts = [
        'metadata' => 'array',
        'lag_days' => 'integer',
        'predecessor_task_id' => 'integer',
        'successor_task_id' => 'integer',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Dependency types
     */
    const DEPENDENCY_TYPES = [
        'finish_to_start' => 'Finish to Start',
        'start_to_start' => 'Start to Start',
        'finish_to_finish' => 'Finish to Finish',
        'start_to_finish' => 'Start to Finish'
    ];

    /**
     * Relationship với task tiền nhiệm (task mà dependency này phụ thuộc vào)
     */
    public function predecessorTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'predecessor_task_id');
    }

    /**
     * Relationship với task kế nhiệm (task hiện tại)
     */
    public function successorTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'successor_task_id');
    }

    /**
     * Scope: Lấy dependencies của một task
     */
    public function scopeForTask($query, int $taskId)
    {
        return $query->where('successor_task_id', $taskId);
    }

    /**
     * Scope: Lấy dependencies mà task này là predecessor
     */
    public function scopeAsPredecessor($query, int $taskId)
    {
        return $query->where('predecessor_task_id', $taskId);
    }

    /**
     * Scope: Lấy dependencies theo type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('dependency_type', $type);
    }

    /**
     * Kiểm tra xem dependency có hợp lệ không
     */
    public function isValid(): bool
    {
        // Không thể phụ thuộc vào chính mình
        if ($this->predecessor_task_id === $this->successor_task_id) {
            return false;
        }

        // Kiểm tra dependency type hợp lệ
        if (!array_key_exists($this->dependency_type, self::DEPENDENCY_TYPES)) {
            return false;
        }

        return true;
    }

    /**
     * Lấy dependency type text
     */
    public function getDependencyTypeTextAttribute(): string
    {
        return self::DEPENDENCY_TYPES[$this->dependency_type] ?? $this->dependency_type;
    }

    /**
     * Kiểm tra xem task có thể bắt đầu không (tất cả dependencies đã hoàn thành)
     */
    public function canTaskStart(): bool
    {
        if (!$this->predecessorTask) {
            return true;
        }

        return $this->predecessorTask->status === 'completed';
    }

    /**
     * Lấy thông tin dependency status
     */
    public function getDependencyStatus(): array
    {
        if (!$this->predecessorTask) {
            return [
                'status' => 'unknown',
                'can_start' => false,
                'message' => 'Predecessor task not found'
            ];
        }

        $predecessorStatus = $this->predecessorTask->status;
        $canStart = $predecessorStatus === 'completed';

        return [
            'status' => $predecessorStatus,
            'can_start' => $canStart,
            'message' => $canStart ? 'Ready to start' : "Waiting for predecessor task to complete"
        ];
    }
}
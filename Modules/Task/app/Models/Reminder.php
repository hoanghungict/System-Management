<?php

declare(strict_types=1);

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Reminder Model
 * 
 * Represents a reminder for tasks or calendar events
 * 
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $user_type
 * @property string $reminder_type
 * @property \DateTime $reminder_time
 * @property string $message
 * @property string $status
 * @property \DateTime $sent_at
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime|null $deleted_at
 * 
 * @property-read Task $task
 * @property-read User $user
 */
class Reminder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reminders';

    protected $fillable = [
        'task_id',
        'user_id',
        'user_type',
        'reminder_type',
        'reminder_time',
        'message',
        'status',
        'sent_at',
        'metadata'
    ];

    protected $casts = [
        'reminder_time' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Reminder types
     */
    const REMINDER_TYPES = [
        'email' => 'Email',
        'push' => 'Push Notification',
        'sms' => 'SMS',
        'in_app' => 'In-App Notification'
    ];

    /**
     * Reminder statuses
     */
    const STATUSES = [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled'
    ];

    /**
     * Relationship with Task
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\app\Models\User::class);
    }

    /**
     * Scope: Pending reminders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where('reminder_time', '<=', now());
    }

    /**
     * Scope: By user
     */
    public function scopeByUser($query, int $userId, string $userType)
    {
        return $query->where('user_id', $userId)
                    ->where('user_type', $userType);
    }

    /**
     * Scope: By reminder type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('reminder_type', $type);
    }

    /**
     * Check if reminder is due
     */
    public function isDue(): bool
    {
        return $this->status === 'pending' && $this->reminder_time <= now();
    }

    /**
     * Mark reminder as sent
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    /**
     * Mark reminder as failed
     */
    public function markAsFailed(): bool
    {
        return $this->update([
            'status' => 'failed'
        ]);
    }

    /**
     * Cancel reminder
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => 'cancelled'
        ]);
    }

}

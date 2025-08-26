<?php

namespace Modules\Notifications\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'user_type',
        'notification_id',
        'is_read',
        'read_at',
        'email_sent',
        'email_sent_at',
        'push_sent',
        'push_sent_at',
        'sms_sent',
        'sms_sent_at',
        'in_app_sent',
        'in_app_sent_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'email_sent' => 'boolean',
        'push_sent' => 'boolean',
        'sms_sent' => 'boolean',
        'in_app_sent' => 'boolean',
        'read_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'push_sent_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'in_app_sent_at' => 'datetime'
    ];

    /**
     * Get the notification
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, $userId, $userType)
    {
        return $query->where('user_id', $userId)
                    ->where('user_type', $userType);
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Mark email as sent
     */
    public function markEmailAsSent(): void
    {
        $this->update([
            'email_sent' => true,
            'email_sent_at' => now()
        ]);
    }

    /**
     * Mark push as sent
     */
    public function markPushAsSent(): void
    {
        $this->update([
            'push_sent' => true,
            'push_sent_at' => now()
        ]);
    }

    /**
     * Mark SMS as sent
     */
    public function markSmsAsSent(): void
    {
        $this->update([
            'sms_sent' => true,
            'sms_sent_at' => now()
        ]);
    }
}

<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model Calendar - View layer cho Task
 * 
 * Calendar chỉ dùng để hiển thị Task vào đúng ngày, đúng người nhận
 * Không chứa business logic, chỉ delegate đến Task
 */
class Calendar extends Model
{
    use HasFactory;
    
    protected $table = 'calendar';
    
    protected static function newFactory()
    {
        return \Database\Factories\CalendarFactory::new();
    }
    
    protected $fillable = [
        'title',
        'description',
        'start_time',
        'end_time',
        'event_type',
        'task_id',
        'creator_id',
        'creator_type'
    ];

    public $timestamps = false;
    
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Lấy task liên quan
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Delegate tất cả receiver operations đến Task
     */
    public function getTaskReceivers()
    {
        return $this->task?->receivers ?? collect();
    }

    public function getAllStudents()
    {
        return $this->task?->getAllStudents() ?? collect();
    }

    public function getAllLecturers()
    {
        return $this->task?->getAllLecturers() ?? collect();
    }

    public function addReceiver(int $receiverId, string $receiverType)
    {
        return $this->task?->addReceiver($receiverId, $receiverType);
    }

    public function removeReceiver(int $receiverId, string $receiverType): bool
    {
        return $this->task?->removeReceiver($receiverId, $receiverType) ?? false;
    }

    public function hasReceiver(int $userId, string $userType): bool
    {
        return $this->task?->hasReceiver($userId, $userType) ?? false;
    }
}

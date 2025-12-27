<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Task - Đại diện cho bảng task trong database
 * 
 * Model này định nghĩa cấu trúc và relationships của Task
 * Tuân thủ Clean Architecture: chỉ chứa relationships và basic accessors/mutators
 */
class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tên bảng trong database
     */
    protected $table = 'task';

    /**
     * Tạo factory instance cho model để sử dụng trong testing
     * 
     * @return \Modules\Task\Database\Factories\TaskFactory|null
     */
    protected static function newFactory()
    {
        // Factory sẽ được tạo khi cần thiết cho testing
        return null;
    }

    /**
     * Các trường có thể mass assign
     */
    protected $fillable = [
        'title',
        'description',
        'due_date', // ✅ Thêm due_date
        'deadline',
        'status',
        'priority',
        'creator_id',
        'creator_type',
        'assigned_to', // ✅ Thêm assigned_to
        'assigned_to_id', // ✅ Thêm assigned_to_id
        'include_new_students',
        'include_new_lecturers'
    ];

    /**
     * ✅ Sử dụng proper timestamps với Laravel conventions
     */
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Các trường được cast sang kiểu dữ liệu cụ thể
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'due_date' => 'date', // ✅ Thêm cast cho due_date
        'deadline' => 'datetime',
        'include_new_students' => 'boolean',
        'include_new_lecturers' => 'boolean',
    ];

    /**
     * Lấy danh sách files đính kèm của task
     * 
     * @return HasMany Relationship với TaskFile
     */
    public function files(): HasMany
    {
        return $this->hasMany(TaskFile::class, 'task_id');
    }

    /**
     * Lấy danh sách submissions của task
     * 
     * @return HasMany Relationship với TaskSubmission
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class, 'task_id');
    }



    // ❌ Calendar events relationship removed - không cần thiết cho task system

    /**
     * Lấy tất cả receivers của task
     * 
     * @return HasMany Relationship với TaskReceiver
     */
    public function receivers(): HasMany
    {
        return $this->hasMany(TaskReceiver::class, 'task_id');
    }

    /**
     * Get the creator (polymorphic relationship)
     * Creator có thể là Lecturer, Admin, hoặc User
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function creator()
    {
        return $this->morphTo('creator', 'creator_type', 'creator_id');
    }

    /**
     * ✅ Get business logic service instance
     * 
     * @return \Modules\Task\app\Services\TaskBusinessLogicService
     */
    public function getBusinessLogicService(): \Modules\Task\app\Services\TaskBusinessLogicService
    {
        return app(\Modules\Task\app\Services\TaskBusinessLogicService::class);
    }

    /**
     * ✅ Lấy tất cả students nhận task này (delegated to service)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllStudents()
    {
        return $this->getBusinessLogicService()->getAllStudentsForTask($this);
    }

    /**
     * ✅ Lấy tất cả lecturers nhận task này (delegated to service)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllLecturers()
    {
        return $this->getBusinessLogicService()->getAllLecturersForTask($this);
    }

    /**
     * ✅ Thêm receiver cho task (delegated to service)
     * 
     * @param int $receiverId
     * @param string $receiverType
     * @return TaskReceiver
     */
    public function addReceiver(int $receiverId, string $receiverType): TaskReceiver
    {
        return $this->getBusinessLogicService()->addReceiverToTask($this, $receiverId, $receiverType);
    }

    /**
     * ✅ Xóa receiver khỏi task (delegated to service)
     * 
     * @param int $receiverId
     * @param string $receiverType
     * @return bool
     */
    public function removeReceiver(int $receiverId, string $receiverType): bool
    {
        return $this->getBusinessLogicService()->removeReceiverFromTask($this, $receiverId, $receiverType);
    }

    /**
     * ✅ Kiểm tra xem một user có nhận task này không (delegated to service)
     * 
     * @param int $userId
     * @param string $userType
     * @return bool
     */
    public function hasReceiver(int $userId, string $userType): bool
    {
        return $this->getBusinessLogicService()->isUserTaskReceiver($this, $userId, $userType);
    }
}

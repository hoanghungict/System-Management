<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model TaskFile - Đại diện cho bảng task_file trong database
 * 
 * Model này định nghĩa cấu trúc và relationships của Task Files
 * Tuân thủ Clean Architecture: chỉ chứa relationships và basic accessors/mutators
 */
class TaskFile extends Model
{
    /**
     * Tên bảng trong database
     */
    protected $table = 'task_file';
    
    /**
     * Các trường có thể mass assign
     */
    protected $fillable = [
        'task_id',
        'file_path'
    ];

    /**
     * Lấy task sở hữu file này
     * 
     * @return BelongsTo Relationship với Task
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Lấy tên file từ đường dẫn
     * 
     * @return string Tên file
     */
    public function getFileNameAttribute(): string
    {
        return basename($this->file_path);
    }

    /**
     * Lấy URL của file
     * 
     * @return string URL đầy đủ của file
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}

<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
     * Sử dụng timestamps
     */
    public $timestamps = true;
    
    /**
     * Các trường có thể mass assign
     * Schema thực tế: id, task_id, name, path, size, created_at, updated_at
     */
    protected $fillable = [
        'task_id',
        'name',      // Tên file gốc
        'path',      // Đường dẫn file trong storage
        'size',      // Kích thước file
    ];
    
    /**
     * Các trường được cast sang kiểu dữ liệu cụ thể
     */
    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * Lấy tên file gốc (alias cho name)
     * 
     * @return string Tên file
     */
    public function getFileNameAttribute(): string
    {
        return $this->name ?? basename($this->path ?? '');
    }

    /**
     * Lấy URL của file
     * 
     * @return string URL đầy đủ của file
     */
    public function getFileUrlAttribute(): string
    {
        if (!$this->path) {
            return '';
        }
        
        // Sử dụng Storage::url() để tạo URL chính xác từ disk public
        // Format: {APP_URL}/storage/{path}
        // Ví dụ: http://localhost:8082/storage/task-files/125/abc.pdf
        $url = Storage::disk('public')->url($this->path);
        
        // Ensure URL is absolute (fallback nếu APP_URL chưa set)
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // Fallback: Tạo URL từ config hoặc request (nếu available)
            $baseUrl = config('app.url');
            if (!$baseUrl && app()->runningInConsole() === false) {
                try {
                    $baseUrl = request()->getSchemeAndHttpHost();
                } catch (\Exception $e) {
                    // Fallback to localhost if request not available
                    $baseUrl = 'http://localhost';
                }
            }
            $url = rtrim($baseUrl ?: 'http://localhost', '/') . '/storage/' . ltrim($this->path, '/');
        }
        
        return $url;
    }
}

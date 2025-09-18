<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model TaskReceiver - Quản lý many-to-many relationship giữa Task và receivers
 * 
 * Hỗ trợ các loại receiver: student, lecturer, class, all_students, all_lecturers
 */
class TaskReceiver extends Model
{
    protected $table = 'task_receivers';
    
    protected $fillable = [
        'task_id',
        'receiver_id',
        'receiver_type'
    ];

    /**
     * Các loại receiver được phép
     */
    const ALLOWED_RECEIVER_TYPES = ['student', 'lecturer', 'class', 'all_students', 'all_lecturers'];

    /**
     * ✅ Lấy task liên quan với correct namespace
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(\Modules\Task\app\Models\Task::class, 'task_id');
    }

    /**
     * Lấy student receiver
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\app\Models\Student::class, 'receiver_id');
    }

    /**
     * Lấy lecturer receiver
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\app\Models\Lecturer::class, 'receiver_id');
    }

    /**
     * Lấy class receiver
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\app\Models\Classroom::class, 'receiver_id');
    }

    /**
     * Lấy tất cả students thực tế từ receiver này
     */
    public function getActualStudents()
    {
        switch ($this->receiver_type) {
            case 'student':
                return collect([$this->student]);
                
            case 'lecturer':
                return collect(); // Lecturer không phải student
                
            case 'class':
                // Lấy tất cả students trong class
                return \Modules\Auth\app\Models\Student::where('class_id', $this->receiver_id)->get();
                
            case 'all_students':
                // Lấy tất cả students trong toàn hệ thống nếu receiver_id = 0
                if ($this->receiver_id == 0) {
                    return \Modules\Auth\app\Models\Student::with('classroom')->get();
                }
                // Lấy tất cả students trong faculty cụ thể
                return \Modules\Auth\app\Models\Student::whereHas('classroom', function($query) {
                    $query->where('faculty_id', $this->receiver_id);
                })->get();
                
            case 'all_lecturers':
                return collect(); // all_lecturers không trả về students
                
            default:
                return collect();
        }
    }

    /**
     * Lấy tất cả lecturers thực tế từ receiver này
     */
    public function getActualLecturers()
    {
        switch ($this->receiver_type) {
            case 'student':
                return collect(); // Student không phải lecturer
                
            case 'lecturer':
                return collect([$this->lecturer]);
                
            case 'class':
            case 'all_students':
                return collect(); // Class và all_students không phải lecturer
                
            case 'all_lecturers':
                // Lấy tất cả lecturers trong toàn hệ thống nếu receiver_id = 0
                if ($this->receiver_id == 0) {
                    return \Modules\Auth\app\Models\Lecturer::all();
                }
                // Lấy tất cả lecturers trong faculty cụ thể
                return \Modules\Auth\app\Models\Lecturer::where('faculty_id', $this->receiver_id)->get();
                
            default:
                return collect();
        }
    }
}

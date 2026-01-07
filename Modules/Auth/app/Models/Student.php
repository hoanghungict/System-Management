<?php

declare(strict_types=1);

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    /**
     * NOTE: SoftDeletes đã được disabled để xóa thật trong DB
     * 
     * Nếu muốn sử dụng lại soft delete (chỉ set deleted_at thay vì xóa thật):
     * 1. Uncomment dòng: use Illuminate\Database\Eloquent\SoftDeletes;
     * 2. Uncomment dòng: use SoftDeletes;
     * 3. Trong StudentService::deleteStudent(), đổi forceDelete() thành delete()
     * 
     * Khi dùng SoftDeletes:
     * - delete() chỉ set deleted_at = now(), không xóa thật
     * - forceDelete() xóa thật khỏi DB
     * - restore() phục hồi record đã xóa
     * - withTrashed() query bao gồm cả records đã xóa
     */
    // use SoftDeletes;

    protected $table = 'student';

    protected $fillable = [
        'full_name',
        'birth_date',
        'gender',
        'address',
        'email',
        'phone',
        'student_code',
        'class_id',
        'imported_at',
        'import_job_id',
        'account_status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'class_id' => 'integer',
        'import_job_id' => 'integer',
        'imported_at' => 'datetime',
        'account_status' => 'string',
    ];

    /**
     * Get the class this student belongs to
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Get the account for this student
     */
    public function account(): HasOne
    {
        return $this->hasOne(StudentAccount::class, 'student_id');
    }

    /**
     * Get the import job that created this student
     */
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class, 'import_job_id');
    }

    /**
     * Get audit logs for this student
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'target');
    }

    /**
     * Get audit logs where this student is the user
     */
    public function performedAuditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}

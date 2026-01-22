<?php

declare(strict_types=1);

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use SoftDeletes, HasFactory;

    protected static function newFactory()
    {
        return new \Modules\Auth\database\factories\StudentFactory();
    }

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

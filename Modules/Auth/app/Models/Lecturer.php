<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lecturer extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return new \Modules\Auth\database\factories\LecturerFactory();
    }
    public $timestamps = false;
    protected $table = 'lecturer';

    protected $fillable = [
        'full_name', 'gender', 'address', 'email', 'phone', 'lecturer_code', 
        'department_id', 'experience_number', 'birth_date',
        'bang_cap', 'ngay_bat_dau_lam_viec', 'hinh_anh'
    ];

    protected $casts = [
        'department_id' => 'integer'
    ];

    /**
     * Get the unit this lecturer belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the faculty this lecturer belongs to (alias for department)
     */
    public function faculty()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the account for this lecturer
     */
    public function account()
    {
        return $this->hasOne(LecturerAccount::class, 'lecturer_id');
    }

    /**
     * Get the classes this lecturer teaches
     */
    public function classes()
    {
        return $this->hasMany(Classroom::class, 'lecturer_id');
    }

    /**
     * Get audit logs for this lecturer
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'target');
    }

    /**
     * Get audit logs where this lecturer is the user
     */
    public function performedAuditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}

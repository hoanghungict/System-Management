<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $table = 'class';

    protected $fillable = [
        'class_name', 'class_code', 'department_id', 'lecturer_id', 'school_year'
    ];

    protected $casts = [
        'department_id' => 'integer',
        'lecturer_id' => 'integer'
    ];

    /**
     * Get the department this class belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the faculty this class belongs to (alias for department)
     */
    public function faculty()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the lecturer teaching this class
     */
    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    /**
     * Get the students in this class
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}

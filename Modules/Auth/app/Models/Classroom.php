<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $table = 'class';

    protected $fillable = [
        'class_name', 'class_code', 'faculty_id', 'lecturer_id', 'school_year'
    ];

    protected $casts = [
        'faculty_id' => 'integer',
        'lecturer_id' => 'integer'
    ];

    /**
     * Get the faculty this class belongs to
     */
    public function faculty()
    {
        return $this->belongsTo(Department::class, 'faculty_id');
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

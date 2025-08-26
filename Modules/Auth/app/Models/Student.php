<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'student';

    protected $fillable = [
        'full_name', 'birth_date', 'gender', 'address', 'email', 'phone', 'student_code', 'class_id'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'class_id' => 'integer'
    ];

    /**
     * Get the class this student belongs to
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Get the account for this student
     */
    public function account()
    {
        return $this->hasOne(StudentAccount::class, 'student_id');
    }
}

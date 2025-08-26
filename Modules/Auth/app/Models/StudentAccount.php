<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAccount extends Model
{
    protected $table = 'student_account';

    protected $fillable = [
        'student_id', 'username', 'password'
    ];

    protected $casts = [
        'student_id' => 'integer'
    ];

    protected $hidden = [
        'password'
    ];

    /**
     * Get the student this account belongs to
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}

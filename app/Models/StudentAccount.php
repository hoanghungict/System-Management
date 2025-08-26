<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAccount extends Model
{
    protected $table = 'sinh_vien_account';

    protected $fillable = ['sinh_vien_id', 'username', 'password'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'sinh_vien_id');
    }
}

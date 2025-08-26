<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'sinh_vien';

    protected $fillable = [
        'ho_ten', 'ngay_sinh', 'gioi_tinh', 'dia_chi', 'email', 'sdt', 'ma_sinh_vien', 'lop_id'
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'lop_id');
    }

    public function account()
    {
        return $this->hasOne(StudentAccount::class, 'sinh_vien_id');
    }
}

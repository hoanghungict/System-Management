<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $table = 'lop';

    protected $fillable = [
        'ten_lop', 'ma_lop', 'khoa_id', 'giang_vien_id', 'nam_hoc'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'khoa_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'giang_vien_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'lop_id');
    }
}

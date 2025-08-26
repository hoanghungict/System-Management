<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    protected $table = 'giang_vien';

    protected $fillable = [
        'ho_ten', 'gioi_tinh', 'dia_chi', 'email', 'sdt', 'ma_giao_vien', 'don_vi_id'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'don_vi_id');
    }

    public function account()
    {
        return $this->hasOne(LecturerAccount::class, 'giang_vien_id');
    }

    public function classes()
    {
        return $this->hasMany(Classroom::class, 'giang_vien_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LecturerAccount extends Model
{
    protected $table = 'giang_vien_account';

    protected $fillable = ['giang_vien_id', 'username', 'password' , 'is_admin'];

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'giang_vien_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $table = 'calendar';

    protected $fillable = [
        'tieu_de', 'mo_ta', 'thoi_gian_bat_dau', 'thoi_gian_ket_thuc',
        'loai_su_kien', 'task_id', 'nguoi_tham_gia_id', 'loai_nguoi_tham_gia',
        'nguoi_tao_id', 'loai_nguoi_tao'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}

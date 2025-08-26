<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;

class LecturerAccount extends Model
{
    protected $table = 'lecturer_account';

    protected $fillable = [
        'lecturer_id', 'username', 'password', 'is_admin'
    ];

    protected $casts = [
        'lecturer_id' => 'integer',
        'is_admin' => 'boolean'
    ];

    protected $hidden = [
        'password'
    ];

    /**
     * Get the lecturer this account belongs to
     */
    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }
}

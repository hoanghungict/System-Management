<?php

declare(strict_types=1);

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportFailure extends Model
{
    protected $table = 'import_failures';

    public $timestamps = false;

    protected $fillable = [
        'import_job_id',
        'row_number',
        'attribute',
        'errors',
        'values',
    ];

    protected $casts = [
        'import_job_id' => 'integer',
        'row_number' => 'integer',
        'values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the import job this failure belongs to
     */
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class, 'import_job_id');
    }
}


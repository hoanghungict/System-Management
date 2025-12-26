<?php

declare(strict_types=1);

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ImportJob extends Model
{
    protected $table = 'import_jobs';

    protected $fillable = [
        'user_id',
        'entity_type',
        'file_path',
        'status',
        'total',
        'processed_rows',
        'success',
        'failed',
        'error',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'total' => 'integer',
        'processed_rows' => 'integer',
        'success' => 'integer',
        'failed' => 'integer',
    ];

    /**
     * Increment processed rows
     */
    public function incrementProcessed(int $count = 1): void
    {
        $this->increment('processed_rows', $count);
    }

    /**
     * Increment success count
     */
    public function incrementSuccess(int $count = 1): void
    {
        $this->increment('success', $count);
    }

    /**
     * Increment failed count
     */
    public function incrementFailed(int $count = 1): void
    {
        $this->increment('failed', $count);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercent(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }

        return round(($this->processed_rows / $this->total) * 100, 2);
    }

    /**
     * Check if import is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'done' || $this->status === 'failed';
    }

    /**
     * Get the import failures for this job
     */
    public function failures(): HasMany
    {
        return $this->hasMany(ImportFailure::class, 'import_job_id');
    }

    /**
     * Get the students imported by this job
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'import_job_id');
    }

    /**
     * Get audit logs for this import job
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'target');
    }
}


<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\Lecturer;

/**
 * QuestionImportLog Model - Tracking import câu hỏi
 */
class QuestionImportLog extends Model
{
    protected $table = 'question_import_logs';

    protected $fillable = [
        'assignment_id',
        'file_name',
        'total_rows',
        'processed_rows',
        'success_count',
        'error_count',
        'status',
        'error_details',
        'imported_by',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'error_details' => 'array',
    ];

    // ========== Relationships ==========

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'imported_by');
    }

    // ========== Scopes ==========

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // ========== Methods ==========

    /**
     * Update progress
     */
    public function updateProgress(int $processedRows, int $successCount = 0): void
    {
        $this->processed_rows = $processedRows;
        $this->success_count += $successCount;
        $this->save();
    }

    /**
     * Mark as completed
     */
    public function markCompleted(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Mark as failed with error details
     */
    public function markFailed(int $row, string $error, array $rowData = []): void
    {
        $this->status = 'failed';
        $this->error_count = 1;
        $this->error_details = [
            'row' => $row,
            'message' => $error,
            'data' => $rowData,
        ];
        $this->save();
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }
}

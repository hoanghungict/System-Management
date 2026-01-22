<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SubmissionQuestion Model
 * Lưu danh sách câu hỏi đã random cho mỗi submission (Mã đề riêng)
 */
class SubmissionQuestion extends Model
{
    protected $table = 'submission_questions';

    protected $fillable = [
        'submission_id',
        'question_id',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    // ========== Relationships ==========

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}

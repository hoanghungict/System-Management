<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Answer Model - Câu trả lời của sinh viên
 */
class Answer extends Model
{
    protected $table = 'answers';

    protected $fillable = [
        'submission_id',
        'question_id',
        'answer_text',
        'file_path',
        'is_correct',
        'score',
        'feedback',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'score' => 'decimal:2',
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

    // ========== Methods ==========

    /**
     * Auto-check answer correctness
     */
    public function checkAnswer(): void
    {
        if (!$this->question->is_auto_gradable) {
            return;
        }

        $this->is_correct = $this->question->isAnswerCorrect($this->answer_text ?? '');
        $this->score = $this->is_correct ? $this->question->points : 0;
        $this->save();
    }

    /**
     * Manual grade answer
     */
    public function gradeManually(float $score, ?string $feedback = null): bool
    {
        $this->score = min($score, $this->question->points);
        $this->feedback = $feedback;
        $this->is_correct = $score >= ($this->question->points * 0.5); // 50% threshold
        return $this->save();
    }
}

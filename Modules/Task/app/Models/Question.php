<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Question Model - Câu hỏi trong bài tập hoặc ngân hàng câu hỏi
 */
class Question extends Model
{
    use SoftDeletes, HasFactory;

    protected static function newFactory()
    {
        return new \Modules\Task\database\factories\QuestionFactory();
    }

    protected $table = 'questions';

    protected $fillable = [
        'question_bank_id', // NEW: thuộc ngân hàng câu hỏi
        'chapter_id',       // NEW: thuộc chương
        'subject_code',     // NEW: mã môn học
        'assignment_id',    // Giữ cho backward compatibility
        'type',
        'content',
        'options',
        'correct_answer',
        'points',
        'order_index',
        'difficulty',       // easy, medium, hard
        'rubric',           // Grading criteria for essay questions
        'explanation',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'decimal:2',
        'order_index' => 'integer',
    ];

    // ========== Relationships ==========

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    /**
     * Ngân hàng câu hỏi chứa câu hỏi này
     */
    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    /**
     * Chương của câu hỏi
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'question_id');
    }

    // ========== Scopes ==========

    public function scopeMultipleChoice($query)
    {
        return $query->where('type', 'multiple_choice');
    }

    public function scopeEssay($query)
    {
        return $query->where('type', 'essay');
    }

    public function scopeShortAnswer($query)
    {
        return $query->where('type', 'short_answer');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByChapter($query, int $chapterId)
    {
        return $query->where('chapter_id', $chapterId);
    }

    public function scopeByQuestionBank($query, int $questionBankId)
    {
        return $query->where('question_bank_id', $questionBankId);
    }

    // ========== Methods ==========

    /**
     * Check if answer is correct
     */
    public function isAnswerCorrect(string $answer): bool
    {
        if ($this->type === 'multiple_choice') {
            return strtoupper(trim($answer)) === strtoupper(trim($this->correct_answer));
        }

        if ($this->type === 'short_answer') {
            // Keyword matching
            $keywords = array_map('trim', explode('|', $this->correct_answer));
            $answerLower = strtolower(trim($answer));
            
            foreach ($keywords as $keyword) {
                if (str_contains($answerLower, strtolower($keyword))) {
                    return true;
                }
            }
            return false;
        }

        // Essay questions need manual grading
        return false;
    }

    /**
     * Get formatted options for frontend
     */
    public function getFormattedOptionsAttribute(): array
    {
        if (!$this->options) {
            return [];
        }

        return $this->options;
    }

    /**
     * Check if this is auto-gradable
     */
    public function getIsAutoGradableAttribute(): bool
    {
        return in_array($this->type, ['multiple_choice', 'short_answer']);
    }
}


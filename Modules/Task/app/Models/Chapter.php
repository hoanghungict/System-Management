<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Chapter Model - Chương trong ngân hàng câu hỏi
 */
class Chapter extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return new \Modules\Task\database\factories\ChapterFactory();
    }
    protected $table = 'chapters';

    protected $fillable = [
        'question_bank_id',
        'name',
        'code',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    protected $appends = [
        'total_questions',
    ];

    // ========== Relationships ==========

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'chapter_id');
    }

    // ========== Scopes ==========

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    // ========== Accessors ==========

    /**
     * Lấy tổng số câu hỏi trong chương
     */
    public function getTotalQuestionsAttribute(): int
    {
        return $this->attributes['questions_count'] ?? $this->questions()->count();
    }

    /**
     * Lấy số câu hỏi theo độ khó trong chương
     */
    public function getQuestionsByDifficultyAttribute(): array
    {
        return [
            'easy' => $this->questions()->where('difficulty', 'easy')->count(),
            'medium' => $this->questions()->where('difficulty', 'medium')->count(),
            'hard' => $this->questions()->where('difficulty', 'hard')->count(),
        ];
    }
}

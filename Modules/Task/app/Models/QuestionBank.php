<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Auth\app\Models\Lecturer;

/**
 * QuestionBank Model - Ngân hàng câu hỏi theo môn học
 */
class QuestionBank extends Model
{
    use SoftDeletes, HasFactory;

    protected static function newFactory()
    {
        return new \Modules\Task\database\factories\QuestionBankFactory();
    }

    protected $table = 'question_banks';

    protected $fillable = [
        'course_id',
        'lecturer_id',
        'name',
        'description',
        'subject_code',
        'status',
        'material_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // ========== Relationships ==========

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\app\Models\Attendance\Course::class, 'course_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class, 'question_bank_id')->orderBy('order_index');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'question_bank_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'question_bank_id');
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByLecturer($query, int $lecturerId)
    {
        return $query->where('lecturer_id', $lecturerId);
    }

    public function scopeBySubject($query, string $subjectCode)
    {
        return $query->where('subject_code', $subjectCode);
    }

    // ========== Accessors ==========

    /**
     * Lấy tổng số câu hỏi trong ngân hàng
     */
    public function getTotalQuestionsAttribute(): int
    {
        return $this->questions()->count();
    }

    /**
     * Lấy số câu hỏi theo độ khó
     */
    public function getQuestionsByDifficultyAttribute(): array
    {
        return [
            'easy' => $this->questions()->where('difficulty', 'easy')->count(),
            'medium' => $this->questions()->where('difficulty', 'medium')->count(),
            'hard' => $this->questions()->where('difficulty', 'hard')->count(),
        ];
    }

    /**
     * Lấy số lượng chương
     */
    public function getChaptersCountAttribute(): int
    {
        return $this->chapters()->count();
    }
}

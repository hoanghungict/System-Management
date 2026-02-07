<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Auth\app\Models\Lecturer;

/**
 * Exam Model - Đề thi
 */
class Exam extends Model
{
    use SoftDeletes, HasFactory;

    protected static function newFactory()
    {
        return new \Modules\Task\database\factories\ExamFactory();
    }

    protected $table = 'exams';

    protected $fillable = [
        'question_bank_id',
        'course_id',
        'lecturer_id',
        'title',
        'description',
        'time_limit',
        'total_questions',
        'max_attempts',
        'difficulty_config',
        'exam_codes_count',
        'show_answers_after_submit',
        'shuffle_questions',
        'shuffle_options',
        'anti_cheat_enabled',
        'status',
        'start_time',
        'end_time',
        'slug',
    ];

    protected $casts = [
        'time_limit' => 'integer',
        'total_questions' => 'integer',
        'max_attempts' => 'integer',
        'difficulty_config' => 'array',
        'exam_codes_count' => 'integer',
        'show_answers_after_submit' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'anti_cheat_enabled' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // ========== Constants ==========
    
    // Tỉ lệ độ khó chuẩn Việt Nam (50% dễ, 33% khá, 17% khó)
    public const DEFAULT_DIFFICULTY_RATIO = [
        'easy' => 0.50,
        'medium' => 0.33,
        'hard' => 0.17,
    ];

    // Thời gian trung bình mỗi câu (phút)
    public const MINUTES_PER_QUESTION = 1.5;

    // ========== Relationships ==========

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\app\Models\Attendance\Course::class, 'course_id');
    }

    public function examCodes(): HasMany
    {
        return $this->hasMany(ExamCode::class, 'exam_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ExamSubmission::class, 'exam_id');
    }

    // ========== Scopes ==========

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByLecturer($query, int $lecturerId)
    {
        return $query->where('lecturer_id', $lecturerId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('start_time')
                    ->orWhere('start_time', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_time')
                    ->orWhere('end_time', '>=', now());
            });
    }

    // ========== Accessors ==========

    /**
     * Kiểm tra đề thi đã hết hạn chưa
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->end_time && $this->end_time->isPast();
    }

    /**
     * Kiểm tra đề thi đã mở chưa
     */
    public function getIsOpenAttribute(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        $now = now();
        $afterStart = !$this->start_time || $now->gte($this->start_time);
        $beforeEnd = !$this->end_time || $now->lte($this->end_time);

        return $afterStart && $beforeEnd;
    }

    /**
     * Tính điểm mỗi câu
     */
    public function getPointsPerQuestionAttribute(): float
    {
        if ($this->total_questions <= 0) {
            return 0;
        }
        return round(10 / $this->total_questions, 4);
    }

    /**
     * Tính số câu gợi ý dựa trên thời gian
     */
    public static function calculateSuggestedQuestions(int $timeLimit): int
    {
        return (int) floor($timeLimit / self::MINUTES_PER_QUESTION);
    }

    /**
     * Tính cấu hình độ khó gợi ý
     */
    public static function calculateDifficultyConfig(int $totalQuestions): array
    {
        return [
            'easy' => (int) round($totalQuestions * self::DEFAULT_DIFFICULTY_RATIO['easy']),
            'medium' => (int) round($totalQuestions * self::DEFAULT_DIFFICULTY_RATIO['medium']),
            'hard' => (int) round($totalQuestions * self::DEFAULT_DIFFICULTY_RATIO['hard']),
        ];
    }

    // ========== Methods ==========

    /**
     * Publish exam
     */
    public function publish(): bool
    {
        // Kiểm tra đã có mã đề chưa
        if ($this->examCodes()->count() === 0) {
            return false;
        }

        $this->status = 'published';
        return $this->save();
    }

    /**
     * Close exam
     */
    public function close(): bool
    {
        $this->status = 'closed';
        $saved = $this->save();

        if ($saved) {
            // Force submit all in-progress submissions
            $this->submissions()
                ->where('status', 'in_progress')
                ->get()
                ->each(function ($submission) {
                    $submission->submit();
                    // Optional: Dispatch event to notify student
                    \Modules\Task\app\Events\ExamSubmissionCreated::dispatch($submission);
                });
        }

        return $saved;
    }
}

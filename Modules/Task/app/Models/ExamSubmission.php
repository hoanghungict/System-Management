<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\AuditLog;
use Modules\Task\app\Traits\Gradable;

/**
 * ExamSubmission Model - Bài làm thi của sinh viên
 */
class ExamSubmission extends Model
{
    use SoftDeletes, HasFactory, Gradable;

    protected static function newFactory()
    {
        return new \Modules\Task\database\factories\ExamSubmissionFactory();
    }

    protected $table = 'exam_submissions';

    protected $fillable = [
        'exam_id',
        'exam_code_id',
        'student_id',
        'attempt',
        'started_at',
        'submitted_at',
        'correct_count',
        'wrong_count',
        'unanswered_count',
        'total_score',
        'manual_score',
        'graded_by',
        'graded_at',
        'grader_note',
        'status',
        'anti_cheat_violations',
        'answers',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'correct_count' => 'integer',
        'wrong_count' => 'integer',
        'unanswered_count' => 'integer',
        'total_score' => 'decimal:2',
        'manual_score' => 'decimal:2',
        'anti_cheat_violations' => 'array',
        'answers' => 'array',
    ];

    protected $appends = [
        'remaining_time',
        'is_time_limit_exceeded',
        'final_score',
        'auto_score',
    ];

    // ========== Relationships ==========

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function examCode(): BelongsTo
    {
        return $this->belongsTo(ExamCode::class, 'exam_code_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'graded_by');
    }

    // ========== Scopes ==========

    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByExam($query, int $examId)
    {
        return $query->where('exam_id', $examId);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    // ========== Boot for AuditLog ==========

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            AuditLog::log(
                'exam_started',
                $model->student_id,
                'ExamSubmission',
                $model->id,
                [
                    'exam_id' => $model->exam_id,
                    'exam_code' => $model->examCode?->code,
                    'attempt' => $model->attempt
                ]
            );
        });
    }

    // ========== Accessors ==========

    /**
     * Lấy thời gian còn lại (giây)
     */
    public function getRemainingTimeAttribute(): ?int
    {
        if (!$this->exam->time_limit || !$this->started_at) {
            return null;
        }

        $endTime = $this->started_at->addMinutes($this->exam->time_limit);
        $remaining = now()->diffInSeconds($endTime, false);
        
        return max(0, $remaining);
    }

    /**
     * Kiểm tra đã hết thời gian chưa
     */
    public function getIsTimeLimitExceededAttribute(): bool
    {
        if (!$this->exam->time_limit || !$this->started_at) {
            return false;
        }

        $endTime = $this->started_at->addMinutes($this->exam->time_limit);
        return now()->gt($endTime);
    }

    /**
     * Alias for total_score for consistency with AssignmentSubmissions
     */
    public function getAutoScoreAttribute(): float
    {
        return (float) ($this->total_score ?? 0);
    }

    // ========== Methods ==========

    /**
     * Nộp bài thi
     */
    public function submit(): bool
    {
        $this->submitted_at = now();
        $this->status = 'submitted';
        
        // Tính điểm
        $this->calculateScore();
        
        $saved = $this->save();

        if ($saved) {
            AuditLog::log(
                'exam_submitted',
                $this->student_id,
                'ExamSubmission',
                $this->id,
                [
                    'exam_id' => $this->exam_id,
                    'correct_count' => $this->correct_count,
                    'total_score' => $this->total_score,
                ]
            );
        }

        return $saved;
    }

    /**
     * Tính điểm tự động
     */
    public function calculateScore(): void
    {
        $answers = $this->answers ?? [];
        $examCode = $this->examCode;
        $questions = $examCode->getOrderedQuestions();
        
        $correct = 0;
        $wrong = 0;
        $unanswered = 0;

        foreach ($questions as $question) {
            $studentAnswer = $answers[$question->id] ?? null;
            
            if (!$studentAnswer) {
                $unanswered++;
                continue;
            }

            // Chuyển đổi đáp án về dạng gốc (nếu đã shuffle)
            $originalAnswer = $examCode->convertToOriginalAnswer($question->id, $studentAnswer);
            
            if ($question->isAnswerCorrect($originalAnswer)) {
                $correct++;
            } else {
                $wrong++;
            }
        }

        $this->correct_count = $correct;
        $this->wrong_count = $wrong;
        $this->unanswered_count = $unanswered;
        
        // Công thức: điểm = số câu đúng * (10 / tổng số câu)
        $totalQuestions = $this->exam->total_questions;
        $this->total_score = $totalQuestions > 0 
            ? round($correct * (10 / $totalQuestions), 2) 
            : 0;
    }

    /**
     * Giáo viên sửa điểm
     */
    public function gradeManually(int $graderId, float $score, ?string $note = null): bool
    {
        return $this->performManualGrading($graderId, $score, $note);
    }

    /**
     * Ghi log vi phạm anti-cheat
     */
    public function logAntiCheatViolation(string $type, ?array $details = null): void
    {
        $violations = $this->anti_cheat_violations ?? [];
        $violations[] = [
            'type' => $type,
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->anti_cheat_violations = $violations;
        $this->save();
    }
}

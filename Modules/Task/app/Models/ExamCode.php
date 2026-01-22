<?php

namespace Modules\Task\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ExamCode Model - Mã đề thi
 */
class ExamCode extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return new \Modules\Task\database\factories\ExamCodeFactory();
    }
    protected $table = 'exam_codes';

    protected $fillable = [
        'exam_id',
        'code',
        'question_order',
        'option_shuffle_map',
    ];

    protected $casts = [
        'question_order' => 'array',
        'option_shuffle_map' => 'array',
    ];

    // ========== Relationships ==========

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ExamSubmission::class, 'exam_code_id');
    }

    // ========== Methods ==========

    /**
     * Lấy danh sách câu hỏi theo thứ tự của mã đề này
     */
    public function getOrderedQuestions()
    {
        $questionIds = $this->question_order ?? [];
        
        if (empty($questionIds)) {
            return collect();
        }

        // Lấy câu hỏi và sắp xếp theo thứ tự trong question_order
        $questions = Question::with('chapter')->whereIn('id', $questionIds)->get();
        
        // Sắp xếp theo thứ tự trong mảng
        return $questions->sortBy(function ($question) use ($questionIds) {
            return array_search($question->id, $questionIds);
        })->values();
    }

    /**
     * Lấy đáp án đã xáo trộn cho 1 câu hỏi
     * 
     * @param int $questionId
     * @param array $originalOptions [{key: 'A', text: '...'}, ...]
     * @return array Đáp án đã xáo trộn
     */
    public function getShuffledOptions(int $questionId, array $originalOptions): array
    {
        $shuffleMap = $this->option_shuffle_map[$questionId] ?? null;
        
        if (!$shuffleMap) {
            return $originalOptions;
        }

        // Áp dụng shuffle map
        $shuffled = [];
        foreach ($shuffleMap as $newKey => $oldKey) {
            $originalOption = collect($originalOptions)->firstWhere('key', $oldKey);
            if ($originalOption) {
                $shuffled[] = [
                    'key' => $newKey,
                    'text' => $originalOption['text'],
                ];
            }
        }

        return $shuffled;
    }

    /**
     * Chuyển đổi đáp án của sinh viên về đáp án gốc
     * 
     * @param int $questionId
     * @param string $studentAnswer Đáp án sinh viên chọn (sau khi shuffle)
     * @return string Đáp án gốc
     */
    public function convertToOriginalAnswer(int $questionId, string $studentAnswer): string
    {
        $shuffleMap = $this->option_shuffle_map[$questionId] ?? null;
        
        if (!$shuffleMap) {
            return $studentAnswer;
        }

        // shuffleMap: {"A": "C", "B": "A", ...} nghĩa là A mới = C cũ
        // Cần tìm giá trị gốc tương ứng với đáp án sinh viên
        return $shuffleMap[$studentAnswer] ?? $studentAnswer;
    }
}

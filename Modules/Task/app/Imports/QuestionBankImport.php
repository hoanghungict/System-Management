<?php

namespace Modules\Task\app\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Modules\Task\app\Models\QuestionBank;
use Modules\Task\app\Models\Chapter;
use Modules\Task\app\Models\Question;
use Illuminate\Support\Facades\Log;

/**
 * QuestionBankImport
 * Import câu hỏi từ Excel vào ngân hàng câu hỏi
 * 
 * Format Excel:
 * | Nội dung | Đáp án A | Đáp án B | Đáp án C | Đáp án D | Đáp án đúng | Chương | Mức độ | Môn học |
 */
class QuestionBankImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected QuestionBank $questionBank;
    protected array $result = [
        'success' => 0,
        'failed' => 0,
        'errors' => [],
    ];
    protected array $chapterCache = [];

    public function __construct(QuestionBank $questionBank)
    {
        $this->questionBank = $questionBank;
        
        // Cache existing chapters
        foreach ($questionBank->chapters as $chapter) {
            $this->chapterCache[$chapter->code] = $chapter->id;
        }
    }

    public function collection(Collection $rows)
    {
        $orderIndex = Question::where('question_bank_id', $this->questionBank->id)->max('order_index') ?? 0;

        foreach ($rows as $index => $row) {
            try {
                $rowNumber = $index + 2; // +2 vì heading row là 1

                // Parse dữ liệu
                $content = $this->getValue($row, ['noi_dung', 'content', 'cau_hoi', 'question']);
                $optionA = $this->getValue($row, ['dap_an_a', 'option_a', 'a']);
                $optionB = $this->getValue($row, ['dap_an_b', 'option_b', 'b']);
                $optionC = $this->getValue($row, ['dap_an_c', 'option_c', 'c']);
                $optionD = $this->getValue($row, ['dap_an_d', 'option_d', 'd']);
                $correctAnswer = $this->getValue($row, ['dap_an_dung', 'correct_answer', 'correct', 'dap_an']);
                $chapterCode = $this->getValue($row, ['chuong', 'chapter', 'chapter_code']);
                $difficulty = $this->getValue($row, ['muc_do', 'difficulty', 'do_kho', 'level']);
                $subjectCode = $this->getValue($row, ['mon_hoc', 'subject', 'subject_code', 'ma_mon']);

                if (empty($content)) {
                    $this->result['errors'][] = "Dòng {$rowNumber}: Thiếu nội dung câu hỏi";
                    $this->result['failed']++;
                    continue;
                }

                // Parse difficulty
                $normalizedDifficulty = $this->normalizeDifficulty($difficulty);

                // Get or create chapter
                $chapterId = $this->getOrCreateChapter($chapterCode);

                // Build options array
                $options = [];
                if ($optionA) $options[] = ['key' => 'A', 'text' => trim($optionA)];
                if ($optionB) $options[] = ['key' => 'B', 'text' => trim($optionB)];
                if ($optionC) $options[] = ['key' => 'C', 'text' => trim($optionC)];
                if ($optionD) $options[] = ['key' => 'D', 'text' => trim($optionD)];

                // Normalize correct answer
                $normalizedAnswer = $this->normalizeAnswer($correctAnswer, $options);

                // Create question
                Question::create([
                    'question_bank_id' => $this->questionBank->id,
                    'chapter_id' => $chapterId,
                    'subject_code' => $subjectCode ?: $this->questionBank->subject_code,
                    'type' => 'multiple_choice',
                    'content' => trim($content),
                    'options' => $options,
                    'correct_answer' => $normalizedAnswer,
                    'difficulty' => $normalizedDifficulty,
                    'points' => 1,
                    'order_index' => ++$orderIndex,
                ]);

                $this->result['success']++;

            } catch (\Exception $e) {
                $rowNumber = $index + 2;
                $this->result['errors'][] = "Dòng {$rowNumber}: " . $e->getMessage();
                $this->result['failed']++;
                Log::error("Import row {$rowNumber} failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Lấy giá trị từ row với nhiều tên cột có thể
     */
    private function getValue($row, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            // Thử các biến thể của key
            $variations = [
                $key,
                strtolower($key),
                str_replace('_', ' ', $key),
                str_replace(' ', '_', $key),
            ];

            foreach ($variations as $variant) {
                if (isset($row[$variant]) && !empty($row[$variant])) {
                    return (string) $row[$variant];
                }
            }
        }
        return null;
    }

    /**
     * Normalize difficulty level
     */
    private function normalizeDifficulty(?string $difficulty): string
    {
        if (!$difficulty) return 'medium';

        $difficulty = strtolower(trim($difficulty));
        
        // Vietnamese mappings
        $mappings = [
            // Easy
            'dễ' => 'easy', 'de' => 'easy', 'easy' => 'easy', '1' => 'easy',
            'dễ dàng' => 'easy', 'cơ bản' => 'easy', 'co ban' => 'easy',
            // Medium
            'trung bình' => 'medium', 'trung binh' => 'medium', 'medium' => 'medium', '2' => 'medium',
            'tb' => 'medium', 'vừa' => 'medium', 'khá' => 'medium', 'kha' => 'medium',
            // Hard
            'khó' => 'hard', 'kho' => 'hard', 'hard' => 'hard', '3' => 'hard',
            'difficult' => 'hard', 'nâng cao' => 'hard', 'nang cao' => 'hard',
        ];

        return $mappings[$difficulty] ?? 'medium';
    }

    /**
     * Get or create chapter
     */
    private function getOrCreateChapter(?string $chapterCode): ?int
    {
        if (empty($chapterCode)) return null;

        $chapterCode = trim($chapterCode);

        // Check cache first
        if (isset($this->chapterCache[$chapterCode])) {
            return $this->chapterCache[$chapterCode];
        }

        // Try to find by code
        $chapter = Chapter::where('question_bank_id', $this->questionBank->id)
            ->where('code', $chapterCode)
            ->first();

        if (!$chapter) {
            // Create new chapter
            $maxOrder = Chapter::where('question_bank_id', $this->questionBank->id)->max('order_index') ?? 0;
            
            $chapter = Chapter::create([
                'question_bank_id' => $this->questionBank->id,
                'name' => "Chương: {$chapterCode}",
                'code' => $chapterCode,
                'order_index' => $maxOrder + 1,
            ]);
        }

        $this->chapterCache[$chapterCode] = $chapter->id;
        return $chapter->id;
    }

    /**
     * Normalize answer (có thể là "A", "1", hoặc text đáp án)
     */
    private function normalizeAnswer(?string $answer, array $options): string
    {
        if (empty($answer)) return '';

        $answer = trim($answer);

        // Nếu là A, B, C, D
        if (preg_match('/^[A-Da-d]$/i', $answer)) {
            return strtoupper($answer);
        }

        // Nếu là số (1, 2, 3, 4) -> convert to A, B, C, D
        if (in_array($answer, ['1', '2', '3', '4'])) {
            $map = ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D'];
            return $map[$answer];
        }

        // Nếu là text đáp án, tìm xem match với option nào
        foreach ($options as $option) {
            if (strtolower(trim($option['text'])) === strtolower($answer)) {
                return $option['key'];
            }
        }

        // Fallback: giả sử là A
        return strtoupper(substr($answer, 0, 1));
    }

    public function rules(): array
    {
        return [
            // Validation rules nếu cần
        ];
    }

    public function getResult(): array
    {
        return $this->result;
    }
}

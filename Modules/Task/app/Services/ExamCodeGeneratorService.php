<?php

namespace Modules\Task\app\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Models\Exam;
use Modules\Task\app\Models\ExamCode;
use Modules\Task\app\Models\Question;
use Modules\Task\app\Models\Chapter;

/**
 * ExamCodeGeneratorService
 * Tạo mã đề với câu hỏi random, đảm bảo:
 * - Phân bố đều qua các chương
 * - Theo tỉ lệ độ khó (50% dễ, 33% khá, 17% khó)
 * - Xáo trộn câu hỏi và đáp án
 */
class ExamCodeGeneratorService
{
    /**
     * Tạo nhiều mã đề cho 1 kỳ thi
     * 
     * @param Exam $exam
     * @param int $numberOfCodes Số mã đề cần tạo
     * @return array Danh sách mã đề đã tạo
     */
    public function generateExamCodes(Exam $exam, int $numberOfCodes = 4): array
    {
        $questionBank = $exam->questionBank;
        
        // Debug: Check questionBank
        if (!$questionBank) {
            Log::error("ExamCodeGenerator: questionBank is null for exam ID: {$exam->id}");
            throw new \Exception('Đề thi chưa được liên kết với ngân hàng câu hỏi.');
        }
        
        $difficultyConfig = $exam->difficulty_config ?? $this->calculateDefaultDifficultyConfig($exam->total_questions);
        
        Log::info("ExamCodeGenerator: Starting with difficultyConfig", $difficultyConfig);
        
        // Lấy tất cả chương của ngân hàng câu hỏi
        $chapters = $questionBank->chapters()->ordered()->get();
        $chaptersCount = $chapters->count();
        
        Log::info("ExamCodeGenerator: Found {$chaptersCount} chapters");
        
        if ($chaptersCount === 0) {
            throw new \Exception('Ngân hàng câu hỏi chưa có chương nào.');
        }

        // Validate đủ câu hỏi
        $this->validateQuestionPool($questionBank, $difficultyConfig);

        $examCodes = [];

        for ($i = 1; $i <= $numberOfCodes; $i++) {
            $code = str_pad($i, 3, '0', STR_PAD_LEFT); // "001", "002", ...
            
            // Random câu hỏi phân bố đều qua các chương
            $selectedQuestions = $this->selectQuestionsBalanced(
                $questionBank->id,
                $chapters,
                $difficultyConfig,
                $exam->total_questions
            );

            // Xáo trộn thứ tự câu hỏi
            if ($exam->shuffle_questions) {
                $selectedQuestions = $selectedQuestions->shuffle();
            }

            // Tạo map xáo trộn đáp án
            $optionShuffleMap = null;
            if ($exam->shuffle_options) {
                $optionShuffleMap = $this->generateOptionShuffleMap($selectedQuestions);
            }

            // Lưu mã đề
            $examCode = ExamCode::create([
                'exam_id' => $exam->id,
                'code' => $code,
                'question_order' => $selectedQuestions->pluck('id')->toArray(),
                'option_shuffle_map' => $optionShuffleMap,
            ]);

            $examCodes[] = $examCode;

            Log::info("Created exam code {$code} for exam {$exam->id} with {$selectedQuestions->count()} questions");
        }

        return $examCodes;
    }

    /**
     * Chọn câu hỏi phân bố đều qua các chương
     * 
     * @param int $questionBankId
     * @param Collection $chapters
     * @param array $difficultyConfig {"easy": 30, "medium": 20, "hard": 10}
     * @param int $totalQuestions
     * @return Collection
     */
    private function selectQuestionsBalanced(
        int $questionBankId,
        Collection $chapters,
        array $difficultyConfig,
        int $totalQuestions
    ): Collection {
        $chaptersCount = $chapters->count();
        $selectedQuestions = collect();
        $excludeIds = [];

        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            $targetCount = $difficultyConfig[$difficulty] ?? 0;
            
            if ($targetCount <= 0) {
                continue;
            }

            $questionsForDifficulty = collect();

            // 1. Phân bổ đều cho các chương
            $basePerChapter = (int) floor($targetCount / $chaptersCount);
            $remainder = $targetCount % $chaptersCount;

            // Random các index chương sẽ nhận phần dư
            $remainderIndexes = [];
            if ($remainder > 0 && $chaptersCount > 0) {
                $indexes = range(0, $chaptersCount - 1);
                shuffle($indexes);
                $remainderIndexes = array_slice($indexes, 0, $remainder);
            }

            $chapterIndex = 0;
            foreach ($chapters as $chapter) {
                $countForChapter = $basePerChapter;
                if (in_array($chapterIndex, $remainderIndexes)) {
                    $countForChapter++;
                }

                if ($countForChapter > 0) {
                    $questions = Question::where('question_bank_id', $questionBankId)
                        ->where('chapter_id', $chapter->id)
                        ->where('difficulty', $difficulty)
                        ->whereNotIn('id', $excludeIds)
                        ->inRandomOrder()
                        ->limit($countForChapter)
                        ->get();

                    $questionsForDifficulty = $questionsForDifficulty->merge($questions);
                    $excludeIds = array_merge($excludeIds, $questions->pluck('id')->toArray());
                }

                $chapterIndex++;
            }

            // 2. Nếu vẫn thiếu (do chương nào đó không đủ câu), lấy bù từ toàn bộ ngân hàng (ĐÚNG ĐỘ KHÓ)
            if ($questionsForDifficulty->count() < $targetCount) {
                $missingCount = $targetCount - $questionsForDifficulty->count();
                
                $additionalQuestions = Question::where('question_bank_id', $questionBankId)
                    ->where('difficulty', $difficulty)
                    ->whereNotIn('id', $excludeIds)
                    ->inRandomOrder()
                    ->limit($missingCount)
                    ->get();
                
                $questionsForDifficulty = $questionsForDifficulty->merge($additionalQuestions);
                $excludeIds = array_merge($excludeIds, $additionalQuestions->pluck('id')->toArray());
            }

            $selectedQuestions = $selectedQuestions->merge($questionsForDifficulty);
        }

        // Fallback cuối cùng nếu vẫn thiếu
        if ($selectedQuestions->count() < $totalQuestions) {
             $missing = $totalQuestions - $selectedQuestions->count();
             $fallback = Question::where('question_bank_id', $questionBankId)
                 ->whereNotIn('id', $excludeIds)
                 ->inRandomOrder()
                 ->limit($missing)
                 ->get();
             $selectedQuestions = $selectedQuestions->merge($fallback);
        }

        return $selectedQuestions->take($totalQuestions)->values();
    }

    /**
     * Tạo map xáo trộn đáp án cho mỗi câu hỏi
     * 
     * @param Collection $questions
     * @return array {"question_id": {"A": "C", "B": "A", "C": "D", "D": "B"}, ...}
     */
    private function generateOptionShuffleMap(Collection $questions): array
    {
        $shuffleMap = [];
        $optionKeys = ['A', 'B', 'C', 'D'];

        foreach ($questions as $question) {
            if ($question->type !== 'multiple_choice' || empty($question->options)) {
                continue;
            }

            // Tạo mapping ngẫu nhiên
            $shuffledKeys = $optionKeys;
            shuffle($shuffledKeys);

            $mapping = [];
            foreach ($optionKeys as $index => $newKey) {
                $mapping[$newKey] = $shuffledKeys[$index];
            }

            $shuffleMap[$question->id] = $mapping;
        }

        return $shuffleMap;
    }

    /**
     * Validate ngân hàng câu hỏi có đủ số lượng không
     * 
     * @param \Modules\Task\app\Models\QuestionBank $questionBank
     * @param array $difficultyConfig
     * @throws \Exception
     */
    private function validateQuestionPool($questionBank, array $difficultyConfig): void
    {
        $errors = [];

        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            $required = $difficultyConfig[$difficulty] ?? 0;
            
            if ($required > 0) {
                $available = Question::where('question_bank_id', $questionBank->id)
                    ->where('difficulty', $difficulty)
                    ->count();
                    
                if ($available < $required) {
                    $difficultyVi = match($difficulty) {
                        'easy' => 'Dễ',
                        'medium' => 'Trung bình',
                        'hard' => 'Khó',
                    };
                    $errors[] = "Thiếu câu hỏi mức {$difficultyVi}: cần {$required}, có {$available}";
                }
            }
        }

        if (!empty($errors)) {
            throw new \Exception(implode('. ', $errors));
        }
    }

    /**
     * Tính cấu hình độ khó mặc định theo tỉ lệ VN
     * 50% Dễ, 33% Trung bình, 17% Khó
     */
    private function calculateDefaultDifficultyConfig(int $totalQuestions): array
    {
        return Exam::calculateDifficultyConfig($totalQuestions);
    }

    /**
     * Gợi ý số câu hỏi dựa trên thời gian
     * 
     * @param int $timeLimit Thời gian (phút)
     * @return array Thông tin gợi ý
     */
    public function getSuggestedConfig(int $timeLimit): array
    {
        $totalQuestions = Exam::calculateSuggestedQuestions($timeLimit);
        $difficultyConfig = Exam::calculateDifficultyConfig($totalQuestions);

        return [
            'time_limit' => $timeLimit,
            'total_questions' => $totalQuestions,
            'difficulty_config' => $difficultyConfig,
            'minutes_per_question' => Exam::MINUTES_PER_QUESTION,
        ];
    }
}

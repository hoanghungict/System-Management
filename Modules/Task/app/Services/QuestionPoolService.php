<?php

namespace Modules\Task\app\Services;

use Illuminate\Support\Collection;
use Modules\Task\app\Models\Assignment;
use Modules\Task\app\Models\AssignmentSubmission;
use Modules\Task\app\Models\Question;
use Modules\Task\app\Models\SubmissionQuestion;
use Illuminate\Support\Facades\Log;

/**
 * QuestionPoolService
 * Xử lý logic random câu hỏi từ ngân hàng câu hỏi cho mỗi sinh viên
 */
class QuestionPoolService
{
    /**
     * Random và lưu câu hỏi cho submission
     * 
     * @param Assignment $assignment
     * @param AssignmentSubmission $submission
     * @return Collection Danh sách câu hỏi đã random
     */
    public function randomizeQuestionsForSubmission(Assignment $assignment, AssignmentSubmission $submission): Collection
    {
        $isPoolEnabled = filter_var($assignment->question_pool_enabled, FILTER_VALIDATE_BOOLEAN) || $assignment->question_pool_enabled === 1;
        
        // Nếu không bật question pool, bỏ qua
        if (!$isPoolEnabled || empty($assignment->question_pool_config)) {
            return collect();
        }

        // Kiểm tra nếu đã có câu hỏi rồi thì không random lại
        if ($submission->submissionQuestions()->exists()) {
            return $this->getQuestionsForSubmission($submission);
        }

        $config = $assignment->question_pool_config;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        Log::info("Question pool config for assignment {$assignment->id}:", (array)$config);

        $selectedQuestions = collect();

        // Random câu hỏi theo từng mức độ khó
        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            $count = $config[$difficulty] ?? 0;
            
            if ($count > 0) {
                $questions = Question::where('assignment_id', $assignment->id)
                    ->where('difficulty', $difficulty)
                    ->inRandomOrder()
                    ->limit($count)
                    ->get();
                    
                $selectedQuestions = $selectedQuestions->merge($questions);
            }
        }

        // Xáo trộn thứ tự nếu cần
        if ($assignment->shuffle_questions) {
            $selectedQuestions = $selectedQuestions->shuffle();
        }

        // Lưu vào submission_questions với thứ tự
        $orderIndex = 1;
        foreach ($selectedQuestions as $question) {
            SubmissionQuestion::create([
                'submission_id' => $submission->id,
                'question_id' => $question->id,
                'order_index' => $orderIndex++,
            ]);
        }
        
        // Refresh lại quan hệ
        $submission->load('submissionQuestions');

        Log::info("Created {$selectedQuestions->count()} submission questions for submission {$submission->id}");

        return $selectedQuestions;
    }

    /**
     * Lấy câu hỏi đã random cho submission (nếu có)
     * 
     * @param AssignmentSubmission $submission
     * @return Collection
     */
    public function getQuestionsForSubmission(AssignmentSubmission $submission): Collection
    {
        // Kiểm tra xem có dùng question pool không bằng cách query trực tiếp
        // Sử dụng ->get() thay vì property để luôn lấy dữ liệu mới nhất
        $submissionQuestions = $submission->submissionQuestions()
            ->with('question')
            ->orderBy('order_index')
            ->get();

        if ($submissionQuestions->isNotEmpty()) {
            // Có câu hỏi đã random, trả về theo thứ tự đã lưu
            return $submissionQuestions
                ->map(fn($sq) => $sq->question)
                ->filter() // Loại bỏ null nếu question bị xóa
                ->values(); // Reset keys
        }

        // Nếu assignment có bật question pool mà chưa có câu hỏi (lỗi?), 
        // và config không rỗng, thì có thể do chưa random xong.
        $isPoolEnabled = filter_var($submission->assignment->question_pool_enabled, FILTER_VALIDATE_BOOLEAN) || $submission->assignment->question_pool_enabled === 1;

        if ($isPoolEnabled) {
             // Nếu bật pool mà không tìm thấy câu hỏi -> có thể là lỗi hoặc config = 0
             // Trả về rỗng để tránh lộ đề (hiện tất cả câu hỏi)
             return collect();
        }

        // Không dùng question pool, trả về toàn bộ câu hỏi của assignment
        return $submission->assignment->questions()->orderBy('order_index')->get();
    }

    /**
     * Kiểm tra xem ngân hàng câu hỏi có đủ số lượng không
     * 
     * @param Assignment $assignment
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateQuestionPool(Assignment $assignment): array
    {
        if (!$assignment->question_pool_enabled || empty($assignment->question_pool_config)) {
            return ['valid' => true, 'errors' => []];
        }

        $config = $assignment->question_pool_config;
        $errors = [];

        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            $required = $config[$difficulty] ?? 0;
            
            if ($required > 0) {
                $available = Question::where('assignment_id', $assignment->id)
                    ->where('difficulty', $difficulty)
                    ->count();
                    
                if ($available < $required) {
                    $errors[] = "Thiếu câu hỏi mức {$difficulty}: cần {$required}, có {$available}";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Lấy thống kê số lượng câu hỏi theo độ khó
     * 
     * @param int $assignmentId
     * @return array
     */
    public function getQuestionStats(int $assignmentId): array
    {
        $stats = Question::where('assignment_id', $assignmentId)
            ->selectRaw('difficulty, COUNT(*) as count')
            ->groupBy('difficulty')
            ->pluck('count', 'difficulty')
            ->toArray();

        return [
            'easy' => $stats['easy'] ?? 0,
            'medium' => $stats['medium'] ?? 0,
            'hard' => $stats['hard'] ?? 0,
            'total' => array_sum($stats),
        ];
    }
}

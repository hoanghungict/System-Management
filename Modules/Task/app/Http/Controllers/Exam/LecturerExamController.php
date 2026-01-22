<?php

namespace Modules\Task\app\Http\Controllers\Exam;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Task\app\Models\Exam;
use Modules\Task\app\Models\QuestionBank;
use Modules\Task\app\Services\ExamCodeGeneratorService;

/**
 * LecturerExamController
 * CRUD đề thi và quản lý mã đề cho giảng viên
 */
class LecturerExamController extends Controller
{
    protected ExamCodeGeneratorService $examCodeService;

    public function __construct(ExamCodeGeneratorService $examCodeService)
    {
        $this->examCodeService = $examCodeService;
    }

    /**
     * Lấy danh sách đề thi của giảng viên
     */
    public function index(Request $request): JsonResponse
    {
        $lecturerId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        
        if (!$lecturerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $exams = Exam::byLecturer($lecturerId)
            ->with(['questionBank:id,name,subject_code', 'course:id,name,code'])
            ->withCount(['examCodes as actual_codes_count', 'submissions'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $exams
        ]);
    }

    /**
     * Tạo đề thi mới
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question_bank_id' => 'required|exists:question_banks,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'time_limit' => 'required|integer|min:10|max:180',
            'total_questions' => 'required|integer|min:5|max:200',
            'max_attempts' => 'nullable|integer|min:1|max:5',
            'difficulty_config' => 'nullable|array',
            'difficulty_config.easy' => 'nullable|integer|min:0',
            'difficulty_config.medium' => 'nullable|integer|min:0',
            'difficulty_config.hard' => 'nullable|integer|min:0',
            'exam_codes_count' => 'nullable|integer|min:1|max:10',
            'show_answers_after_submit' => 'nullable|boolean',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_options' => 'nullable|boolean',
            'anti_cheat_enabled' => 'nullable|boolean',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $lecturerId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        // Nếu không có difficulty_config, tính tự động
        $data = $validator->validated();
        if (empty($data['difficulty_config'])) {
            $data['difficulty_config'] = Exam::calculateDifficultyConfig($data['total_questions']);
        }

        $exam = Exam::create([
            ...$data,
            'lecturer_id' => $lecturerId,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đề thi được tạo thành công',
            'data' => $exam->load('questionBank')
        ], 201);
    }

    /**
     * Chi tiết đề thi
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $exam = Exam::with(['questionBank.chapters', 'examCodes', 'course'])
            ->withCount('submissions')
            ->find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đề thi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $exam
        ]);
    }

    /**
     * Cập nhật đề thi
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đề thi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Không cho sửa nếu đã publish và có submission
        if ($exam->status === 'published' && $exam->submissions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể sửa đề thi đã có sinh viên làm bài'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'time_limit' => 'sometimes|integer|min:10|max:180',
            'total_questions' => 'sometimes|integer|min:5|max:200',
            'max_attempts' => 'nullable|integer|min:1|max:5',
            'difficulty_config' => 'nullable|array',
            'exam_codes_count' => 'nullable|integer|min:1|max:10',
            'show_answers_after_submit' => 'nullable|boolean',
            'anti_cheat_enabled' => 'nullable|boolean',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $exam->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật đề thi thành công',
            'data' => $exam->fresh()
        ]);
    }

    /**
     * Xóa đề thi
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đề thi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $exam->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa đề thi thành công'
        ]);
    }

    /**
     * Tạo mã đề
     */
    public function generateCodes(Request $request, int $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đề thi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Xóa mã đề cũ nếu có
        $exam->examCodes()->delete();

        try {
            $numberOfCodes = $request->get('count', $exam->exam_codes_count ?? 4);
            
            \Illuminate\Support\Facades\Log::info("GenerateCodes: Starting for exam {$id}, count: {$numberOfCodes}");
            
            $examCodes = $this->examCodeService->generateExamCodes($exam, $numberOfCodes);
            
            $actualCount = count($examCodes);
            \Illuminate\Support\Facades\Log::info("GenerateCodes: Created {$actualCount} codes for exam {$id}");
            
            if ($actualCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo mã đề. Vui lòng kiểm tra ngân hàng câu hỏi.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã tạo {$actualCount} mã đề thành công",
                'data' => $examCodes
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Generate Code Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Publish đề thi
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đề thi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Kiểm tra đã có mã đề chưa
        if ($exam->examCodes()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng tạo mã đề trước khi publish'
            ], 422);
        }

        $exam->publish();

        return response()->json([
            'success' => true,
            'message' => 'Đề thi đã được publish',
            'data' => $exam
        ]);
    }

    /**
     * Đóng đề thi
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đề thi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $exam->close();

        return response()->json([
            'success' => true,
            'message' => 'Đề thi đã được đóng',
            'data' => $exam
        ]);
    }

    /**
     * Lấy gợi ý cấu hình dựa trên thời gian
     */
    public function getSuggestedConfig(Request $request): JsonResponse
    {
        $timeLimit = $request->get('time_limit', 90);
        $config = $this->examCodeService->getSuggestedConfig($timeLimit);

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    /**
     * Lấy danh sách bài làm của đề thi
     */
    public function getSubmissions(Request $request, int $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đề thi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $submissions = $exam->submissions()
            ->with(['student:id,full_name,student_code', 'examCode:id,code', 'grader:id,full_name'])
            ->orderBy('submitted_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $submissions
        ]);
    }

    /**
     * Xem chi tiết bài làm
     */
    public function getSubmission(Request $request, int $submissionId): JsonResponse
    {
        $submission = \Modules\Task\app\Models\ExamSubmission::with([
            'exam.questionBank',
            'examCode',
            'student:id,full_name,student_code',
            'grader:id,full_name'
        ])->find($submissionId);

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bài làm'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($submission->exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Lấy thêm chi tiết câu hỏi và câu trả lời
        $questions = $submission->examCode->getOrderedQuestions();
        $answers = $submission->answers ?? [];

        $questionDetails = $questions->map(function ($q) use ($answers, $submission) {
            $studentAnswer = $answers[$q->id] ?? null;
            $originalAnswer = $studentAnswer 
                ? $submission->examCode->convertToOriginalAnswer($q->id, $studentAnswer)
                : null;
            
            return [
                'id' => $q->id,
                'content' => $q->content,
                'options' => $q->options,
                'correct_answer' => $q->correct_answer,
                'student_answer' => $studentAnswer,
                'original_answer' => $originalAnswer,
                'is_correct' => $originalAnswer ? $q->isAnswerCorrect($originalAnswer) : false,
                'difficulty' => $q->difficulty,
                'chapter' => $q->chapter?->name,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'submission' => $submission,
                'question_details' => $questionDetails,
            ]
        ]);
    }

    /**
     * Sửa điểm bài làm
     */
    public function gradeSubmission(Request $request, int $submissionId): JsonResponse
    {
        $submission = \Modules\Task\app\Models\ExamSubmission::with('exam')->find($submissionId);

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bài làm'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($submission->exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'manual_score' => 'required|numeric|min:0|max:10',
            'grader_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $submission->gradeManually(
            $userId,
            $request->manual_score,
            $request->grader_note
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật điểm',
            'data' => $submission->fresh()
        ]);
    }
    /**
     * Lấy danh sách mã đề và chi tiết câu hỏi
     */
    public function getExamCodes(Request $request, int $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đề thi'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($exam->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // DEBUG: Log exam codes count
        $relationshipCount = $exam->examCodes()->count();
        $rawCount = \DB::table('exam_codes')->where('exam_id', $id)->count();
        \Illuminate\Support\Facades\Log::info("getExamCodes: Exam {$id} - Relationship count: {$relationshipCount}, Raw count: {$rawCount}");
        
        // DEBUG: Return raw data if relationship is empty but raw has data
        if ($relationshipCount === 0 && $rawCount > 0) {
            \Illuminate\Support\Facades\Log::error("getExamCodes: Mismatch! Relationship returns 0 but raw query returns {$rawCount}");
        }
        
        // Use direct query instead of relationship to avoid potential caching/connection issues
        $rawExamCodes = \Modules\Task\app\Models\ExamCode::where('exam_id', $id)->get();
        \Illuminate\Support\Facades\Log::info("getExamCodes: Got {$rawExamCodes->count()} raw codes");
        
        $examCodes = $rawExamCodes->map(function ($code) {
            try {
                $questions = $code->getOrderedQuestions();
                \Illuminate\Support\Facades\Log::info("getExamCodes: Code {$code->code} has {$questions->count()} questions");
                
                return [
                    'id' => $code->id,
                    'code' => $code->code,
                    'questions' => $questions->map(function($q) use ($code) {
                        return [
                            'id' => $q->id,
                            'content' => $q->content,
                            'type' => $q->type,
                            'chapter_id' => $q->chapter_id, // Added ID
                            'chapter_name' => $q->chapter->name ?? null,
                            'difficulty' => $q->difficulty,
                            'options' => $code->getShuffledOptions($q->id, $q->options ?? []),
                            'correct_answer' => $q->correct_answer, // Original correct answer (e.g. "A")
                            'correct_key' => $code->option_shuffle_map[$q->id] 
                                ? array_search($q->correct_answer, $code->option_shuffle_map[$q->id]) // Find new key that points to old correct key
                                : $q->correct_answer, // No shuffle
                        ];
                    })->values()->toArray(),
                ];
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("getExamCodes: Error processing code {$code->id}: " . $e->getMessage());
                return null;
            }
        })->filter()->values();

        // Thống kê capacity của ngân hàng để hiển thị
        $bankStats = \Modules\Task\app\Models\Question::where('question_bank_id', $exam->question_bank_id)
            ->selectRaw('chapter_id, difficulty, count(*) as total')
            ->groupBy('chapter_id', 'difficulty')
            ->get()
            ->groupBy('chapter_id') // Group by ID for robustness
            ->map(function ($items) {
                $stats = $items->pluck('total', 'difficulty')->toArray();
                $stats['total'] = $items->sum('total');
                // Add name for fallback mapping
                $stats['name'] = $items->first()->chapter->name ?? 'Unknown';
                return $stats;
            });

        return response()->json([
            'success' => true,
            'data' => $examCodes->toArray(),
            'bank_stats' => $bankStats
        ]);
    }
}

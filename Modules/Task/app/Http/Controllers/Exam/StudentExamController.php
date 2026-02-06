<?php

namespace Modules\Task\app\Http\Controllers\Exam;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Task\app\Models\Exam;
use Modules\Task\app\Models\ExamCode;
use Modules\Task\app\Models\ExamSubmission;
use Modules\Task\app\Services\AntiCheatService;
use Illuminate\Support\Facades\DB;

/**
 * StudentExamController
 * Làm bài thi cho sinh viên
 */
class StudentExamController extends Controller
{
    protected AntiCheatService $antiCheatService;

    public function __construct(AntiCheatService $antiCheatService)
    {
        $this->antiCheatService = $antiCheatService;
    }

    /**
     * Lấy danh sách đề thi có thể làm
     * - Đề thi không có course_id (public) = hiển thị cho tất cả sinh viên
     * - Đề thi có course_id = chỉ hiển thị cho sinh viên đã đăng ký môn đó
     */
    public function index(Request $request): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        // Lấy danh sách course_id mà sinh viên đã đăng ký (status = active)
        $enrolledCourseIds = DB::table('course_enrollments')
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->pluck('course_id')
            ->toArray();

        // Lấy các đề thi đang active:
        // 1. Đề thi không có course_id (public) - hiển thị cho tất cả
        // 2. Đề thi có course_id thuộc môn đã đăng ký
        $exams = Exam::active()
            ->where(function($query) use ($enrolledCourseIds) {
                $query->whereNull('course_id') // Public exams
                    ->orWhereIn('course_id', $enrolledCourseIds); // Enrolled course exams
            })
            ->with(['course:id,name,code', 'lecturer:id,full_name'])
            ->withCount(['submissions as my_attempts' => function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            }])
            ->orderBy('end_time', 'asc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $exams
        ]);
    }

    /**
     * Chi tiết đề thi (trước khi bắt đầu)
     * Kiểm tra sinh viên đã đăng ký môn học
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        $exam = Exam::active()
            ->with(['course:id,name,code', 'lecturer:id,full_name'])
            ->find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Đề thi không tồn tại hoặc chưa mở'], 404);
        }

        // Kiểm tra sinh viên có đăng ký môn học này không (chỉ khi exam có course_id)
        if ($exam->course_id) {
            $isEnrolled = DB::table('course_enrollments')
                ->where('student_id', $studentId)
                ->where('course_id', $exam->course_id)
                ->where('status', 'active')
                ->exists();

            if (!$isEnrolled) {
                return response()->json(['success' => false, 'message' => 'Bạn chưa đăng ký môn học này'], 403);
            }
        }

        // Lấy các lần làm bài của sinh viên
        $mySubmissions = ExamSubmission::where('exam_id', $id)
            ->where('student_id', $studentId)
            ->with('examCode:id,code')
            ->get();

        // Kiểm tra còn lượt làm không
        $submittedCount = $mySubmissions->whereIn('status', ['submitted', 'graded'])->count();
        $canAttempt = $submittedCount < $exam->max_attempts;

        // Kiểm tra có đang làm dở không
        $inProgressSubmission = $mySubmissions->firstWhere('status', 'in_progress');

        return response()->json([
            'success' => true,
            'data' => [
                'exam' => $exam,
                'my_submissions' => $mySubmissions,
                'submitted_count' => $submittedCount,
                'can_attempt' => $canAttempt,
                'in_progress_submission' => $inProgressSubmission,
            ]
        ]);
    }

    /**
     * Bắt đầu làm bài thi
     * Kiểm tra sinh viên đã đăng ký môn học
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        $exam = Exam::active()->find($id);

        if (!$exam) {
            return response()->json(['success' => false, 'message' => 'Đề thi không tồn tại hoặc chưa mở'], 404);
        }

        // Kiểm tra sinh viên có đăng ký môn học này không (chỉ khi exam có course_id)
        if ($exam->course_id) {
            $isEnrolled = DB::table('course_enrollments')
                ->where('student_id', $studentId)
                ->where('course_id', $exam->course_id)
                ->where('status', 'active')
                ->exists();

            if (!$isEnrolled) {
                return response()->json(['success' => false, 'message' => 'Bạn chưa đăng ký môn học này'], 403);
            }
        }

        // Kiểm tra có đang làm dở không
        $inProgressSubmission = ExamSubmission::where('exam_id', $id)
            ->where('student_id', $studentId)
            ->inProgress()
            ->first();

        if ($inProgressSubmission) {
            // Tiếp tục bài làm cũ
            return $this->returnSubmissionWithQuestions($inProgressSubmission, 'Tiếp tục bài làm');
        }

        // Kiểm tra còn lượt làm không
        $submittedCount = ExamSubmission::where('exam_id', $id)
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if ($submittedCount >= $exam->max_attempts) {
            return response()->json([
                'success' => false,
                'message' => "Bạn đã hết {$exam->max_attempts} lượt làm bài"
            ], 422);
        }

        try {
            return DB::transaction(function () use ($exam, $studentId, $submittedCount) {
                // Random mã đề cho sinh viên
                $examCode = $exam->examCodes()->inRandomOrder()->first();

                if (!$examCode) {
                    throw new \Exception('Đề thi chưa có mã đề');
                }

                // Tạo submission mới
                $submission = ExamSubmission::create([
                    'exam_id' => $exam->id,
                    'exam_code_id' => $examCode->id,
                    'student_id' => $studentId,
                    'attempt' => $submittedCount + 1,
                    'started_at' => now(),
                    'status' => 'in_progress',
                ]);

                // Dispatch realtime event
                \Modules\Task\app\Events\ExamSubmissionCreated::dispatch($submission);

                return $this->returnSubmissionWithQuestions($submission, 'Bắt đầu làm bài');
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lưu câu trả lời (auto-save)
     */
    public function saveAnswer(Request $request, int $submissionId): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        $submission = ExamSubmission::where('id', $submissionId)
            ->where('student_id', $studentId)
            ->inProgress()
            ->first();

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Bài làm không tồn tại'], 404);
        }

        // Kiểm tra hết thời gian chưa
        if ($submission->is_time_limit_exceeded) {
            // Tự động nộp bài
            $submission->submit();
            return response()->json([
                'success' => false,
                'message' => 'Hết thời gian, bài đã được tự động nộp',
                'auto_submitted' => true
            ], 422);
        }

        $answers = $submission->answers ?? [];
        $answers[$request->question_id] = $request->answer;
        $submission->answers = $answers;
        $submission->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu câu trả lời'
        ]);
    }

    /**
     * Nộp bài thi
     */
    public function submit(Request $request, int $submissionId): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        $submission = ExamSubmission::where('id', $submissionId)
            ->where('student_id', $studentId)
            ->inProgress()
            ->with('exam')
            ->first();

        // Nếu không tìm thấy bài đang làm, kiểm tra xem đã nộp chưa
        if (!$submission) {
            $submittedSubmission = ExamSubmission::where('id', $submissionId)
                ->where('student_id', $studentId)
                ->whereIn('status', ['submitted', 'graded'])
                ->with('exam')
                ->first();
            
            if ($submittedSubmission) {
                 // Nếu đã nộp rồi, trả về kết quả luôn
                 if ($submittedSubmission->exam->show_answers_after_submit) {
                    return $this->getResult($request, $submissionId);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Bài thi đã được nộp trước đó',
                    'data' => [
                        'total_score' => $submittedSubmission->total_score,
                        'correct_count' => $submittedSubmission->correct_count,
                        'wrong_count' => $submittedSubmission->wrong_count,
                        'unanswered_count' => $submittedSubmission->unanswered_count,
                    ]
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Bài làm không tồn tại hoặc đã kết thúc'], 404);
        }

        // Lưu câu trả lời cuối cùng nếu có
        if ($request->has('answers')) {
            $answers = $submission->answers ?? [];
            foreach ($request->answers as $questionId => $answer) {
                $answers[$questionId] = $answer;
            }
            $submission->answers = $answers;
        }

        // Nộp bài
        $submission->submit();

        // Nếu cho xem đáp án ngay
        if ($submission->exam->show_answers_after_submit) {
            return $this->getResult($request, $submissionId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nộp bài thành công',
            'data' => [
                'total_score' => $submission->total_score,
                'correct_count' => $submission->correct_count,
                'wrong_count' => $submission->wrong_count,
                'unanswered_count' => $submission->unanswered_count,
            ]
        ]);
    }

    /**
     * Xem kết quả chi tiết
     */
    public function getResult(Request $request, int $submissionId): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        $submission = ExamSubmission::where('id', $submissionId)
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'graded'])
            ->with(['exam', 'examCode'])
            ->first();

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy kết quả'], 404);
        }

        // Nếu không cho xem đáp án
        if (!$submission->exam->show_answers_after_submit) {
            return response()->json([
                'success' => true,
                'data' => [
                    'submission' => $submission,
                    'show_answers' => false,
                ]
            ]);
        }

        // Lấy chi tiết câu hỏi và đáp án
        $questions = $submission->examCode->getOrderedQuestions();
        $answers = $submission->answers ?? [];

        $questionDetails = $questions->map(function ($q) use ($answers, $submission) {
            $studentAnswer = $answers[$q->id] ?? null;
            $originalAnswer = $studentAnswer 
                ? $submission->examCode->convertToOriginalAnswer($q->id, $studentAnswer)
                : null;
            
            // Lấy options đã shuffle
            $shuffledOptions = $submission->examCode->getShuffledOptions($q->id, $q->options ?? []);
            
            // Tìm đáp án đúng sau khi shuffle
            $correctAnswerAfterShuffle = null;
            $shuffleMap = $submission->examCode->option_shuffle_map[$q->id] ?? null;
            if ($shuffleMap) {
                $correctAnswerAfterShuffle = array_search($q->correct_answer, $shuffleMap);
            } else {
                $correctAnswerAfterShuffle = $q->correct_answer;
            }

            return [
                'id' => $q->id,
                'content' => $q->content,
                'options' => $shuffledOptions,
                'student_answer' => $studentAnswer,
                'correct_answer' => $correctAnswerAfterShuffle,
                'is_correct' => $originalAnswer ? $q->isAnswerCorrect($originalAnswer) : false,
                'explanation' => $q->explanation,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'submission' => $submission,
                'show_answers' => true,
                'question_details' => $questionDetails,
            ]
        ]);
    }

    /**
     * Log vi phạm anti-cheat
     */
    public function logViolation(Request $request, int $submissionId): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        $submission = ExamSubmission::where('id', $submissionId)
            ->where('student_id', $studentId)
            ->inProgress()
            ->first();

        if (!$submission) {
            return response()->json(['success' => false], 404);
        }

        // Chỉ log nếu anti-cheat được bật
        if (!$submission->exam->anti_cheat_enabled) {
            return response()->json(['success' => true]);
        }

        $this->antiCheatService->logViolation(
            $submission,
            $request->type,
            $request->details
        );

        return response()->json(['success' => true]);
    }

    /**
     * Helper: Trả về submission với câu hỏi
     */
    private function returnSubmissionWithQuestions(ExamSubmission $submission, string $message): JsonResponse
    {
        $submission->load(['exam', 'examCode']);
        
        // Lấy câu hỏi theo thứ tự của mã đề
        $questions = $submission->examCode->getOrderedQuestions();

        // Áp dụng shuffle options nếu có
        $questionsWithShuffledOptions = $questions->map(function ($q) use ($submission) {
            $shuffledOptions = $submission->examCode->getShuffledOptions($q->id, $q->options ?? []);
            
            return [
                'id' => $q->id,
                'content' => $q->content,
                'type' => $q->type,
                'options' => $shuffledOptions,
                // Không trả về correct_answer và explanation
            ];
        });

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'submission' => [
                    'id' => $submission->id,
                    'exam_id' => $submission->exam_id,
                    'exam_code' => $submission->examCode->code,
                    'attempt' => $submission->attempt,
                    'started_at' => $submission->started_at,
                    'remaining_time' => $submission->remaining_time,
                    'answers' => $submission->answers ?? [],
                    'status' => $submission->status,
                ],
                'exam' => [
                    'id' => $submission->exam->id,
                    'title' => $submission->exam->title,
                    'time_limit' => $submission->exam->time_limit,
                    'total_questions' => $submission->exam->total_questions,
                    'anti_cheat_enabled' => $submission->exam->anti_cheat_enabled,
                ],
                'questions' => $questionsWithShuffledOptions,
            ]
        ]);
    }
}

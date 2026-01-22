<?php

namespace Modules\Task\app\Http\Controllers\Assignment;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Task\app\Models\Assignment;
use Modules\Task\app\Models\AssignmentSubmission;
use Modules\Task\app\Services\QuestionPoolService;
use Illuminate\Support\Facades\DB;

/**
 * StudentAssignmentController
 * View assignments and handle submission start
 */
class StudentAssignmentController extends Controller
{
    /**
     * List available assignments for student
     */
    public function index(Request $request): JsonResponse
    {
        // Fix: Use jwt_user_id from attributes
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        
        // Logic lấy assignment dựa trên enrollment của student vào course
        // Hiện tại lấy tất cả published assignment của các course mà student enrolled
        
        $assignments = Assignment::published()
            ->whereHas('course.enrollments', function($q) use ($studentId) {
                // Giả sử có relationship enrollments trong Course và student_id trong enrollment
                $q->where('student_id', $studentId);
            })
            ->with(['course:id,name,code', 'lecturer:id,full_name'])
            ->with(['submissions' => function($q) use ($studentId) {
                $q->where('student_id', $studentId)
                  ->select('id', 'assignment_id', 'status', 'total_score', 'attempt', 'submitted_at');
            }])
            ->orderBy('deadline', 'asc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Get assignment detail
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        $assignment = Assignment::published()
            ->with(['course:id,name,code', 'lecturer:id,full_name', 'questions'])
            ->find($id);
        
        if ($assignment) {
            $assignment->questions->makeHidden(['correct_answer', 'explanation']);
        }

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found or not available'], 404);
        }

        // Check if student is enrolled in course (Optional but recommended)
        $isEnrolled = DB::table('course_enrollments')
            ->where('course_id', $assignment->course_id)
            ->where('student_id', $studentId)
            ->exists();

        // if (!$isEnrolled) {
        //     return response()->json(['success' => false, 'message' => 'You are not enrolled in this course'], 403);
        // }

        // Get student's submissions for this assignment
        $mySubmissions = AssignmentSubmission::where('assignment_id', $id)
            ->where('student_id', $studentId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignment,
            'my_submissions' => $mySubmissions
        ]);
    }

    /**
     * Start an assignment attempt
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        
        \Illuminate\Support\Facades\Log::info("Start attempt - StudentId: {$studentId}, AssignmentId: {$id}");
        
        $assignment = Assignment::published()->where('id', $id)->first();

        if (!$assignment) {
            // Debug: check if assignment exists at all
            $anyAssignment = Assignment::find($id);
            \Illuminate\Support\Facades\Log::warning("Assignment not found or not published", [
                'id' => $id,
                'exists' => $anyAssignment !== null,
                'status' => $anyAssignment ? $anyAssignment->status : 'N/A'
            ]);
            return response()->json(['success' => false, 'message' => 'Assignment not found or not available'], 404);
        }
        
        \Illuminate\Support\Facades\Log::info("Assignment found", ['id' => $id, 'status' => $assignment->status]);

        // Check enrollment (Optional)
        // ...

        // Check deadline
        if ($assignment->is_expired) {
            return response()->json(['success' => false, 'message' => 'Assignment deadline has passed'], 422);
        }

        // Check attempts - CHỈ đếm những submission đã nộp (submitted/graded) để validate max_attempts
        // Check attempts - CHỈ đếm những submission đã nộp (submitted/graded) để validate max_attempts
        $submittedAttemptsCount = AssignmentSubmission::withTrashed()
            ->where('assignment_id', $id)
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'graded']) // Chỉ đếm đã nộp
            ->count();
            
        if ($assignment->max_attempts && $submittedAttemptsCount >= $assignment->max_attempts) {
            return response()->json(['success' => false, 'message' => 'Đã đạt số lần nộp bài tối đa'], 422);
        }
        
        // Check if there is an in-progress submission to RESUME
        $existingSubmission = AssignmentSubmission::where('assignment_id', $id)
            ->where('student_id', $studentId)
            ->inProgress()
            ->first();
            
        if ($existingSubmission) {
            // Lấy câu hỏi đã random cho submission (nếu dùng question pool)
            $questionPoolService = new QuestionPoolService();
            $questions = $questionPoolService->getQuestionsForSubmission($existingSubmission);
            // Hide sensitive fields manually
            $questions->each(function ($q) {
                if ($q instanceof \Modules\Task\app\Models\Question || $q instanceof \Illuminate\Database\Eloquent\Model) {
                    $q->makeHidden(['correct_answer', 'explanation', 'rubric']);
                }
            });
            $existingSubmission->setRelation('questions', $questions);
            $existingSubmission->remaining_time = $existingSubmission->remaining_time;
            
            return response()->json([
                'success' => true,
                'message' => 'Continuing existing attempt',
                'data' => $existingSubmission
            ]);
        }

        // Simple approach: try to create, catch duplicate error
        try {
            // Get max attempt number
            $maxAttempt = AssignmentSubmission::withTrashed()
                ->where('assignment_id', $id)
                ->where('student_id', $studentId)
                ->max('attempt');
                
            $nextAttempt = ($maxAttempt ?? 0) + 1;

            // Create new Submission
            $submission = AssignmentSubmission::create([
                'assignment_id' => $id,
                'student_id' => $studentId,
                'attempt' => $nextAttempt,
                'started_at' => now(),
                'status' => 'in_progress'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // If duplicate entry error, find and return existing submission
            if ($e->getCode() === '23000') {
                $existingSubmission = AssignmentSubmission::where('assignment_id', $id)
                    ->where('student_id', $studentId)
                    ->inProgress()
                    ->first();
                    
                if ($existingSubmission) {
                    // Lấy câu hỏi đã random cho submission cũ
                    $questionPoolService = new QuestionPoolService();
                    $questions = $questionPoolService->getQuestionsForSubmission($existingSubmission);
                    // Hide sensitive fields manually
                    $questions->each(function ($q) {
                        if ($q instanceof \Modules\Task\app\Models\Question || $q instanceof \Illuminate\Database\Eloquent\Model) {
                            $q->makeHidden(['correct_answer', 'explanation', 'rubric']);
                        }
                    });
                    $existingSubmission->setRelation('questions', $questions);
                    $existingSubmission->remaining_time = $existingSubmission->remaining_time;
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Continuing existing attempt',
                        'data' => $existingSubmission
                    ]);
                }
            }
            
            throw $e;
        }

        // Random câu hỏi nếu bật question pool, hoặc lấy toàn bộ
        $questionPoolService = new QuestionPoolService();
        
        \Illuminate\Support\Facades\Log::info("Start Assignment Debug ID: {$assignment->id}");
        \Illuminate\Support\Facades\Log::info("Pool Enabled (Raw): " . $assignment->question_pool_enabled);
        
        $isPoolEnabled = filter_var($assignment->question_pool_enabled, FILTER_VALIDATE_BOOLEAN) || $assignment->question_pool_enabled === 1;
        
        \Illuminate\Support\Facades\Log::info("Pool Enabled (Checked): " . ($isPoolEnabled ? 'YES' : 'NO'));
        \Illuminate\Support\Facades\Log::info("Pool Config: " . json_encode($assignment->question_pool_config));
        
        if ($isPoolEnabled) {
            // Random và lưu câu hỏi cho submission mới
            $questions = $questionPoolService->randomizeQuestionsForSubmission($assignment, $submission);
        } else {
            // Không dùng question pool, lấy toàn bộ câu hỏi
            $questions = $assignment->questions;
        }
        
        // Hide sensitive fields manually
        $questions->each(function ($q) {
            if ($q instanceof \Modules\Task\app\Models\Question || $q instanceof \Illuminate\Database\Eloquent\Model) {
                $q->makeHidden(['correct_answer', 'explanation', 'rubric']);
            } else {
                \Illuminate\Support\Facades\Log::warning("Item in questions collection is not a Model: " . gettype($q));
            }
        });

        // Append extra data for frontend convenience
        $submission->setRelation('questions', $questions);
        $submission->remaining_time = $submission->remaining_time;

        return response()->json([
            'success' => true,
            'message' => 'Assignment started',
            'data' => $submission
        ]);
    }

    /**
     * Submit assignment answers
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        
        $request->validate([
            'submission_id' => 'required|exists:assignment_submissions,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_text' => 'nullable|string'
        ]);

        $submission = AssignmentSubmission::find($request->submission_id);

        if ($submission->assignment_id !== $id || $submission->student_id !== $studentId) {
            return response()->json(['success' => false, 'message' => 'Invalid submission'], 403);
        }

        if ($submission->status !== 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Submission already submitted'], 422);
        }

        try {
            // Save answers
            foreach ($request->answers as $answerData) {
                $submission->answers()->updateOrCreate(
                    ['question_id' => $answerData['question_id']],
                    [
                        'answer_text' => $answerData['answer_text'] ?? null,
                        'file_path' => $answerData['file_path'] ?? null
                    ]
                );
            }

            // Finalize submission (updates status and logs audit)
            $submission->submit();

            // Trigger Auto Grading
            // Use fully qualified class name or import. Checked and it is correct.
            \Modules\Task\app\Jobs\AutoGradeSubmissionJob::dispatch($submission->id);

            return response()->json([
                'success' => true,
                'message' => 'Assignment submitted successfully. Validating results...',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Submission failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error submitting assignment: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get submission result
     */
    public function getResult(Request $request, int $submissionId): JsonResponse
    {
        $studentId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        
        $submission = AssignmentSubmission::with(['assignment.questions', 'answers.question'])
            ->find($submissionId);

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Submission not found'], 404);
        }

        if ($submission->student_id !== $studentId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Logic check show_answers
        $showAnswers = $submission->assignment->show_answers;
        
        // If assignment is not closed and show_answers is false, maybe hide?
        // Implementation plan says "Show results + answers" based on config.
        
        if ($showAnswers) {
            // Load correct answers for questions
            $submission->assignment->questions->makeVisible(['correct_answer', 'explanation']);
            $submission->answers->each(function($answer) {
                if ($answer->question) {
                    $answer->question->makeVisible(['correct_answer', 'explanation']);
                }
            });
        } else {
            // Hide correct answers
            $submission->assignment->questions->makeHidden(['correct_answer', 'explanation']);
            
            // Also hide for answers relationship
            $submission->answers->each(function($answer) {
                if ($answer->question) {
                    $answer->question->makeHidden(['correct_answer', 'explanation']);
                }
            });
        }

        return response()->json([
            'success' => true,
            'data' => $submission
        ]);
    }
    /**
     * Upload file for essay question
     */
    public function uploadFile(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // Max 10MB
            ]);

            if ($request->hasFile('file')) {
                // Store file synchronously (required for upload)
                $path = $request->file('file')->store('assignments/submissions', 'public');
                
                // Dispatch Job to Queue for Audit Logging and Async Processing
                \Modules\Task\app\Jobs\ProcessFileUploadLog::dispatch(
                    $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : 0),
                    $path,
                    'success'
                );
                
                return response()->json([
                    'success' => true,
                    'url' => asset('storage/' . $path),
                    'path' => $path
                ]);
            }

            throw new \Exception('No file uploaded');

        } catch (\Throwable $e) {
            // Dispatch Job to Queue for Failure Logging
            \Modules\Task\app\Jobs\ProcessFileUploadLog::dispatch(
                $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : 0),
                'unknown',
                'failed',
                $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
}

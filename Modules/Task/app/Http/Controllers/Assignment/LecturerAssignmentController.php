<?php

namespace Modules\Task\app\Http\Controllers\Assignment;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Task\app\Models\Assignment;
use Modules\Task\app\Models\Question;
use Modules\Auth\app\Models\AuditLog;
use Illuminate\Support\Facades\Validator;

/**
 * LecturerAssignmentController
 * CRUD operations for assignments by lecturers
 */
class LecturerAssignmentController extends Controller
{
    /**
     * Get all assignments for lecturer
     */
    public function index(Request $request): JsonResponse
    {
        // Fix: Use jwt_user_id from attributes
        $lecturerId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        
        if (!$lecturerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        $assignments = Assignment::byLecturer($lecturerId)
            ->with(['questions:id,assignment_id,type,points'])
            ->withCount('submissions')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Create new assignment
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'type' => 'required|in:quiz,essay,mixed',
            'deadline' => 'nullable|date|after:now',
            'time_limit' => 'nullable|integer|min:1',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'show_answers' => 'nullable|boolean',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_options' => 'nullable|boolean',
            'question_pool_enabled' => 'nullable|boolean',
            'question_pool_config' => 'nullable|array',
            'question_pool_config.easy' => 'nullable|integer|min:0',
            'question_pool_config.medium' => 'nullable|integer|min:0',
            'question_pool_config.hard' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $assignment = Assignment::create([
            ...$validator->validated(),
            'lecturer_id' => $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null),
            'status' => 'draft',
        ]);

        AuditLog::log(
            'create_assignment',
            $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null),
            'assignment',
            $assignment->id,
            ['title' => $assignment->title]
        );

        return response()->json([
            'success' => true,
            'message' => 'Assignment created successfully',
            'data' => $assignment->load('questions')
        ], 201);
    }

    /**
     * Get single assignment detail
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::with(['questions', 'importLogs', 'extensionRequests.student'])
            ->withCount('submissions')
            ->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }

        // Check ownership
        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($assignment->lecturer_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $assignment
        ]);
    }

    /**
     * Update assignment
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($assignment->lecturer_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'type' => 'sometimes|in:quiz,essay,mixed',
            'deadline' => 'nullable|date',
            'time_limit' => 'nullable|integer|min:1',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'show_answers' => 'nullable|boolean',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_options' => 'nullable|boolean',
            'question_pool_enabled' => 'nullable|boolean',
            'question_pool_config' => 'nullable|array',
            'question_pool_config.easy' => 'nullable|integer|min:0',
            'question_pool_config.medium' => 'nullable|integer|min:0',
            'question_pool_config.hard' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $assignment->update($validator->validated());

        AuditLog::log(
            'update_assignment',
            $userId,
            'assignment',
            $assignment->id,
            ['changes' => $assignment->getChanges()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully',
            'data' => $assignment->fresh()->load('questions')
        ]);
    }

    /**
     * Delete assignment
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($assignment->lecturer_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        AuditLog::log(
            'delete_assignment',
            $userId,
            'assignment',
            $assignment->id,
            ['title' => $assignment->title]
        );

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully'
        ]);
    }

    /**
     * Publish assignment
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($assignment->lecturer_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if has questions
        if ($assignment->questions()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot publish assignment without questions'
            ], 422);
        }

        $assignment->publish();

        return response()->json([
            'success' => true,
            'message' => 'Assignment published successfully',
            'data' => $assignment
        ]);
    }

    /**
     * Close assignment
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($assignment->lecturer_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $assignment->close();

        return response()->json([
            'success' => true,
            'message' => 'Assignment closed successfully',
            'data' => $assignment
        ]);
    }
    /**
     * Get all submissions for an assignment
     */
    public function getSubmissions(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($assignment->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $submissions = \Modules\Task\app\Models\AssignmentSubmission::byAssignment($id)
            ->with(['student:id,full_name,student_code', 'grader:id,full_name'])
            ->orderBy('submitted_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $submissions
        ]);
    }

    /**
     * Get single submission detail with answers
     */
    public function getSubmission(Request $request, int $submissionId): JsonResponse
    {
        $submission = \Modules\Task\app\Models\AssignmentSubmission::with([
                'assignment.questions', 
                'student:id,full_name,student_code',
                'answers.question', 
                'grader:id,full_name'
            ])
            ->find($submissionId);

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Submission not found'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($submission->assignment->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $submission
        ]);
    }

    /**
     * Grade a submission (Manual override or complete grading)
     */
    public function gradeSubmission(Request $request, int $submissionId): JsonResponse
    {
        $submission = \Modules\Task\app\Models\AssignmentSubmission::with('assignment')->find($submissionId);

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Submission not found'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($submission->assignment->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Validate payload: can contain overall feedback, and per-answer grading OR a total override
        $validator = Validator::make($request->all(), [
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.score' => 'required|numeric|min:0',
            'answers.*.feedback' => 'nullable|string',
            'general_feedback' => 'nullable|string',
            'manual_score' => 'nullable|numeric|min:0|max:10', // Acts as Total Score override from FE
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $lecturerId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);

        // CASE 1: Grading individual answers (Detailed grading)
        if ($request->has('answers') && !empty($request->answers)) {
            foreach ($request->answers as $gradeData) {
                $answer = $submission->answers()->where('question_id', $gradeData['question_id'])->first();
                if ($answer) {
                    $answer->gradeManually($gradeData['score'], $gradeData['feedback'] ?? null);
                }
            }
            
            // Recalculate based on sum of all answers
            $submission->load(['answers.question']);
            $totalScore = $submission->answers->sum('score');
            $autoScoreFromModel = $submission->calculateAutoScore();
            $manualScoreDiff = $totalScore - $autoScoreFromModel;
            
            $submission->grade($lecturerId, $manualScoreDiff, $request->general_feedback);
        } 
        // CASE 2: Grading by Total Score Override (Quick grading)
        elseif ($request->has('manual_score')) {
            // Frontend sends 'manual_score' effectively as the Desired Total Score
            $desiredTotalScore = $request->manual_score;
            $autoScoreFromModel = $submission->calculateAutoScore();
            
            // The manual component is the difference
            // e.g. Total 9, Auto 5 => Manual component 4
            $manualScoreComponent = max(0, $desiredTotalScore - $autoScoreFromModel);
            
            $submission->grade($lecturerId, $manualScoreComponent, $request->general_feedback);
        }
        // CASE 3: Just saving feedback
        else {
            $submission->grade($lecturerId, $submission->manual_score, $request->general_feedback);
        }

        return response()->json([
            'success' => true,
            'message' => 'Submission graded successfully',
            'data' => $submission
        ]);
    }
    public function exportGrades(Request $request, int $id)
    {
        try {
            $assignment = Assignment::find($id);

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
            }

            $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
            if ($assignment->lecturer_id !== $userId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            // Disable Debugbar
            if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
                \Barryvdh\Debugbar\Facades\Debugbar::disable();
            }

            // Clean ALL output buffers to avoid corrupted excel files
            while (ob_get_level()) {
                ob_end_clean();
            }

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \Modules\Task\app\Exports\AssignmentGradesExport($id), 
                'grades_assignment_' . $id . '.xlsx'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Export Grades Error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false, 
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

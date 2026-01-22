<?php

namespace Modules\Task\app\Http\Controllers\Assignment;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Task\app\Models\Assignment;
use Modules\Task\app\Models\Question;
use Modules\Task\app\Models\QuestionImportLog;
use Modules\Auth\app\Models\AuditLog;
use Illuminate\Support\Facades\Validator;
// use Modules\Task\app\Jobs\ImportQuestionsJob; // Will implement later

/**
 * LecturerQuestionController
 * Manage questions for an assignment
 */
class LecturerQuestionController extends Controller
{
    /**
     * Get questions for an assignment
     */
    public function index(Request $request, int $assignmentId): JsonResponse
    {
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($assignment->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $questions = $assignment->questions;

        return response()->json([
            'success' => true,
            'data' => $questions
        ]);
    }

    /**
     * Add a question to assignment
     */
    public function store(Request $request, int $assignmentId): JsonResponse
    {
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($assignment->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($assignment->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Cannot add questions to published assignment'], 422);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:multiple_choice,short_answer,essay',
            'content' => 'required|string',
            'options' => 'required_if:type,multiple_choice|nullable|array',
            'correct_answer' => 'nullable|string',
            'points' => 'required|numeric|min:0',
            'order_index' => 'nullable|integer',
            'explanation' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $question = $assignment->questions()->create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Question added successfully',
            'data' => $question
        ], 201);
    }

    /**
     * Update a question
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $question = Question::with('assignment')->find($id);

        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($question->assignment->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($question->assignment->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Cannot edit questions of published assignment'], 422);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:multiple_choice,short_answer,essay',
            'content' => 'sometimes|string',
            'options' => 'nullable|array',
            'correct_answer' => 'nullable|string',
            'points' => 'sometimes|numeric|min:0',
            'order_index' => 'nullable|integer',
            'explanation' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $question->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully',
            'data' => $question
        ]);
    }

    /**
     * Delete a question
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $question = Question::with('assignment')->find($id);

        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($question->assignment->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($question->assignment->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Cannot delete questions from published assignment'], 422);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully'
        ]);
    }

    /**
     * Import questions from Excel
     */
    public function import(Request $request, int $assignmentId): JsonResponse
    {
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
        }

        $userId = $request->attributes->get('jwt_user_id') ?? ($request->user() ? $request->user()->id : null);
        if ($assignment->lecturer_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($assignment->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Cannot import into published assignment'], 422);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        $file = $request->file('file');
        
        // Sanitize filename
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        
        // Save file temporarily using Storage facade with UUID
        $path = $file->storeAs('temp/imports', (string) \Illuminate\Support\Str::uuid() . '_' . $fileName);
        
        // Get absolute path using Storage facade (more reliable for Job processing)
        $fullPath = \Illuminate\Support\Facades\Storage::path($path);

        // Create log entry as processing
        $importLog = QuestionImportLog::create([
            'assignment_id' => $assignment->id,
            'file_name' => $fileName,
            'imported_by' => $userId,
            'status' => 'processing',
            'total_rows' => 0,
            'processed_rows' => 0
        ]);

        // Dispatch Job Synchronously to return result immediately
        try {
            \Modules\Task\app\Jobs\ImportQuestionsJob::dispatchSync($importLog->id, $fullPath);
            
            // Refresh log to get updated status
            $importLog->refresh();

            // Check for errors in error_details column (json)
            $errors = [];
            if ($importLog->status === 'failed' && $importLog->error_details) {
                $errorDetails = $importLog->error_details;
                if (isset($errorDetails['message'])) {
                    $errorMessage = "Dòng " . ($errorDetails['row'] ?? '?') . ": " . $errorDetails['message'];
                    $errors[] = $errorMessage;
                }
            }

            // Get number of imported questions
            // processed_rows only counts successful rows
            
            return response()->json([
                'success' => true,
                'data' => [
                    'imported' => $importLog->success_count ?? $importLog->processed_rows,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            // Return error in same format as success for frontend to display
            return response()->json([
                'success' => true, // Set to true so frontend processes it
                'data' => [
                    'imported' => 0,
                    'errors' => ['Import thất bại: ' . $e->getMessage()]
                ]
            ]);
        }
    }
}

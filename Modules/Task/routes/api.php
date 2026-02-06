<?php

use Illuminate\Support\Facades\Route;
use Modules\Task\app\Http\Controllers\Task\TaskController;
use Modules\Task\app\Http\Controllers\Task\TaskSubmitController;
use Modules\Task\app\Http\Controllers\Admin\AdminTaskController;
use Modules\Task\app\Http\Controllers\Lecturer\LecturerTaskController;
use Modules\Task\app\Student\Controllers\StudentTaskController as StudentTaskControllerClean;
use Modules\Task\app\Http\Controllers\Reports\TaskReportController;
use Modules\Task\app\Http\Controllers\Statistics\TaskStatisticsController;
use Modules\Task\app\Http\Controllers\Calendar\CalendarController;
use Modules\Task\app\Http\Controllers\Reminder\ReminderController;
use Modules\Task\app\Http\Controllers\Email\EmailController;
use Modules\Task\app\Http\Controllers\Assignment\LecturerAssignmentController;
use Modules\Task\app\Http\Controllers\Assignment\LecturerQuestionController;
use Modules\Task\app\Http\Controllers\Assignment\StudentAssignmentController;
use Modules\Task\app\Http\Controllers\Assignment\ExtensionController;

/*
|--------------------------------------------------------------------------
| Task Module API Routes
|--------------------------------------------------------------------------
|
| All routes for Task module are defined here.
| Routes are grouped by user role: common, lecturer, student, admin.
|
*/

// =============================================================================
// COMMON ROUTES - All authenticated users
// =============================================================================
Route::middleware(['jwt'])
    ->prefix('api/v1')
    ->group(function () {
        // Tasks – only index and show via apiResource
        Route::apiResource('tasks', TaskController::class)
            ->only(['index', 'show']);

        // Additional task routes
        Route::get('tasks/my-tasks', [TaskController::class, 'getMyTasks'])->name('tasks.my-tasks');
        Route::get('tasks/my-assigned-tasks', [TaskController::class, 'getMyAssignedTasks'])->name('tasks.my-assigned-tasks');
        Route::get('tasks/statistics/my', [TaskController::class, 'getMyStatistics'])->name('tasks.my-statistics');
        Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
        Route::post('tasks/{task}/submit', [TaskSubmitController::class, 'submitTask'])->name('tasks.submit-task');
        Route::post('tasks/{task}/files', [TaskController::class, 'uploadFiles'])->name('tasks.upload-files');
        Route::delete('tasks/{task}/files/{file}', [TaskController::class, 'deleteFile'])->name('tasks.delete-file');
        Route::get('tasks/{task}/files/{file}/download', [TaskController::class, 'downloadFile'])->name('tasks.download-file');
    });

// =============================================================================
// LECTURER ROUTES
// =============================================================================
Route::middleware(['jwt', 'lecturer'])
    ->prefix('api/v1')
    ->group(function () {
        Route::apiResource('lecturer-tasks', LecturerTaskController::class);
        Route::post('lecturer-tasks/{task}/assign', [LecturerTaskController::class, 'assignTask'])->name('lecturer-tasks.assign');
        Route::post('lecturer-tasks/{task}/revoke', [LecturerTaskController::class, 'revokeTask'])->name('lecturer-tasks.revoke');
        Route::post('lecturer-tasks/recurring', [LecturerTaskController::class, 'createRecurringTask'])->name('lecturer-tasks.recurring');
        Route::post('lecturer-tasks/create-with-permissions', [LecturerTaskController::class, 'createTaskWithPermissions'])->name('lecturer-tasks.create-with-permissions');
        Route::post('lecturer-tasks/{task}/process-files', [LecturerTaskController::class, 'processTaskFiles'])->name('lecturer-tasks.process-files');
        Route::post('lecturer-tasks/{task}/upload-file', [LecturerTaskController::class, 'uploadFile'])->name('lecturer-tasks.upload-file');
        Route::post('lecturer-tasks/{task}/files', [LecturerTaskController::class, 'uploadFiles'])->name('lecturer-tasks.upload-files');
        Route::delete('lecturer-tasks/{task}/files/{file}', [LecturerTaskController::class, 'deleteFile'])->name('lecturer-tasks.delete-file');
        Route::get('lecturer-tasks/{task}/files/{file}/download', [LecturerTaskController::class, 'downloadFile'])->name('lecturer-tasks.download-file');
        Route::get('lecturer-tasks/{task}/submissions', [LecturerTaskController::class, 'getTaskSubmissions'])->name('lecturer-tasks.get-submissions');
        Route::post('lecturer-tasks/{task}/submissions/{submission}/grade', [LecturerTaskController::class, 'gradeSubmission'])->name('lecturer-tasks.grade-submission');
        
        // Lecturer calendar routes
        Route::apiResource('lecturer-calendar', CalendarController::class);

        // ================= NEW ASSIGNMENT SYSTEM ROUTES =================
        Route::prefix('lecturer')->group(function () {
            // Assignments
            Route::apiResource('assignments', LecturerAssignmentController::class);
            Route::post('assignments/{id}/publish', [LecturerAssignmentController::class, 'publish']);
            Route::post('assignments/{id}/close', [LecturerAssignmentController::class, 'close']);

            // Submissions & Grading
            Route::get('assignments/{id}/submissions', [LecturerAssignmentController::class, 'getSubmissions']);
            Route::get('submissions/{submissionId}', [LecturerAssignmentController::class, 'getSubmission']);
            Route::post('submissions/{submissionId}/grade', [LecturerAssignmentController::class, 'gradeSubmission']);
            Route::post('assignments/{id}/export-grades', [LecturerAssignmentController::class, 'exportGrades']);

            // Questions
            Route::get('assignments/{assignment}/questions', [LecturerQuestionController::class, 'index']);
            Route::post('assignments/{assignment}/questions', [LecturerQuestionController::class, 'store']);
            Route::post('assignments/{assignment}/import-questions', [LecturerQuestionController::class, 'import']);
            
            Route::put('questions/{id}', [LecturerQuestionController::class, 'update']);
            Route::delete('questions/{id}', [LecturerQuestionController::class, 'destroy']);

            // Extension Requests
            Route::get('extension-requests', [ExtensionController::class, 'index']);
            Route::post('extension-requests/{id}/approve', [ExtensionController::class, 'approve']);
            Route::post('extension-requests/{id}/reject', [ExtensionController::class, 'reject']);

            // ================= QUESTION BANK ROUTES =================
            Route::apiResource('question-banks', \Modules\Task\app\Http\Controllers\QuestionBankController::class);
            Route::get('question-banks/{id}/questions', [\Modules\Task\app\Http\Controllers\QuestionBankController::class, 'getQuestions']);
            Route::post('question-banks/{id}/import', [\Modules\Task\app\Http\Controllers\QuestionBankController::class, 'importQuestions']);
            Route::post('question-banks/{id}/chapters', [\Modules\Task\app\Http\Controllers\QuestionBankController::class, 'addChapter']);
            Route::put('question-banks/{id}/chapters/{chapterId}', [\Modules\Task\app\Http\Controllers\QuestionBankController::class, 'updateChapter']);
            Route::delete('question-banks/{id}/chapters/{chapterId}', [\Modules\Task\app\Http\Controllers\QuestionBankController::class, 'deleteChapter']);

            // ================= EXAM ROUTES =================
            Route::apiResource('exams', \Modules\Task\app\Http\Controllers\Exam\LecturerExamController::class);
            Route::post('exams/{id}/generate-codes', [\Modules\Task\app\Http\Controllers\Exam\LecturerExamController::class, 'generateCodes']);
            Route::get('exams/{id}/codes', [\Modules\Task\app\Http\Controllers\Exam\LecturerExamController::class, 'getExamCodes']);
            Route::post('exams/{id}/publish', [\Modules\Task\app\Http\Controllers\Exam\LecturerExamController::class, 'publish']);
            Route::post('exams/{id}/close', [\Modules\Task\app\Http\Controllers\Exam\LecturerExamController::class, 'close']);
            Route::get('exams/{id}/submissions', [\Modules\Task\app\Http\Controllers\Exam\LecturerExamController::class, 'getSubmissions']);
            Route::get('exam-submissions/{submissionId}', [\Modules\Task\app\Http\Controllers\Exam\LecturerExamController::class, 'getSubmission']);
            Route::post('exam-submissions/{submissionId}/grade', [\Modules\Task\app\Http\Controllers\Exam\LecturerExamController::class, 'gradeSubmission']);
            Route::get('exam-config/suggested', [\Modules\Task\app\Http\Controllers\Exam\LecturerExamController::class, 'getSuggestedConfig']);
        });
        // ================================================================
    });

// =============================================================================
// STUDENT ROUTES
// =============================================================================
Route::middleware(['jwt', 'student'])
    ->prefix('api/v1')
    ->group(function () {
        Route::apiResource('student-tasks', StudentTaskControllerClean::class)->only(['show']);
        Route::get('student-tasks', [StudentTaskControllerClean::class, 'index'])->name('student-tasks.index');
        Route::get('student-tasks/pending', [StudentTaskControllerClean::class, 'getPendingTasks'])->name('student-tasks.pending');
        Route::get('student-tasks/submitted', [StudentTaskControllerClean::class, 'getSubmittedTasks'])->name('student-tasks.submitted');
        Route::get('student-tasks/overdue', [StudentTaskControllerClean::class, 'getOverdueTasks'])->name('student-tasks.overdue');
        Route::get('student-tasks/statistics', [StudentTaskControllerClean::class, 'getStudentStatistics'])->name('student-tasks.statistics');
        Route::post('student-tasks/{task}/submit', [StudentTaskControllerClean::class, 'submitTask'])->name('student-tasks.submit');
        Route::post('student-tasks/{task}/upload-file', [StudentTaskControllerClean::class, 'uploadFile'])->name('student-tasks.upload-file');
        Route::get('student-tasks/{task}/files', [StudentTaskControllerClean::class, 'getFiles'])->name('student-tasks.get-files');
        Route::delete('student-tasks/{task}/files/{file}', [StudentTaskControllerClean::class, 'deleteFile'])->name('student-tasks.delete-file');
        Route::get('student-tasks/{task}/submission', [StudentTaskControllerClean::class, 'getSubmission'])->name('student-tasks.get-submission');
        Route::put('student-tasks/{task}/submission', [StudentTaskControllerClean::class, 'updateSubmission'])->name('student-tasks.update-submission');
        
        // Student calendar routes
        Route::apiResource('student-calendar', CalendarController::class);

        // ================= NEW ASSIGNMENT SYSTEM ROUTES =================
        Route::prefix('student')->group(function () {
            Route::get('assignments', [StudentAssignmentController::class, 'index']);
            Route::get('assignments/{id}', [StudentAssignmentController::class, 'show']);
            Route::post('assignments/{id}/start', [StudentAssignmentController::class, 'start']);
            Route::get('/assignments/{id}/result', [StudentAssignmentController::class, 'getResult']);
            Route::post('/assignments/{id}/submit', [StudentAssignmentController::class, 'submit']);
            Route::post('/assignments/upload', [StudentAssignmentController::class, 'uploadFile']);
            Route::post('/assignments/{id}/extension', [ExtensionController::class, 'request']);
            Route::get('submissions/{submissionId}/result', [StudentAssignmentController::class, 'getResult']);
            Route::post('assignments/{id}/extension', [ExtensionController::class, 'requestExtension']);

            // ================= EXAM ROUTES =================
            Route::get('exams', [\Modules\Task\app\Http\Controllers\Exam\StudentExamController::class, 'index']);
            Route::get('exams/{id}', [\Modules\Task\app\Http\Controllers\Exam\StudentExamController::class, 'show']);
            Route::post('exams/{id}/start', [\Modules\Task\app\Http\Controllers\Exam\StudentExamController::class, 'start']);
            Route::post('exam-submissions/{submissionId}/save-answer', [\Modules\Task\app\Http\Controllers\Exam\StudentExamController::class, 'saveAnswer']);
            Route::post('exam-submissions/{submissionId}/submit', [\Modules\Task\app\Http\Controllers\Exam\StudentExamController::class, 'submit']);
            Route::get('exam-submissions/{submissionId}/result', [\Modules\Task\app\Http\Controllers\Exam\StudentExamController::class, 'getResult']);
            Route::post('exam-submissions/{submissionId}/violation', [\Modules\Task\app\Http\Controllers\Exam\StudentExamController::class, 'logViolation']);
            
            // ================= GRADEBOOK ROUTES =================
            Route::get('grade-info', [\Modules\Task\app\Http\Controllers\Grade\StudentGradeController::class, 'getStudentInfo']);
            Route::get('grades', [\Modules\Task\app\Http\Controllers\Grade\StudentGradeController::class, 'getStudentGrades']);
        });
        // ================================================================
    });

// =============================================================================
// ADMIN ROUTES
// =============================================================================
Route::middleware(['jwt', 'admin'])
    ->prefix('api/v1')
    ->group(function () {
        Route::apiResource('admin-tasks', AdminTaskController::class);
    });

// =============================================================================
// MISCELLANEOUS ROUTES - Reports, Statistics, Reminders, Email
// =============================================================================
Route::middleware(['jwt'])
    ->prefix('api/v1')
    ->group(function () {
        Route::apiResource('reports', TaskReportController::class);
        Route::apiResource('statistics', TaskStatisticsController::class);
        Route::apiResource('reminders', ReminderController::class);
        Route::apiResource('email', EmailController::class);
    });

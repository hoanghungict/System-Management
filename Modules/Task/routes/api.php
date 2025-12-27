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

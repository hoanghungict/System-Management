<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\app\Http\Controllers\AttendanceController\SemesterController;
use Modules\Auth\app\Http\Controllers\AttendanceController\CourseController;
use Modules\Auth\app\Http\Controllers\AttendanceController\AttendanceController;
use Modules\Auth\app\Http\Controllers\AttendanceController\AdminAttendanceController;
use Modules\Auth\app\Http\Controllers\AttendanceController\EnrollmentController;
use Modules\Auth\app\Http\Controllers\AttendanceController\TimetableController;

/*
|--------------------------------------------------------------------------
| Attendance Module Routes - Điểm danh theo môn học
|--------------------------------------------------------------------------
|
| Các routes API cho hệ thống điểm danh mới
| Phân quyền: Admin, Lecturer, Student
|
*/

Route::prefix('v1/attendance')->group(function () {

    // =====================================================================
    // PUBLIC ROUTES (cần JWT nhưng tất cả roles đều access được)
    // =====================================================================
    Route::middleware(['jwt'])->group(function () {
        
        // Lấy học kỳ đang hoạt động
        Route::get('/semesters/active', [SemesterController::class, 'getActive']);
        
        // Danh sách học kỳ (chỉ xem)
        Route::get('/semesters', [SemesterController::class, 'index']);
        Route::get('/semesters/{id}', [SemesterController::class, 'show']);
        
        // ----- THỜI KHÓA BIỂU -----
        Route::prefix('timetable')->group(function () {
            Route::get('/weekly', [TimetableController::class, 'weekly']);
            Route::get('/daily', [TimetableController::class, 'daily']);
            Route::get('/periods', [TimetableController::class, 'periods']);
        });
    });

    // =====================================================================
    // ADMIN ROUTES - Quản trị hệ thống
    // =====================================================================
    Route::middleware(['jwt', 'admin'])->group(function () {
        
        // ----- QUẢN LÝ HỌC KỲ -----
        Route::post('/semesters', [SemesterController::class, 'store']);
        Route::put('/semesters/{id}', [SemesterController::class, 'update']);
        Route::delete('/semesters/{id}', [SemesterController::class, 'destroy']);
        Route::post('/semesters/{id}/activate', [SemesterController::class, 'activate']);
        
        // ----- QUẢN LÝ MÔN HỌC -----
        Route::get('/courses', [CourseController::class, 'index']);
        Route::post('/courses', [CourseController::class, 'store']);
        Route::get('/courses/{id}', [CourseController::class, 'show']);
        Route::put('/courses/{id}', [CourseController::class, 'update']);
        Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
        Route::post('/courses/{id}/regenerate-sessions', [CourseController::class, 'regenerateSessions']);
        Route::get('/courses/{id}/sessions', [CourseController::class, 'getSessions']);
        Route::get('/courses/{id}/students', [CourseController::class, 'getStudents']);
        Route::get('/courses/{id}/statistics', [CourseController::class, 'getStatistics']);
        
        // ----- QUẢN LÝ ĐĂNG KÝ MÔN HỌC -----
        Route::get('/courses/{courseId}/enrollments', [EnrollmentController::class, 'index']);
        Route::post('/courses/{courseId}/enroll', [EnrollmentController::class, 'enrollStudent']);
        Route::post('/courses/{courseId}/enroll-bulk', [EnrollmentController::class, 'enrollStudentsBulk']);
        Route::post('/courses/{courseId}/enroll-late', [EnrollmentController::class, 'addLateEnrollment']);
        Route::delete('/courses/{courseId}/enrollments/{studentId}', [EnrollmentController::class, 'unenrollStudent']);
        
        // ----- ADMIN: QUẢN TRỊ ĐIỂM DANH (SỬA SAU KHI COMPLETED) -----
        Route::put('/admin/attendances/{attendanceId}', [AdminAttendanceController::class, 'updateAttendance']);
        Route::put('/admin/sessions/{sessionId}', [AdminAttendanceController::class, 'updateSession']);
        Route::post('/admin/sessions/{sessionId}/reopen', [AdminAttendanceController::class, 'reopenSession']);
    });

    // =====================================================================
    // LECTURER ROUTES - Giảng viên điểm danh
    // =====================================================================
    Route::middleware(['jwt', 'lecturer'])->group(function () {
        
        // ----- XEM MÔN HỌC CỦA GV -----
        Route::get('/my-courses', [CourseController::class, 'getMyCourses']);
        Route::get('/courses/{id}', [CourseController::class, 'show']);
        Route::get('/courses/{id}/sessions', [CourseController::class, 'getSessions']);
        Route::get('/courses/{id}/students', [CourseController::class, 'getStudents']);
        Route::get('/courses/{id}/statistics', [CourseController::class, 'getStatistics']);
        
        // ----- ĐIỂM DANH -----
        Route::get('/sessions/{sessionId}', [AttendanceController::class, 'getSessionDetails']);
        Route::post('/sessions/{sessionId}/start', [AttendanceController::class, 'startSession']);
        Route::put('/sessions/{sessionId}/attendance', [AttendanceController::class, 'updateAttendance']);
        Route::put('/sessions/{sessionId}/attendance/bulk', [AttendanceController::class, 'bulkUpdateAttendance']);
        Route::post('/sessions/{sessionId}/mark-all-present', [AttendanceController::class, 'markAllPresent']);
        Route::post('/sessions/{sessionId}/complete', [AttendanceController::class, 'completeSession']);
        Route::post('/sessions/{sessionId}/cancel', [AttendanceController::class, 'cancelSession']);
        Route::put('/sessions/{sessionId}/reschedule', [AttendanceController::class, 'rescheduleSession']);
        
        // ----- THỐNG KÊ -----
        Route::get('/courses/{courseId}/students/{studentId}/stats', [AttendanceController::class, 'getStudentStats']);
        Route::get('/courses/{courseId}/at-risk-students', [AttendanceController::class, 'getAtRiskStudents']);
    });

    // =====================================================================
    // STUDENT ROUTES - Sinh viên xem điểm danh
    // =====================================================================
    Route::middleware(['jwt'])->group(function () {
        
        // Lấy môn học của sinh viên
        Route::get('/students/{studentId}/courses', [EnrollmentController::class, 'getStudentCourses']);
    });
});

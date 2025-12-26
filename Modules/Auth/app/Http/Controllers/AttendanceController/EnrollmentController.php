<?php

namespace Modules\Auth\app\Http\Controllers\AttendanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\AttendanceService\EnrollmentService;
use Modules\Auth\app\Http\Requests\AttendanceRequest\EnrollStudentRequest;
use Modules\Auth\app\Http\Requests\AttendanceRequest\BulkEnrollStudentsRequest;
use Illuminate\Support\Facades\Log;

/**
 * Controller quản lý đăng ký môn học
 * 
 * @group Attendance - Enrollment Management
 */
class EnrollmentController extends Controller
{
    protected EnrollmentService $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Lấy danh sách sinh viên trong môn
     */
    public function index(int $courseId): JsonResponse
    {
        try {
            $enrollments = $this->enrollmentService->getCourseEnrollments($courseId);

            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'total' => $enrollments->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get enrollments', ['course_id' => $courseId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Đăng ký 1 sinh viên vào môn
     */
    public function enrollStudent(EnrollStudentRequest $request, int $courseId): JsonResponse
    {
        try {
            $adminId = $request->user()->id;
            $data = $request->validated();
            
            $enrollment = $this->enrollmentService->enrollStudent(
                $courseId,
                $data['student_id'],
                $adminId,
                $data['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký sinh viên thành công',
                'data' => $enrollment,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to enroll student', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Đăng ký nhiều sinh viên vào môn (bulk)
     */
    public function enrollStudentsBulk(BulkEnrollStudentsRequest $request, int $courseId): JsonResponse
    {
        try {
            $adminId = $request->user()->id;
            $studentIds = $request->validated()['student_ids'];
            
            $results = $this->enrollmentService->enrollStudentsBulk($courseId, $studentIds, $adminId);

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký sinh viên hoàn tất',
                'data' => [
                    'success_count' => count($results['success']),
                    'failed_count' => count($results['failed']),
                    'already_enrolled_count' => count($results['already_enrolled']),
                    'details' => $results,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to bulk enroll students', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Thêm sinh viên đăng ký muộn
     * 
     * Sinh viên đăng ký muộn sẽ được tự động pass các buổi trước
     */
    public function addLateEnrollment(EnrollStudentRequest $request, int $courseId): JsonResponse
    {
        try {
            $adminId = $request->user()->id;
            $data = $request->validated();
            
            $enrollment = $this->enrollmentService->addLateEnrollment(
                $courseId,
                $data['student_id'],
                $adminId,
                $data['note'] ?? 'Đăng ký muộn'
            );

            return response()->json([
                'success' => true,
                'message' => 'Thêm sinh viên đăng ký muộn thành công. Các buổi trước đã được tự động pass.',
                'data' => $enrollment,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to add late enrollment', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Hủy đăng ký sinh viên khỏi môn
     */
    public function unenrollStudent(Request $request, int $courseId, int $studentId): JsonResponse
    {
        try {
            $reason = $request->get('reason');
            $success = $this->enrollmentService->unenrollStudent($courseId, $studentId, $reason);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đăng ký hoặc hủy thất bại',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Hủy đăng ký sinh viên thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to unenroll student', [
                'course_id' => $courseId,
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Lấy danh sách môn của sinh viên
     */
    public function getStudentCourses(int $studentId): JsonResponse
    {
        try {
            $courses = $this->enrollmentService->getStudentCourses($studentId);

            return response()->json([
                'success' => true,
                'data' => $courses,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get student courses', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

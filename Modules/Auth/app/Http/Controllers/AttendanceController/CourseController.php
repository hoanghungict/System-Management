<?php

namespace Modules\Auth\app\Http\Controllers\AttendanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\AttendanceService\CourseService;
use Modules\Auth\app\Http\Requests\AttendanceRequest\CreateCourseRequest;
use Modules\Auth\app\Http\Requests\AttendanceRequest\UpdateCourseRequest;
use Illuminate\Support\Facades\Log;

/**
 * Controller quản lý Môn học
 * 
 * @group Attendance - Course Management
 */
class CourseController extends Controller
{
    protected CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * Lấy danh sách môn học
     * 
     * @queryParam semester_id int Lọc theo học kỳ
     * @queryParam lecturer_id int Lọc theo giảng viên
     * @queryParam status string Lọc theo trạng thái
     * @queryParam search string Tìm kiếm theo tên/mã
     * @queryParam per_page int Số bản ghi mỗi trang
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['semester_id', 'lecturer_id', 'status', 'department_id', 'search']);
            $perPage = $request->get('per_page', 15);
            
            $courses = $this->courseService->getCourses($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $courses,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get courses', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách môn học',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy chi tiết môn học
     */
    public function show(int $id): JsonResponse
    {
        try {
            $course = $this->courseService->getCourseById($id);

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy môn học',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $course,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get course', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo môn học mới (và tự động sinh lịch điểm danh)
     */
    public function store(CreateCourseRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $generateSessions = $request->boolean('generate_sessions', true);
            
            $course = $this->courseService->createCourse($data, $generateSessions);

            return response()->json([
                'success' => true,
                'message' => 'Tạo môn học thành công' . ($generateSessions ? ' và đã tạo lịch điểm danh' : ''),
                'data' => $course,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create course', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo môn học',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật môn học
     */
    public function update(UpdateCourseRequest $request, int $id): JsonResponse
    {
        try {
            $success = $this->courseService->updateCourse($id, $request->validated());

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy môn học hoặc cập nhật thất bại',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật môn học thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update course', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật môn học',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa môn học
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $success = $this->courseService->deleteCourse($id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy môn học',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Xóa môn học thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete course', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tái tạo lịch điểm danh cho môn học
     */
    public function regenerateSessions(int $id): JsonResponse
    {
        try {
            $count = $this->courseService->regenerateSessions($id);

            return response()->json([
                'success' => true,
                'message' => "Đã tạo lại {$count} buổi điểm danh",
                'sessions_count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to regenerate sessions', ['course_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Lấy danh sách buổi học của môn
     */
    public function getSessions(Request $request, int $id): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $sessions = $this->courseService->getCourseSessions($id, $perPage);

            return response()->json([
                'success' => true,
                'data' => $sessions,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get course sessions', ['course_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách sinh viên trong môn
     */
    public function getStudents(int $id): JsonResponse
    {
        try {
            $students = $this->courseService->getCourseStudents($id);

            return response()->json([
                'success' => true,
                'data' => $students,
                'total' => $students->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get course students', ['course_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thống kê môn học
     */
    public function getStatistics(int $id): JsonResponse
    {
        try {
            $stats = $this->courseService->getCourseStatistics($id);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get course statistics', ['course_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Lấy môn học của giảng viên đang đăng nhập
     */
    public function getMyCourses(Request $request): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Vui lòng đăng nhập lại',
                ], 401);
            }

            $semesterId = $request->get('semester_id');
            
            $courses = $this->courseService->getCoursesByLecturer($lecturerId, $semesterId);

            return response()->json([
                'success' => true,
                'data' => $courses,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get my courses', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

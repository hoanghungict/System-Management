<?php

namespace Modules\Auth\app\Http\Controllers\AttendanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\AttendanceService\AttendanceService;
use Modules\Auth\app\Http\Requests\AttendanceRequest\UpdateAttendanceRequest;
use Modules\Auth\app\Http\Requests\AttendanceRequest\BulkUpdateAttendanceRequest;
use Modules\Auth\app\Http\Requests\AttendanceRequest\RescheduleSessionRequest;
use Illuminate\Support\Facades\Log;

/**
 * Controller xử lý điểm danh (cho Giảng viên)
 * 
 * @group Attendance - Attendance Management
 */
class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Lấy chi tiết buổi học với danh sách điểm danh
     */
    public function getSessionDetails(int $sessionId): JsonResponse
    {
        try {
            $session = $this->attendanceService->getSessionDetails($sessionId);

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy buổi học',
                ], 404);
            }

            // Log để debug
            $attendancesCount = $session->attendances ? $session->attendances->count() : 0;
            Log::info('Getting session details', [
                'session_id' => $sessionId,
                'course_id' => $session->course_id,
                'status' => $session->status,
                'attendances_count' => $attendancesCount,
                'attendances_loaded' => $session->relationLoaded('attendances'),
                'sample_attendance' => $session->attendances->first() ? [
                    'id' => $session->attendances->first()->id,
                    'student_id' => $session->attendances->first()->student_id,
                    'status' => $session->attendances->first()->status,
                ] : null,
            ]);

            // Đảm bảo attendances được serialize
            $sessionData = $session->toArray();
            
            Log::info('Session data to return', [
                'session_id' => $sessionId,
                'has_attendances_key' => isset($sessionData['attendances']),
                'attendances_count_in_array' => isset($sessionData['attendances']) ? count($sessionData['attendances']) : 0,
            ]);

            return response()->json([
                'success' => true,
                'data' => $session,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get session details', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin buổi học
     */
    public function updateSession(Request $request, int $sessionId): JsonResponse
    {
        try {
            $data = $request->all();
            $session = $this->attendanceService->updateSession($sessionId, $data);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật buổi học thành công',
                'data' => $session,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update session', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Bắt đầu điểm danh buổi học
     */
    public function startSession(Request $request, int $sessionId): JsonResponse
    {
        try {
            // Get user ID from JWT middleware (stored in request attributes)
            $lecturerId = $request->attributes->get('jwt_user_id');
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Vui lòng đăng nhập lại',
                ], 401);
            }
            
            $session = $this->attendanceService->startSession($sessionId, $lecturerId);

            return response()->json([
                'success' => true,
                'message' => 'Bắt đầu điểm danh thành công',
                'data' => $session,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start session', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cập nhật điểm danh 1 sinh viên
     */
    public function updateAttendance(UpdateAttendanceRequest $request, int $sessionId): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Vui lòng đăng nhập lại',
                ], 401);
            }

            $data = $request->validated();
            
            $additionalData = [];
            if (isset($data['note'])) $additionalData['note'] = $data['note'];
            if (isset($data['minutes_late'])) $additionalData['minutes_late'] = $data['minutes_late'];
            if (isset($data['check_in_time'])) $additionalData['check_in_time'] = $data['check_in_time'];
            if (isset($data['excuse_reason'])) $additionalData['excuse_reason'] = $data['excuse_reason'];

            $success = $this->attendanceService->updateAttendance(
                $sessionId,
                $data['student_id'],
                $data['status'],
                $lecturerId,
                $additionalData
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cập nhật điểm danh thất bại',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật điểm danh thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update attendance', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cập nhật điểm danh hàng loạt
     */
    public function bulkUpdateAttendance(BulkUpdateAttendanceRequest $request, int $sessionId): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Vui lòng đăng nhập lại',
                ], 401);
            }

            $attendances = $request->validated()['attendances'];
            
            $updatedCount = $this->attendanceService->bulkUpdateAttendance($sessionId, $attendances, $lecturerId);

            return response()->json([
                'success' => true,
                'message' => "Đã cập nhật {$updatedCount} bản ghi điểm danh",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to bulk update attendance', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Đánh dấu tất cả có mặt
     */
    public function markAllPresent(Request $request, int $sessionId): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Vui lòng đăng nhập lại',
                ], 401);
            }

            $count = $this->attendanceService->markAllPresent($sessionId, $lecturerId);

            return response()->json([
                'success' => true,
                'message' => "Đã đánh dấu {$count} sinh viên có mặt",
                'marked_count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark all present', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Hoàn thành buổi điểm danh
     * 
     * SAU KHI HOÀN TẤT: GV KHÔNG ĐƯỢC SỬA NỮA
     */
    public function completeSession(Request $request, int $sessionId): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Vui lòng đăng nhập lại',
                ], 401);
            }

            $success = $this->attendanceService->completeSession($sessionId, $lecturerId);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hoàn thành buổi điểm danh thất bại',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Hoàn thành buổi điểm danh thành công. Lưu ý: Bạn sẽ không thể sửa đổi sau khi hoàn thành.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to complete session', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Hủy buổi học
     */
    public function cancelSession(int $sessionId): JsonResponse
    {
        try {
            $success = $this->attendanceService->cancelSession($sessionId);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hủy buổi học thất bại',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Hủy buổi học thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel session', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Đổi ngày buổi học
     */
    public function rescheduleSession(RescheduleSessionRequest $request, int $sessionId): JsonResponse
    {
        try {
            $data = $request->validated();
            
            $success = $this->attendanceService->rescheduleSession(
                $sessionId,
                $data['session_date'],
                $data['start_time'] ?? null,
                $data['end_time'] ?? null
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đổi lịch buổi học thất bại',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đổi lịch buổi học thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reschedule session', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Lấy thống kê điểm danh của sinh viên trong môn
     */
    public function getStudentStats(int $courseId, int $studentId): JsonResponse
    {
        try {
            $stats = $this->attendanceService->getStudentAttendanceStats($studentId, $courseId);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get student stats', [
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
     * Lấy danh sách sinh viên có nguy cơ (gần/vượt số buổi nghỉ)
     */
    public function getAtRiskStudents(int $courseId): JsonResponse
    {
        try {
            $students = $this->attendanceService->getAtRiskStudents($courseId);

            return response()->json([
                'success' => true,
                'data' => $students,
                'total' => count($students),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get at-risk students', ['course_id' => $courseId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Lấy tổng hợp điểm danh môn học
     * 
     * Trả về dữ liệu dạng bảng: sessions là cột, students là hàng
     */
    public function getCourseSummary(int $courseId): JsonResponse
    {
        try {
            $summary = $this->attendanceService->getCourseSummary($courseId);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get course summary', ['course_id' => $courseId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

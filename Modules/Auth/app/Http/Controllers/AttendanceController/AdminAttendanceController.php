<?php

namespace Modules\Auth\app\Http\Controllers\AttendanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\AttendanceService\AttendanceService;
use Illuminate\Support\Facades\Log;

/**
 * Controller quản trị điểm danh (chỉ dành cho Admin)
 * 
 * Admin có thể sửa điểm danh SAU KHI COMPLETED
 * 
 * @group Attendance - Admin Management
 */
class AdminAttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * ADMIN: Sửa điểm danh (bao gồm cả sau khi completed)
     * 
     * Chức năng này chỉ dành cho Admin
     */
    public function updateAttendance(Request $request, int $attendanceId): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:present,absent,late,excused',
                'note' => 'nullable|string|max:500',
                'minutes_late' => 'nullable|integer|min:0',
            ]);

            $adminId = $request->user()->id;
            
            $additionalData = [];
            if ($request->has('note')) $additionalData['note'] = $request->note;
            if ($request->has('minutes_late')) $additionalData['minutes_late'] = $request->minutes_late;

            $success = $this->attendanceService->adminUpdateAttendance(
                $attendanceId,
                $request->status,
                $adminId,
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
                'message' => 'Admin đã cập nhật điểm danh thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Admin failed to update attendance', [
                'attendance_id' => $attendanceId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ADMIN: Sửa buổi học (kể cả đã completed)
     */
    public function updateSession(Request $request, int $sessionId): JsonResponse
    {
        try {
            $request->validate([
                'session_date' => 'sometimes|date',
                'start_time' => 'sometimes|date_format:H:i',
                'end_time' => 'sometimes|date_format:H:i',
                'room' => 'nullable|string|max:50',
                'topic' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled,holiday',
            ]);

            $data = $request->only(['session_date', 'start_time', 'end_time', 'room', 'topic', 'notes', 'status']);
            
            // Nếu đổi ngày, cập nhật day_of_week
            if (isset($data['session_date'])) {
                $data['day_of_week'] = \Carbon\Carbon::parse($data['session_date'])->dayOfWeekIso + 1;
            }

            // Sử dụng repository trực tiếp vì admin được bypass check completed
            $session = \Modules\Auth\app\Models\Attendance\AttendanceSession::findOrFail($sessionId);
            $session->update($data);

            Log::info('Admin updated session', [
                'session_id' => $sessionId,
                'admin_id' => $request->user()->id,
                'data' => $data,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Admin đã cập nhật buổi học thành công',
                'data' => $session->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Admin failed to update session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ADMIN: Mở lại buổi điểm danh đã completed
     */
    public function reopenSession(Request $request, int $sessionId): JsonResponse
    {
        try {
            $session = \Modules\Auth\app\Models\Attendance\AttendanceSession::findOrFail($sessionId);
            
            if ($session->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Buổi học không ở trạng thái đã hoàn thành',
                ], 400);
            }

            $session->update([
                'status' => 'in_progress',
                'completed_at' => null,
            ]);

            Log::info('Admin reopened session', [
                'session_id' => $sessionId,
                'admin_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã mở lại buổi điểm danh. Giảng viên có thể tiếp tục chỉnh sửa.',
            ]);
        } catch (\Exception $e) {
            Log::error('Admin failed to reopen session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

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
    protected \Modules\Task\app\Services\CalendarService $calendarService;

    public function __construct(
        AttendanceService $attendanceService,
        \Modules\Task\app\Services\CalendarService $calendarService
    ) {
        $this->attendanceService = $attendanceService;
        $this->calendarService = $calendarService;
    }

    /**
     * ADMIN: Lấy danh sách buổi học (có filter)
     */
    public function index(Request $request): JsonResponse
    {
        $query = \Modules\Auth\app\Models\Attendance\AttendanceSession::with(['course.lecturer']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('date_from')) {
            $query->where('session_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('session_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderBy('session_date', 'desc')->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
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
            
            // Check Conflict if changing time/room
            if (isset($data['session_date']) || isset($data['start_time']) || isset($data['end_time']) || isset($data['room'])) {
                $checkDate = $data['session_date'] ?? $session->session_date->toDateString();
                $checkStart = $data['start_time'] ?? $session->start_time; // Need to ensure format H:i
                $checkEnd = $data['end_time'] ?? $session->end_time;
                $checkRoom = $data['room'] ?? $session->room;
                
                $conflicts = $this->calendarService->checkConflict(
                    $checkDate,
                    $checkStart,
                    $checkEnd,
                    $checkRoom,
                    null, // Don't check lecturer on update for simplicity unless requested
                    $sessionId
                );

                if (!empty($conflicts)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phát hiện trùng lịch',
                        'conflicts' => $conflicts
                    ], 409);
                }
            }

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
     * ADMIN: Tạo buổi học thủ công
     */
    public function store(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'session_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'room' => 'required|string|max:50',
            'lecturer_id' => 'nullable|exists:lecturer,id', // Tùy chọn, nếu thay đổi giảng viên
            'topic' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            
            // Tự động tính day_of_week
            $data['day_of_week'] = \Carbon\Carbon::parse($data['session_date'])->dayOfWeekIso + 1; // 2=Mon, 8=Sun -> DB: 2-8? Need to check DB convention. typically 1=Sun or 0=Sun. 
            // In source code: 2=Mon (based on typical ISO). Let's assume day_of_week in DB follows standard (2-8 or 0-6).
            // Let's check insert_dummy_data.sql. 
            // '2026-01-20' is Tuesday. insert says day_of_week=3. So 2=Mon, 3=Tue. Correct. 
            // Carbon dayOfWeekIso: 1=Mon, 7=Sun. So +1 logic seems to match 2=Mon?? No.
            // If 2=Mon in DB, and Iso 1=Mon, then +1 makes 2. Correct.
            
            // Default status
            $data['status'] = 'scheduled';
            $data['session_number'] = \Modules\Auth\app\Models\Attendance\AttendanceSession::where('course_id', $data['course_id'])->count() + 1;

            // Check Conflict
            $conflicts = $this->calendarService->checkConflict(
                $data['session_date'],
                $data['start_time'],
                $data['end_time'],
                $data['room'],
                $data['lecturer_id'] ?? null
            );

            if (!empty($conflicts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phát hiện trùng lịch',
                    'conflicts' => $conflicts,
                    // Allow force create? Maybe later. For now, block.
                ], 409);
            }

            $session = \Modules\Auth\app\Models\Attendance\AttendanceSession::create($data);

            /*
            Log::info('Admin created manual session', [
                'admin_id' => $request->user()->id,
                'session_id' => $session->id,
                'data' => $data
            ]);
            */

            return response()->json([
                'success' => true,
                'message' => 'Tạo buổi học thành công',
                'data' => $session
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ADMIN: Xóa buổi học
     */
    public function destroy(Request $request, int $sessionId): JsonResponse
    {
        try {
            $session = \Modules\Auth\app\Models\Attendance\AttendanceSession::findOrFail($sessionId);
            $session->delete();

            /*
            Log::info('Admin deleted session', [
                'admin_id' => $request->user()->id,
                'session_id' => $sessionId
            ]);
            */

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa buổi học thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa: ' . $e->getMessage(),
            ], 500);
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
    /**
     * ADMIN: Import lịch học từ Excel
     */
    public function importSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \Modules\Auth\app\Imports\ScheduleImport, $request->file('file'));
            
            Log::info('Admin imported schedule', [
                'admin_id' => $request->user()->id,
                'file' => $request->file('file')->getClientOriginalName()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Import lịch học thành công'
            ]);
        } catch (\Exception $e) {
            Log::error('Admin failed to import schedule', [
                'error' => $e->getMessage()
            ]);
            
            // Extract meaningful error from ValidationException if possible
            $msg = $e->getMessage();
            if ($e instanceof \Maatwebsite\Excel\Validators\ValidationException) {
                $failures = $e->failures();
                $msg = 'Lỗi dữ liệu tại dòng ' . $failures[0]->row() . ': ' . implode(', ', $failures[0]->errors());
            }

            return response()->json([
                'success' => false,
                'message' => 'Import thất bại: ' . $msg,
            ], 400); // 400 for bad request/data
        }
    }
}

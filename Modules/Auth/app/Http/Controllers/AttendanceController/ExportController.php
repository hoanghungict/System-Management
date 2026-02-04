<?php
/* Modules/Auth/app/Http/Controllers/AttendanceController/ExportController.php */

namespace Modules\Auth\app\Http\Controllers\AttendanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\app\Services\AttendanceService\AttendanceService;
use Modules\Auth\app\Exports\CourseAttendanceExport;
use Modules\Auth\app\Exports\SemesterTimelineExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Export điểm danh của một môn học
     */
    public function exportCourseAttendance(int $courseId, Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $jwtPayload = $request->attributes->get('jwt_payload');
            $userType = $request->attributes->get('jwt_user_type');

            $isAdmin = false;
            if ($jwtPayload && !empty($jwtPayload->is_admin)) {
                $isAdmin = true;
            } elseif (in_array(strtolower($userType), ['admin', 'quan tri', 'quantri'])) {
                $isAdmin = true;
            }

            Log::info('Start export course attendance', [
                'course_id' => $courseId,
                'user_id' => $lecturerId,
                'is_admin' => $isAdmin
            ]);

            // Fetch summary first to get lecturer_id
            $summary = $this->attendanceService->getCourseSummary($courseId);
            
            // Nếu không phải admin, kiểm tra xem có phải môn của mình không
            if (!$isAdmin && (!isset($summary['course']['lecturer_id']) || $summary['course']['lecturer_id'] != $lecturerId)) {
                return response()->json(['success' => false, 'message' => 'Bạn không có quyền xuất dữ liệu môn học này'], 403);
            }

            $fileName = 'diem_danh_' . str_replace(' ', '_', $summary['course']['code']) . '_' . date('Y-m-d') . '.xlsx';

            return Excel::download(new CourseAttendanceExport($summary), $fileName);
        } catch (\Exception $e) {
            Log::error('Export course attendance failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xuất file Excel môn học: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export tổng quan điểm danh (Timeline) của học kỳ
     */
    public function exportSemesterTimeline(Request $request)
    {
        try {
            $semesterId = (int) $request->get('semester_id');
            if (!$semesterId) {
                return response()->json(['success' => false, 'message' => 'Thiếu semester_id'], 400);
            }

            $lecturerId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');
            $jwtPayload = $request->attributes->get('jwt_payload');
            
            Log::info('Start export semester timeline', [
                'semester_id' => $semesterId,
                'jwt_lecturer_id' => $lecturerId,
                'jwt_user_type' => $userType,
                'jwt_is_admin' => $jwtPayload->is_admin ?? false
            ]);

            // Admin có thể là lecturer với is_admin = true HOẶC user_type là admin
            $isAdmin = false;
            if ($jwtPayload && !empty($jwtPayload->is_admin)) {
                $isAdmin = true;
            } elseif (in_array(strtolower($userType ?? ''), ['admin', 'quan tri', 'quantri'])) {
                $isAdmin = true;
            }
            
            // Nếu là admin -> lấy tất cả (filterLecturerId = null)
            // Nếu là lecturer thường -> lọc theo lecturer_id
            $filterLecturerId = (!$isAdmin && $lecturerId) ? $lecturerId : null;
            
            Log::info('Determined filter', ['is_admin' => $isAdmin, 'filter_lecturer_id' => $filterLecturerId]);

            $timelineData = $this->attendanceService->getSemesterTimeline($semesterId, $filterLecturerId);
            
            Log::info('Timeline data results', [
                'students' => count($timelineData['students']),
                'columns' => count($timelineData['columns']),
                'semester' => $timelineData['semester_name']
            ]);

            $semesterName = $timelineData['semester_name'] ?? 'hoc_ky';
            $fileName = 'tong_quan_diem_danh_' . str_replace(' ', '_', $semesterName) . '_' . date('Y-m-d') . '.xlsx';

            return Excel::download(new SemesterTimelineExport($timelineData), $fileName);
        } catch (\Exception $e) {
            Log::error('Export semester timeline failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xuất file Excel: ' . $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace Modules\Auth\app\Http\Controllers\AttendanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\AttendanceService\TimetableService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controller quản lý Thời khóa biểu
 * 
 * @group Timetable - Thời khóa biểu
 */
class TimetableController extends Controller
{
    protected TimetableService $timetableService;

    public function __construct(TimetableService $timetableService)
    {
        $this->timetableService = $timetableService;
    }

    /**
     * Lấy thời khóa biểu theo tuần
     * 
     * @queryParam start_date string Ngày bắt đầu tuần (Y-m-d). Mặc định: đầu tuần hiện tại
     * @queryParam end_date string Ngày kết thúc tuần (Y-m-d). Mặc định: cuối tuần hiện tại
     * 
     * @response {
     *   "success": true,
     *   "data": {
     *     "week_start": "2025-01-20",
     *     "week_end": "2025-01-26",
     *     "items": [...]
     *   }
     * }
     */
    public function weekly(Request $request): JsonResponse
    {
        try {
            // Lấy thông tin user từ JWT middleware
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            // Lấy ngày bắt đầu và kết thúc tuần
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Nếu không có, mặc định là tuần hiện tại
            if (!$startDate || !$endDate) {
                $now = Carbon::now();
                $startDate = $now->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
                $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            }

            $data = $this->timetableService->getWeeklyTimetable(
                $startDate,
                $endDate,
                $userId,
                $userType
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Thời khóa biểu tuần được lấy thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get weekly timetable', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thời khóa biểu',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Lấy thời khóa biểu theo ngày
     * 
     * @queryParam date string Ngày (Y-m-d). Mặc định: hôm nay
     * 
     * @response {
     *   "success": true,
     *   "data": {
     *     "date": "2025-01-20",
     *     "items": [...]
     *   }
     * }
     */
    public function daily(Request $request): JsonResponse
    {
        try {
            // Lấy thông tin user từ JWT middleware
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            // Lấy ngày
            $date = $request->input('date', Carbon::now()->format('Y-m-d'));

            $data = $this->timetableService->getDailyTimetable(
                $date,
                $userId,
                $userType
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Thời khóa biểu ngày được lấy thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get daily timetable', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thời khóa biểu',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Lấy config mapping tiết học
     * 
     * @response {
     *   "success": true,
     *   "data": {
     *     "1": {"start": "07:00", "end": "07:45"},
     *     "2": {"start": "07:50", "end": "08:35"},
     *     ...
     *   }
     * }
     */
    public function periods(Request $request): JsonResponse
    {
        try {
            $periods = $this->timetableService->getPeriodsConfig();

            return response()->json([
                'success' => true,
                'data' => $periods,
                'message' => 'Config tiết học được lấy thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get periods config', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy config tiết học',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

<?php

namespace Modules\Task\app\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Task\app\Services\CalendarService;

/**
 * Calendar Controller - Common cho tất cả roles
 * Tuân thủ Clean Architecture: Controller chỉ xử lý HTTP requests/responses
 */
class CalendarController extends Controller
{
    public function __construct(
        private CalendarService $calendarService
    ) {}

    /**
     * Lấy events theo ngày (Common)
     */
    public function getEventsByDate(Request $request): JsonResponse
    {
        try {
            $date = $request->input('date', now()->format('Y-m-d'));
            $events = $this->calendarService->getEventsByDate($date);
            
            return response()->json([
                'success' => true,
                'data' => $events,
                'message' => 'Events retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy events theo khoảng thời gian (Common)
     */
    public function getEventsByRange(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->addDays(30)->format('Y-m-d'));
            $events = $this->calendarService->getEventsByRange($startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $events,
                'message' => 'Events retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy events sắp tới (Common)
     */
    public function getUpcomingEvents(Request $request): JsonResponse
    {
        try {
            $events = $this->calendarService->getUpcomingEvents();
            
            return response()->json([
                'success' => true,
                'data' => $events,
                'message' => 'Upcoming events retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving upcoming events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy events quá hạn (Common)
     */
    public function getOverdueEvents(Request $request): JsonResponse
    {
        try {
            $events = $this->calendarService->getOverdueEvents();
            
            return response()->json([
                'success' => true,
                'data' => $events,
                'message' => 'Overdue events retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving overdue events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy số lượng events theo trạng thái (Common)
     */
    public function getEventsCountByStatus(Request $request): JsonResponse
    {
        try {
            $counts = $this->calendarService->getEventsCountByStatus();
            
            return response()->json([
                'success' => true,
                'data' => $counts,
                'message' => 'Events count retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving events count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy reminders (Common)
     */
    public function getReminders(Request $request): JsonResponse
    {
        try {
            $reminders = $this->calendarService->getReminders();
            
            return response()->json([
                'success' => true,
                'data' => $reminders,
                'message' => 'Reminders retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving reminders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo reminder (Common)
     */
    public function setReminder(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $reminder = $this->calendarService->setReminder($data);
            
            return response()->json([
                'success' => true,
                'data' => $reminder,
                'message' => 'Reminder set successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error setting reminder: ' . $e->getMessage()
            ], 500);
        }
    }
}
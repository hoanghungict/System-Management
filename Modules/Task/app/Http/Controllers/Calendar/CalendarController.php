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
     * Hỗ trợ cả `start`/`end` và `start_date`/`end_date`
     */
    public function getEventsByRange(Request $request): JsonResponse
    {
        try {
            // Hỗ trợ cả 2 format: start/end (cho admin) và start_date/end_date (cho student)
            $startDate = $request->input('start') ?? $request->input('start_date', now()->format('Y-m-d'));
            $endDate = $request->input('end') ?? $request->input('end_date', now()->addDays(30)->format('Y-m-d'));
            
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

    /**
     * Lấy tất cả events (Admin only)
     */
    public function getAllEvents(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', 15);
            
            $events = $this->calendarService->getAllEvents($page, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $events,
                'message' => 'All calendar events retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving all events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy events theo loại (Admin only)
     */
    public function getEventsByType(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type');
            
            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type parameter is required'
                ], 422);
            }
            
            $events = $this->calendarService->getEventsByType($type);
            
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
     * Lấy recurring events (Admin only)
     */
    public function getRecurringEvents(Request $request): JsonResponse
    {
        try {
            $events = $this->calendarService->getRecurringEvents();
            
            return response()->json([
                'success' => true,
                'data' => $events,
                'message' => 'Recurring events retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving recurring events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo calendar event mới (Admin/Lecturer)
     */
    public function createEvent(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type') ?? 'admin';
            
            $data = $request->all();
            $data['creator_id'] = $userId;
            $data['creator_type'] = $userType;
            
            $event = $this->calendarService->createEvent($data);
            
            return response()->json([
                'success' => true,
                'data' => $event,
                'message' => 'Event created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating event: ' . $e->getMessage()
            ], 500);
        }
    }
}
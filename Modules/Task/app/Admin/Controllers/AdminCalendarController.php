<?php

namespace Modules\Task\app\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Task\app\Services\CalendarService;

/**
 * Admin Calendar Controller - Admin-specific calendar operations
 * Tuân thủ Clean Architecture: Admin-specific business logic
 */
class AdminCalendarController extends Controller
{
    public function __construct(
        private CalendarService $calendarService
    ) {}

    /**
     * Lấy tất cả events (Admin only)
     */
    public function getAllEvents(Request $request): JsonResponse
    {
        try {
            $events = $this->calendarService->getAllEvents();
            
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
}

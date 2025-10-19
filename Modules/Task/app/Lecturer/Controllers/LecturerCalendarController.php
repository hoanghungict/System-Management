<?php

namespace Modules\Task\app\Lecturer\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Task\app\Lecturer\UseCases\LecturerCalendarUseCase;

/**
 * Lecturer Calendar Controller
 * 
 * Controller dành riêng cho Giảng viên để quản lý lịch
 * Tuân theo Clean Architecture với Use Cases
 */
class LecturerCalendarController extends Controller
{
    protected $lecturerCalendarUseCase;

    public function __construct(LecturerCalendarUseCase $lecturerCalendarUseCase)
    {
        $this->lecturerCalendarUseCase = $lecturerCalendarUseCase;
    }

    /**
     * Lấy events của giảng viên
     */
    public function getLecturerEvents(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $events = $this->lecturerCalendarUseCase->getLecturerEvents($lecturerId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Lecturer events retrieved successfully',
                'data' => $events['data'],
                'pagination' => $events['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lecturer events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo event mới
     */
    public function createEvent(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            $data['creator_id'] = $lecturerId;
            $data['creator_type'] = 'lecturer';
            
            $event = $this->lecturerCalendarUseCase->createEvent($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => $event
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật event
     */
    public function updateEvent(Request $request, $eventId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            
            $event = $this->lecturerCalendarUseCase->updateEvent($eventId, $data, $lecturerId, 'lecturer');
            
            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $event
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa event
     */
    public function deleteEvent(Request $request, $eventId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $this->lecturerCalendarUseCase->deleteEvent($eventId, $lecturerId, 'lecturer');
            
            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy events theo ngày
     */
    public function getEventsByDate(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $date = $request->get('date');
            $events = $this->lecturerCalendarUseCase->getEventsByDate($lecturerId, $date);
            
            return response()->json([
                'success' => true,
                'message' => 'Events by date retrieved successfully',
                'data' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve events by date: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy events theo khoảng thời gian
     */
    public function getEventsByRange(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $startDate = $request->get('start');
            $endDate = $request->get('end');
            $events = $this->lecturerCalendarUseCase->getEventsByRange($lecturerId, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'message' => 'Events by range retrieved successfully',
                'data' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve events by range: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy events sắp tới
     */
    public function getUpcomingEvents(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $limit = $request->get('limit', 10);
            $events = $this->lecturerCalendarUseCase->getUpcomingEvents($lecturerId, $limit);
            
            return response()->json([
                'success' => true,
                'message' => 'Upcoming events retrieved successfully',
                'data' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy events quá hạn
     */
    public function getOverdueEvents(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $events = $this->lecturerCalendarUseCase->getOverdueEvents($lecturerId);
            
            return response()->json([
                'success' => true,
                'message' => 'Overdue events retrieved successfully',
                'data' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve overdue events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đếm events theo status
     */
    public function getEventsCountByStatus(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $counts = $this->lecturerCalendarUseCase->getEventsCountByStatus($lecturerId);
            
            return response()->json([
                'success' => true,
                'message' => 'Events count by status retrieved successfully',
                'data' => $counts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve events count by status: ' . $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace Modules\Task\app\Student\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Task\app\Student\UseCases\StudentCalendarUseCase;

/**
 * Student Calendar Controller
 * 
 * Controller dành riêng cho Sinh viên để quản lý lịch
 * Tuân theo Clean Architecture với Use Cases
 */
class StudentCalendarController extends Controller
{
    protected $studentCalendarUseCase;

    public function __construct(StudentCalendarUseCase $studentCalendarUseCase)
    {
        $this->studentCalendarUseCase = $studentCalendarUseCase;
    }

    /**
     * Lấy events của sinh viên
     */
    public function getStudentEvents(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $events = $this->studentCalendarUseCase->getStudentEvents($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Student events retrieved successfully',
                'data' => $events['data'],
                'pagination' => $events['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy events theo ngày
     */
    public function getEventsByDate(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $date = $request->get('date');
            $events = $this->studentCalendarUseCase->getEventsByDate($studentId, $date);
            
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
            $studentId = $request->attributes->get('jwt_user_id');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $events = $this->studentCalendarUseCase->getEventsByRange($studentId, $startDate, $endDate);
            
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
            $studentId = $request->attributes->get('jwt_user_id');
            $limit = $request->get('limit', 10);
            $events = $this->studentCalendarUseCase->getUpcomingEvents($studentId, $limit);
            
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
            $studentId = $request->attributes->get('jwt_user_id');
            $events = $this->studentCalendarUseCase->getOverdueEvents($studentId);
            
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
            $studentId = $request->attributes->get('jwt_user_id');
            $counts = $this->studentCalendarUseCase->getEventsCountByStatus($studentId);
            
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

    /**
     * Lấy reminders của sinh viên
     */
    public function getReminders(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $reminders = $this->studentCalendarUseCase->getReminders($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Student reminders retrieved successfully',
                'data' => $reminders['data'],
                'pagination' => $reminders['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student reminders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set reminder cho event
     */
    public function setReminder(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            $data['user_id'] = $studentId;
            $data['user_type'] = 'student';
            
            $reminder = $this->studentCalendarUseCase->setReminder($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Reminder set successfully',
                'data' => $reminder
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set reminder: ' . $e->getMessage()
            ], 500);
        }
    }
}

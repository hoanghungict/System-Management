<?php

namespace Modules\Task\app\Student\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Task\app\Student\UseCases\StudentClassUseCase;

/**
 * Student Class Controller
 * 
 * Controller dành riêng cho Sinh viên để quản lý lớp học
 * Tuân theo Clean Architecture với Use Cases
 */
class StudentClassController extends Controller
{
    protected $studentClassUseCase;

    public function __construct(StudentClassUseCase $studentClassUseCase)
    {
        $this->studentClassUseCase = $studentClassUseCase;
    }

    /**
     * Lấy thông tin lớp học của sinh viên
     */
    public function getStudentClass(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $class = $this->studentClassUseCase->getStudentClass($studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Student class retrieved successfully',
                'data' => $class
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student class: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách sinh viên cùng lớp
     */
    public function getClassmates(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $classmates = $this->studentClassUseCase->getClassmates($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Classmates retrieved successfully',
                'data' => $classmates['data'],
                'pagination' => $classmates['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve classmates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách giảng viên của lớp
     */
    public function getClassLecturers(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $lecturers = $this->studentClassUseCase->getClassLecturers($studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Class lecturers retrieved successfully',
                'data' => $lecturers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class lecturers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông báo của lớp
     */
    public function getClassAnnouncements(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $announcements = $this->studentClassUseCase->getClassAnnouncements($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Class announcements retrieved successfully',
                'data' => $announcements['data'],
                'pagination' => $announcements['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class announcements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy lịch học của lớp
     */
    public function getClassSchedule(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $schedule = $this->studentClassUseCase->getClassSchedule($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Class schedule retrieved successfully',
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin điểm danh của sinh viên
     */
    public function getAttendance(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $attendance = $this->studentClassUseCase->getAttendance($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Student attendance retrieved successfully',
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student attendance: ' . $e->getMessage()
            ], 500);
        }
    }
}

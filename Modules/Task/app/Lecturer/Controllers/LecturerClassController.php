<?php

namespace Modules\Task\app\Lecturer\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Task\app\Lecturer\UseCases\LecturerClassUseCase;

/**
 * Lecturer Class Controller
 * 
 * Controller dành riêng cho Giảng viên để quản lý lớp học
 * Tuân theo Clean Architecture với Use Cases
 */
class LecturerClassController extends Controller
{
    protected $lecturerClassUseCase;

    public function __construct(LecturerClassUseCase $lecturerClassUseCase)
    {
        $this->lecturerClassUseCase = $lecturerClassUseCase;
    }

    /**
     * Lấy danh sách lớp học của giảng viên
     */
    public function getLecturerClasses(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $classes = $this->lecturerClassUseCase->getLecturerClasses($lecturerId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Lecturer classes retrieved successfully',
                'data' => $classes['data'],
                'pagination' => $classes['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lecturer classes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách sinh viên trong lớp
     */
    public function getClassStudents(Request $request, $classId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $students = $this->lecturerClassUseCase->getClassStudents($classId, $lecturerId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Class students retrieved successfully',
                'data' => $students['data'],
                'pagination' => $students['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo thông báo cho lớp
     */
    public function createAnnouncement(Request $request, $classId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            $data['class_id'] = $classId;
            $data['creator_id'] = $lecturerId;
            $data['creator_type'] = 'lecturer';
            
            $announcement = $this->lecturerClassUseCase->createAnnouncement($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Announcement created successfully',
                'data' => $announcement
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create announcement: ' . $e->getMessage()
            ], 500);
        }
    }
}

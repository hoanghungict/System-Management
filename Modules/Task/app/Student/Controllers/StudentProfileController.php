<?php

namespace Modules\Task\app\Student\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Task\app\Student\UseCases\StudentProfileUseCase;

/**
 * Student Profile Controller
 * 
 * Controller dành riêng cho Sinh viên để quản lý profile
 * Tuân theo Clean Architecture với Use Cases
 */
class StudentProfileController extends Controller
{
    protected $studentProfileUseCase;

    public function __construct(StudentProfileUseCase $studentProfileUseCase)
    {
        $this->studentProfileUseCase = $studentProfileUseCase;
    }

    /**
     * Lấy profile của sinh viên
     */
    public function show(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $profile = $this->studentProfileUseCase->getProfile($studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Student profile retrieved successfully',
                'data' => $profile
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật profile của sinh viên
     */
    public function update(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            
            $profile = $this->studentProfileUseCase->updateProfile($studentId, $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Student profile updated successfully',
                'data' => $profile
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin lớp học của sinh viên
     */
    public function getClassInfo(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $classInfo = $this->studentProfileUseCase->getClassInfo($studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Class information retrieved successfully',
                'data' => $classInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin điểm số của sinh viên
     */
    public function getGrades(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $grades = $this->studentProfileUseCase->getGrades($studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Student grades retrieved successfully',
                'data' => $grades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student grades: ' . $e->getMessage()
            ], 500);
        }
    }
}

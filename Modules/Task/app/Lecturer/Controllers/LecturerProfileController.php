<?php

namespace Modules\Task\app\Lecturer\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Task\app\Lecturer\UseCases\LecturerProfileUseCase;

/**
 * Lecturer Profile Controller
 * 
 * Controller dành riêng cho Giảng viên để quản lý profile
 * Tuân theo Clean Architecture với Use Cases
 */
class LecturerProfileController extends Controller
{
    protected $lecturerProfileUseCase;

    public function __construct(LecturerProfileUseCase $lecturerProfileUseCase)
    {
        $this->lecturerProfileUseCase = $lecturerProfileUseCase;
    }

    /**
     * Lấy profile của giảng viên
     */
    public function show(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $profile = $this->lecturerProfileUseCase->getProfile($lecturerId);
            
            return response()->json([
                'success' => true,
                'message' => 'Lecturer profile retrieved successfully',
                'data' => $profile
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lecturer profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật profile của giảng viên
     */
    public function update(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            
            $profile = $this->lecturerProfileUseCase->updateProfile($lecturerId, $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Lecturer profile updated successfully',
                'data' => $profile
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lecturer profile: ' . $e->getMessage()
            ], 500);
        }
    }
}

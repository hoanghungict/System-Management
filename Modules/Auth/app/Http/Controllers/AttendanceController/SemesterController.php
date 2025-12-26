<?php

namespace Modules\Auth\app\Http\Controllers\AttendanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\AttendanceService\SemesterService;
use Modules\Auth\app\Http\Requests\AttendanceRequest\SemesterRequest;
use Illuminate\Support\Facades\Log;

/**
 * Controller quản lý Học kỳ
 * 
 * @group Attendance - Semester Management
 */
class SemesterController extends Controller
{
    protected SemesterService $semesterService;

    public function __construct(SemesterService $semesterService)
    {
        $this->semesterService = $semesterService;
    }

    /**
     * Lấy danh sách học kỳ
     * 
     * @queryParam per_page int Số bản ghi mỗi trang. Default: 15
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $semesters = $this->semesterService->getAllSemesters($perPage);

            return response()->json([
                'success' => true,
                'data' => $semesters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get semesters', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách học kỳ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy chi tiết học kỳ
     */
    public function show(int $id): JsonResponse
    {
        try {
            $semester = $this->semesterService->getSemesterById($id);

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy học kỳ',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $semester,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get semester', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo học kỳ mới
     */
    public function store(SemesterRequest $request): JsonResponse
    {
        try {
            $semester = $this->semesterService->createSemester($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tạo học kỳ thành công',
                'data' => $semester,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create semester', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo học kỳ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật học kỳ
     */
    public function update(SemesterRequest $request, int $id): JsonResponse
    {
        try {
            $success = $this->semesterService->updateSemester($id, $request->validated());

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy học kỳ hoặc cập nhật thất bại',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật học kỳ thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update semester', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật học kỳ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa học kỳ
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $success = $this->semesterService->deleteSemester($id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy học kỳ',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Xóa học kỳ thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete semester', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Kích hoạt học kỳ
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $success = $this->semesterService->activateSemester($id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy học kỳ',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Kích hoạt học kỳ thành công',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to activate semester', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy học kỳ đang hoạt động
     */
    public function getActive(): JsonResponse
    {
        try {
            $semester = $this->semesterService->getActiveSemester();

            return response()->json([
                'success' => true,
                'data' => $semester,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get active semester', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

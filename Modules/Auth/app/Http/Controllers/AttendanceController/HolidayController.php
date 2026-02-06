<?php

namespace Modules\Auth\app\Http\Controllers\AttendanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Models\Attendance\Holiday;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller quản lý ngày nghỉ lễ
 * 
 * @group Attendance - Holiday Management
 */
class HolidayController extends Controller
{
    /**
     * Lấy danh sách ngày lễ
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', now()->year);
            
            // Lấy ngày lễ trong năm + các ngày lễ lặp lại
            $holidays = Holiday::whereYear('date', $year)
                ->orWhere('is_recurring', true)
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $holidays,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo ngày lễ mới
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'is_recurring' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $holiday = Holiday::create($validator->validated());

            /* Log::info('Admin created holiday', [
                'admin_id' => $request->user()->id ?? 'unknown',
                'holiday_id' => $holiday->id,
                'name' => $holiday->name
            ]); */

            return response()->json([
                'success' => true,
                'message' => 'Tạo ngày lễ thành công',
                'data' => $holiday,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo ngày lễ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật ngày lễ
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ngày lễ',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'description' => 'nullable|string',
            'is_recurring' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $holiday->update($validator->validated());

            /* Log::info('Admin updated holiday', [
                'admin_id' => $request->user()->id ?? 'unknown',
                'holiday_id' => $holiday->id,
                'changes' => $holiday->getChanges()
            ]); */

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật ngày lễ thành công',
                'data' => $holiday->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa ngày lễ
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ngày lễ',
            ], 404);
        }

        try {
            $holiday->delete();

            /* Log::info('Admin deleted holiday', [
                'admin_id' => $request->user()->id ?? 'unknown',
                'holiday_id' => $id,
                'name' => $holiday->name
            ]); */

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa ngày lễ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa: ' . $e->getMessage(),
            ], 500);
        }
    }
}

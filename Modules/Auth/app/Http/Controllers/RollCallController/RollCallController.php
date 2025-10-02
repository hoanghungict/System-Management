<?php

namespace Modules\Auth\app\Http\Controllers\RollCallController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\RollCallService\RollCallService;
use Modules\Auth\app\Http\Requests\RollCallRequest\CreateRollCallRequest;
use Modules\Auth\app\Http\Requests\RollCallRequest\UpdateRollCallStatusRequest;
use Modules\Auth\app\Http\Requests\RollCallRequest\BulkUpdateRollCallStatusRequest;
use Modules\Auth\app\Models\Classroom;
use Illuminate\Support\Facades\Log;
use Modules\Auth\app\Models\Student;

class RollCallController extends Controller
{
    protected $rollCallService;

    public function __construct(RollCallService $rollCallService)
    {
        $this->rollCallService = $rollCallService;
    }

    /**
     * Lấy danh sách lớp học để tạo điểm danh
     */
    public function getClassrooms(): JsonResponse
    {
        try {
            $classrooms = $this->rollCallService->getClassrooms();
            
            return response()->json([
                'success' => true,
                'data' => $classrooms
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get classrooms', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách lớp học.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo buổi điểm danh mới
     */
    public function store(CreateRollCallRequest $request): JsonResponse
    {
        try {
            $rollCall = $this->rollCallService->createRollCall($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Tạo buổi điểm danh thành công.',
                'data' => $rollCall
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create roll call', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo buổi điểm danh.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách buổi điểm danh theo lớp
     */
    public function getRollCallsByClass(Request $request, int $classId): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $rollCalls = $this->rollCallService->getRollCallsByClass($classId, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $rollCalls
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get roll calls by class', [
                'error' => $e->getMessage(),
                'class_id' => $classId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách buổi điểm danh.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết buổi điểm danh
     */
    public function getRollCallDetails(int $id): JsonResponse
    {
        try {
            $rollCall = $this->rollCallService->getRollCallDetails($id);
            
            return response()->json([
                'success' => true,
                'data' => $rollCall
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get roll call details', [
                'error' => $e->getMessage(),
                'roll_call_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy chi tiết buổi điểm danh.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật trạng thái điểm danh của 1 sinh viên
     */
    public function updateStatus(UpdateRollCallStatusRequest $request, int $rollCallId): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $success = $this->rollCallService->updateStudentStatus(
                $rollCallId,
                $validated['student_id'],
                $validated['status'],
                $validated['note'] ?? null
            );
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật trạng thái điểm danh thành công.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cập nhật trạng thái điểm danh thất bại.'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update roll call status', [
                'error' => $e->getMessage(),
                'roll_call_id' => $rollCallId,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái điểm danh.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật trạng thái điểm danh hàng loạt
     */
    public function bulkUpdateStatus(BulkUpdateRollCallStatusRequest $request, int $rollCallId): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Chuyển đổi dữ liệu từ frontend
            $studentStatuses = [];
            foreach ($validated['student_statuses'] as $data) {
                $studentStatuses[$data['student_id']] = [
                    'status' => $data['status'],
                    'note' => $data['note'] ?? null
                ];
            }
            
            $success = $this->rollCallService->updateBulkStatus($rollCallId, $studentStatuses);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật trạng thái điểm danh hàng loạt thành công.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cập nhật trạng thái điểm danh hàng loạt thất bại.'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to bulk update roll call status', [
                'error' => $e->getMessage(),
                'roll_call_id' => $rollCallId,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái điểm danh hàng loạt.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hoàn thành buổi điểm danh
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $success = $this->rollCallService->completeRollCall($id);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hoàn thành buổi điểm danh thành công.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Hoàn thành buổi điểm danh thất bại.'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to complete roll call', [
                'error' => $e->getMessage(),
                'roll_call_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi hoàn thành buổi điểm danh.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hủy buổi điểm danh
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $success = $this->rollCallService->cancelRollCall($id);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hủy buổi điểm danh thành công.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Hủy buổi điểm danh thất bại.'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to cancel roll call', [
                'error' => $e->getMessage(),
                'roll_call_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi hủy buổi điểm danh.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê điểm danh theo lớp
     */
    public function statistics(Request $request, int $classId): JsonResponse
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            
            $stats = $this->rollCallService->getRollCallStatistics($classId, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get roll call statistics', [
                'error' => $e->getMessage(),
                'class_id' => $classId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thống kê điểm danh.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách sinh viên trong lớp để điểm danh
     */
    public function getStudentsForRollCall(int $classId): JsonResponse
    {
        try {
            $students = Student::where('class_id', $classId)
                ->with('account')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $students
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get students for roll call', [
                'error' => $e->getMessage(),
                'class_id' => $classId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sinh viên.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy tất cả sinh viên để chọn cho manual roll call
     */
    public function getAllStudents(): JsonResponse
    {
        try {
            $students = $this->rollCallService->getAllStudentsForSelection();
            
            return response()->json([
                'success' => true,
                'data' => $students,
                'message' => 'Lấy danh sách sinh viên thành công.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get all students', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sinh viên.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thêm sinh viên vào buổi điểm danh manual
     */
    public function addParticipants(Request $request, int $rollCallId): JsonResponse
    {
        try {
            $request->validate([
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'integer|exists:student,id'
            ]);
            
            $success = $this->rollCallService->addParticipants($rollCallId, $request->student_ids);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thêm sinh viên vào buổi điểm danh thành công.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Thêm sinh viên vào buổi điểm danh thất bại.'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to add participants', [
                'error' => $e->getMessage(),
                'roll_call_id' => $rollCallId,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm sinh viên.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Xóa sinh viên khỏi buổi điểm danh manual
     */
    public function removeParticipant(int $rollCallId, int $studentId): JsonResponse
    {
        try {
            $success = $this->rollCallService->removeParticipant($rollCallId, $studentId);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Xóa sinh viên khỏi buổi điểm danh thành công.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Xóa sinh viên khỏi buổi điểm danh thất bại.'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to remove participant', [
                'error' => $e->getMessage(),
                'roll_call_id' => $rollCallId,
                'student_id' => $studentId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa sinh viên.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace Modules\Auth\app\Services\RollCallService;

use Modules\Auth\app\Models\RollCall;
use Modules\Auth\app\Models\RollCallDetail;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Classroom;
use Modules\Auth\app\Repositories\Interfaces\RollCallRepositoryInterface;
use Modules\Auth\app\Repositories\Interfaces\RollCallDetailRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RollCallService
{
    protected $rollCallRepository;
    protected $rollCallDetailRepository;

    public function __construct(
        RollCallRepositoryInterface $rollCallRepository,
        RollCallDetailRepositoryInterface $rollCallDetailRepository
    ) {
        $this->rollCallRepository = $rollCallRepository;
        $this->rollCallDetailRepository = $rollCallDetailRepository;
    }

    /**
     * Lấy danh sách lớp học
     */
    public function getClassrooms()
    {
        $cacheKey = 'classrooms:with_students';

        return Cache::remember($cacheKey, 1800, function () {
            return Classroom::with('students')->get();
        });
    }

    /**
     * Tạo buổi điểm danh mới cho lớp
     */
    public function createRollCall(array $data): RollCall
    {
        DB::beginTransaction();

        try {
            // Tạo buổi điểm danh
            $rollCall = $this->rollCallRepository->create($data);

            // Lấy danh sách sinh viên trong lớp
            $students = Student::where('class_id', $data['class_id'])->get();

            // Tạo chi tiết điểm danh cho từng sinh viên
            foreach ($students as $student) {
                $this->rollCallDetailRepository->create([
                    'roll_call_id' => $rollCall->id,
                    'student_id' => $student->id,
                    'status' => 'Vắng Mặt', // Mặc định là vắng mặt
                    'checked_at' => null
                ]);
            }

            // Xóa cache liên quan
            $this->clearRollCallCache($data['class_id']);

            DB::commit();

            Log::info('Roll call created successfully', [
                'roll_call_id' => $rollCall->id,
                'class_id' => $data['class_id'],
                'students_count' => $students->count()
            ]);

            return $this->rollCallRepository->findById($rollCall->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create roll call', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Lấy danh sách buổi điểm danh theo lớp
     */
    public function getRollCallsByClass(int $classId, int $perPage = 15)
    {
        $cacheKey = "roll_calls:class:{$classId}:page:{$perPage}";

        return Cache::remember($cacheKey, 1800, function () use ($classId, $perPage) {
            return $this->rollCallRepository->getByClass($classId, $perPage);
        });
    }

    /**
     * Lấy chi tiết buổi điểm danh
     */
    public function getRollCallDetails(int $rollCallId)
    {
        $cacheKey = "roll_call_details:{$rollCallId}";

        return Cache::remember($cacheKey, 1800, function () use ($rollCallId) {
            return $this->rollCallRepository->findById($rollCallId);
        });
    }

    /**
     * Cập nhật trạng thái điểm danh của sinh viên
     */
    public function updateStudentStatus(int $rollCallId, int $studentId, string $status, string $note = null): bool
    {
        try {
            $success = $this->rollCallDetailRepository->updateByStudentAndRollCall(
                $studentId,
                $rollCallId,
                [
                    'status' => $status,
                    'note' => $note,
                    'checked_at' => now()
                ]
            );

            if ($success) {
                // Lấy thông tin buổi điểm danh để xóa cache
                $rollCall = $this->rollCallRepository->findById($rollCallId);
                if ($rollCall) {
                    $this->clearRollCallCache($rollCall->class_id);
                }
                Cache::forget("roll_call_details:{$rollCallId}");

                Log::info('Student roll call status updated', [
                    'roll_call_id' => $rollCallId,
                    'student_id' => $studentId,
                    'status' => $status
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to update student roll call status', [
                'roll_call_id' => $rollCallId,
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Cập nhật trạng thái hàng loạt
     */
    public function updateBulkStatus(int $rollCallId, array $studentStatuses): bool
    {
        DB::beginTransaction();

        try {
            foreach ($studentStatuses as $studentId => $data) {
                $this->updateStudentStatus(
                    $rollCallId,
                    $studentId,
                    $data['status'],
                    $data['note'] ?? null
                );
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update bulk roll call status', [
                'roll_call_id' => $rollCallId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Hoàn thành buổi điểm danh
     */
    public function completeRollCall(int $rollCallId): bool
    {
        try {
            $success = $this->rollCallRepository->update($rollCallId, ['status' => 'completed']);

            if ($success) {
                $rollCall = $this->rollCallRepository->findById($rollCallId);
                if ($rollCall) {
                    $this->clearRollCallCache($rollCall->class_id);
                }

                Log::info('Roll call completed', ['roll_call_id' => $rollCallId]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to complete roll call', [
                'roll_call_id' => $rollCallId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Hủy buổi điểm danh
     */
    public function cancelRollCall(int $rollCallId): bool
    {
        try {
            $success = $this->rollCallRepository->update($rollCallId, ['status' => 'cancelled']);

            if ($success) {
                $rollCall = $this->rollCallRepository->findById($rollCallId);
                if ($rollCall) {
                    $this->clearRollCallCache($rollCall->class_id);
                }

                Log::info('Roll call cancelled', ['roll_call_id' => $rollCallId]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to cancel roll call', [
                'roll_call_id' => $rollCallId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy thống kê điểm danh theo lớp
     */
    public function getRollCallStatistics(int $classId, $startDate = null, $endDate = null)
    {
        $cacheKey = "roll_call_stats:class:{$classId}:" . ($startDate ?? 'all') . ':' . ($endDate ?? 'all');

        return Cache::remember($cacheKey, 3600, function () use ($classId, $startDate, $endDate) {
            return $this->rollCallRepository->getStatisticsByClass($classId, $startDate, $endDate);
        });
    }

    /**
     * Xóa cache điểm danh
     */
    private function clearRollCallCache(int $classId): void
    {
        // Xóa cache danh sách buổi điểm danh
        $keys = [
            "roll_calls:class:{$classId}:page:*",
            "roll_call_stats:class:{$classId}:*"
        ];

        foreach ($keys as $pattern) {
            // Nếu dùng Redis, có thể dùng pattern matching
            // Ở đây ta xóa cache cơ bản
            Cache::forget("roll_calls:class:{$classId}");
        }
    }
}

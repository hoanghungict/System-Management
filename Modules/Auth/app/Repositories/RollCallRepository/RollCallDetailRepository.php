<?php

namespace Modules\Auth\app\Repositories\RollCallRepository;

use Modules\Auth\app\Models\RollCallDetail;
use Modules\Auth\app\Repositories\Interfaces\RollCallDetailRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RollCallDetailRepository implements RollCallDetailRepositoryInterface
{
    protected $model;

    public function __construct(RollCallDetail $model)
    {
        $this->model = $model;
    }

    /**
     * Tạo chi tiết điểm danh
     */
    public function create(array $data): RollCallDetail
    {
        return $this->model->create($data);
    }

    /**
     * Lấy chi tiết điểm danh theo ID
     */
    public function findById(int $id): ?RollCallDetail
    {
        return $this->model->with(['rollCall', 'student'])->find($id);
    }

    /**
     * Lấy chi tiết điểm danh theo buổi điểm danh
     */
    public function getByRollCall(int $rollCallId): Collection
    {
        return $this->model->with(['student.account'])
            ->where('roll_call_id', $rollCallId)
            ->orderBy('student_id')
            ->get();
    }

    /**
     * Lấy chi tiết điểm danh theo sinh viên và buổi điểm danh
     */
    public function getByStudentAndRollCall(int $studentId, int $rollCallId): ?RollCallDetail
    {
        return $this->model->with(['student', 'rollCall'])
            ->where('student_id', $studentId)
            ->where('roll_call_id', $rollCallId)
            ->first();
    }

    /**
     * Cập nhật chi tiết điểm danh
     */
    public function update(int $id, array $data): bool
    {
        $detail = $this->model->find($id);
        if (!$detail) {
            return false;
        }
        return $detail->update($data);
    }

    /**
     * Cập nhật chi tiết điểm danh theo sinh viên và buổi điểm danh
     */
    public function updateByStudentAndRollCall(int $studentId, int $rollCallId, array $data): bool
    {
        $detail = $this->getByStudentAndRollCall($studentId, $rollCallId);
        if (!$detail) {
            return false;
        }
        return $detail->update($data);
    }

    /**
     * Xóa chi tiết điểm danh
     */
    public function delete(int $id): bool
    {
        $detail = $this->model->find($id);
        if (!$detail) {
            return false;
        }
        return $detail->delete();
    }

    /**
     * Xóa tất cả chi tiết điểm danh theo buổi điểm danh
     */
    public function deleteByRollCall(int $rollCallId): bool
    {
        return $this->model->where('roll_call_id', $rollCallId)->delete() > 0;
    }

    /**
     * Lấy chi tiết điểm danh theo trạng thái
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->with(['student', 'rollCall'])
            ->byStatus($status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy chi tiết điểm danh đã điểm danh
     */
    public function getChecked(): Collection
    {
        return $this->model->with(['student', 'rollCall'])
            ->checked()
            ->orderBy('checked_at', 'desc')
            ->get();
    }

    /**
     * Lấy chi tiết điểm danh chưa điểm danh
     */
    public function getUnchecked(): Collection
    {
        return $this->model->with(['student', 'rollCall'])
            ->unchecked()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Đếm số sinh viên theo trạng thái trong buổi điểm danh
     */
    public function countByStatusInRollCall(int $rollCallId, string $status): int
    {
        return $this->model->where('roll_call_id', $rollCallId)
            ->where('status', $status)
            ->count();
    }

    /**
     * Lấy thống kê điểm danh của sinh viên
     */
    public function getStudentStatistics(int $studentId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->model->where('student_id', $studentId)
            ->with('rollCall');
        
        if ($startDate) {
            $query->whereHas('rollCall', function($q) use ($startDate) {
                $q->whereDate('date', '>=', $startDate);
            });
        }
        
        if ($endDate) {
            $query->whereHas('rollCall', function($q) use ($endDate) {
                $q->whereDate('date', '<=', $endDate);
            });
        }
        
        $details = $query->get();
        
        $stats = [
            'total_roll_calls' => $details->count(),
            'attendance_rate' => 0,
            'status_breakdown' => [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0
            ]
        ];
        
        if ($details->count() > 0) {
            $presentCount = 0;
            
            foreach ($details as $detail) {
                $stats['status_breakdown'][$detail->status]++;
                
                if (in_array($detail->status, ['present', 'late'])) {
                    $presentCount++;
                }
            }
            
            $stats['attendance_rate'] = round(($presentCount / $details->count()) * 100, 2);
        }
        
        return $stats;
    }
}
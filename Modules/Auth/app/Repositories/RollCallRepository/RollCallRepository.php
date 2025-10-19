<?php

namespace Modules\Auth\app\Repositories\RollCallRepository;

use Modules\Auth\app\Models\RollCall;
use Modules\Auth\app\Repositories\Interfaces\RollCallRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class RollCallRepository implements RollCallRepositoryInterface
{
    protected $model;

    public function __construct(RollCall $model)
    {
        $this->model = $model;
    }

    /**
     * Tạo buổi điểm danh mới
     */
    public function create(array $data): RollCall
    {
        return $this->model->create($data);
    }

    /**
     * Lấy buổi điểm danh theo ID
     */
    public function findById(int $id): ?RollCall
    {
        return $this->model->with(['class', 'creator', 'rollCallDetails.student'])->find($id);
    }

    /**
     * Lấy danh sách buổi điểm danh theo lớp
     */
    public function getByClass(int $classId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['class', 'creator'])
            ->byClass($classId)
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Lấy danh sách buổi điểm danh theo lớp (không phân trang)
     */
    public function getByClassAll(int $classId): Collection
    {
        return $this->model->with(['class', 'creator'])
            ->byClass($classId)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Cập nhật buổi điểm danh
     */
    public function update(int $id, array $data): bool
    {
        $rollCall = $this->model->find($id);
        if (!$rollCall) {
            return false;
        }
        return $rollCall->update($data);
    }

    /**
     * Xóa buổi điểm danh
     */
    public function delete(int $id): bool
    {
        $rollCall = $this->model->find($id);
        if (!$rollCall) {
            return false;
        }
        return $rollCall->delete();
    }

    /**
     * Lấy buổi điểm danh theo ngày
     */
    public function getByDate(string $date): Collection
    {
        return $this->model->with(['class', 'creator'])
            ->byDate($date)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Lấy buổi điểm danh theo trạng thái
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->with(['class', 'creator'])
            ->where('status', $status)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Lấy buổi điểm danh theo lớp và ngày
     */
    public function getByClassAndDate(int $classId, string $date): Collection
    {
        return $this->model->with(['class', 'creator'])
            ->byClass($classId)
            ->byDate($date)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Lấy thống kê điểm danh theo lớp
     */
    public function getStatisticsByClass(int $classId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->model->byClass($classId)->with('rollCallDetails');
        
        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }
        
        $rollCalls = $query->get();
        
        $stats = [
            'total_roll_calls' => $rollCalls->count(),
            'total_students' => \Modules\Auth\app\Models\Student::where('class_id', $classId)->count(),
            'attendance_rate' => 0,
            'status_breakdown' => [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0
            ]
        ];
        
        if ($rollCalls->count() > 0) {
            $totalDetails = 0;
            $presentCount = 0;
            
            foreach ($rollCalls as $rollCall) {
                foreach ($rollCall->rollCallDetails as $detail) {
                    $totalDetails++;
                    $stats['status_breakdown'][$detail->status]++;
                    
                    if (in_array($detail->status, ['present', 'late'])) {
                        $presentCount++;
                    }
                }
            }
            
            if ($totalDetails > 0) {
                $stats['attendance_rate'] = round(($presentCount / $totalDetails) * 100, 2);
            }
        }
        
        return $stats;
    }

    /**
     * Đếm số buổi điểm danh theo lớp
     */
    public function countByClass(int $classId): int
    {
        return $this->model->byClass($classId)->count();
    }

    /**
     * Lấy buổi điểm danh gần nhất của lớp
     */
    public function getLatestByClass(int $classId): ?RollCall
    {
        return $this->model->with(['class', 'creator'])
            ->byClass($classId)
            ->orderBy('date', 'desc')
            ->first();
    }

    /**
     * Lấy model instance
     */
    public function getModel()
    {
        return $this->model;
    }
}

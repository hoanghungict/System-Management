<?php

namespace Modules\Auth\app\Repositories\AttendanceRepository;

use Modules\Auth\app\Models\Attendance\AttendanceSession;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository cho AttendanceSession (Buổi học)
 */
class AttendanceSessionRepository
{
    protected AttendanceSession $model;

    public function __construct(AttendanceSession $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy tất cả buổi học của môn
     */
    public function getByCourse(int $courseId): Collection
    {
        return $this->model->where('course_id', $courseId)
            ->orderBy('session_number')
            ->get();
    }

    /**
     * Lấy buổi học với phân trang
     */
    public function paginateByCourse(int $courseId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['attendances', 'markedByLecturer'])
            ->where('course_id', $courseId)
            ->orderBy('session_number')
            ->paginate($perPage);
    }

    /**
     * Tìm theo ID
     */
    public function findById(int $id): ?AttendanceSession
    {
        return $this->model->with(['course', 'attendances.student', 'markedByLecturer'])
            ->find($id);
    }

    /**
     * Tạo mới
     */
    public function create(array $data): AttendanceSession
    {
        return $this->model->create($data);
    }

    /**
     * Tạo nhiều buổi học
     */
    public function createMany(array $sessions): bool
    {
        return $this->model->insert($sessions);
    }

    /**
     * Cập nhật
     */
    public function update(int $id, array $data): bool
    {
        $session = $this->model->find($id);
        if (!$session) {
            return false;
        }
        return $session->update($data);
    }

    /**
     * Xóa
     */
    public function delete(int $id): bool
    {
        $session = $this->model->find($id);
        if (!$session) {
            return false;
        }
        return $session->delete();
    }

    /**
     * Xóa tất cả buổi học của môn
     */
    public function deleteByCourse(int $courseId): int
    {
        return $this->model->where('course_id', $courseId)->delete();
    }

    /**
     * Lấy buổi học theo trạng thái
     */
    public function getByStatus(int $courseId, string $status): Collection
    {
        return $this->model->where('course_id', $courseId)
            ->where('status', $status)
            ->orderBy('session_number')
            ->get();
    }

    /**
     * Lấy buổi học tiếp theo cần điểm danh
     */
    public function getNextScheduledSession(int $courseId): ?AttendanceSession
    {
        return $this->model->where('course_id', $courseId)
            ->where('status', 'scheduled')
            ->where('session_date', '>=', now()->toDateString())
            ->orderBy('session_number')
            ->first();
    }

    /**
     * Lấy buổi học hôm nay
     */
    public function getTodaySessions(int $courseId): Collection
    {
        return $this->model->where('course_id', $courseId)
            ->whereDate('session_date', now()->toDateString())
            ->get();
    }

    /**
     * Đếm số buổi theo trạng thái
     */
    public function countByStatus(int $courseId, string $status): int
    {
        return $this->model->where('course_id', $courseId)
            ->where('status', $status)
            ->count();
    }

    /**
     * Lấy số buổi tiếp theo (cho việc tạo mới)
     */
    public function getNextSessionNumber(int $courseId): int
    {
        $maxNumber = $this->model->where('course_id', $courseId)->max('session_number');
        return ($maxNumber ?? 0) + 1;
    }

    /**
     * Kiểm tra buổi học đã tồn tại trong ngày
     */
    public function existsOnDate(int $courseId, string $date): bool
    {
        return $this->model->where('course_id', $courseId)
            ->whereDate('session_date', $date)
            ->exists();
    }
}

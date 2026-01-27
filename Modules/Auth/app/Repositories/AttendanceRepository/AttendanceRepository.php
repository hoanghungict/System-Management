<?php

namespace Modules\Auth\app\Repositories\AttendanceRepository;

use Modules\Auth\app\Models\Attendance\Attendance;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository cho Attendance (Chi tiết điểm danh)
 */
class AttendanceRepository
{
    protected Attendance $model;

    public function __construct(Attendance $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy tất cả điểm danh của buổi học
     */
    public function getBySession(int $sessionId): Collection
    {
        return $this->model->with(['student'])
            ->where('session_id', $sessionId)
            ->get();
    }

    /**
     * Tìm theo ID
     */
    public function findById(int $id): ?Attendance
    {
        return $this->model->with(['session', 'student', 'markedByLecturer'])
            ->find($id);
    }

    /**
     * Tìm theo buổi học và sinh viên
     */
    public function findBySessionAndStudent(int $sessionId, int $studentId): ?Attendance
    {
        return $this->model->where('session_id', $sessionId)
            ->where('student_id', $studentId)
            ->first();
    }

    /**
     * Tạo mới
     */
    public function create(array $data): Attendance
    {
        return $this->model->create($data);
    }

    /**
     * Tạo nhiều bản ghi
     */
    public function createMany(array $attendances): bool
    {
        if (empty($attendances)) {
            return false;
        }
        
        try {
            $result = $this->model->insert($attendances);
            
            \Log::info('AttendanceRepository::createMany', [
                'count' => count($attendances),
                'result' => $result,
                'sample_data' => $attendances[0] ?? null,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error in AttendanceRepository::createMany', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'count' => count($attendances),
                'sample_data' => $attendances[0] ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Cập nhật
     */
    public function update(int $id, array $data): bool
    {
        $attendance = $this->model->find($id);
        if (!$attendance) {
            return false;
        }
        return $attendance->update($data);
    }

    /**
     * Cập nhật theo buổi học và sinh viên
     */
    public function updateBySessionAndStudent(int $sessionId, int $studentId, array $data): bool
    {
        return $this->model->where('session_id', $sessionId)
            ->where('student_id', $studentId)
            ->update($data);
    }

    /**
     * Cập nhật hàng loạt theo buổi học
     */
    public function bulkUpdateBySession(int $sessionId, array $studentStatuses): int
    {
        $updated = 0;
        foreach ($studentStatuses as $studentId => $data) {
            if ($this->updateBySessionAndStudent($sessionId, $studentId, $data)) {
                $updated++;
            }
        }
        return $updated;
    }

    /**
     * Xóa
     */
    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    /**
     * Xóa theo buổi học
     */
    public function deleteBySession(int $sessionId): int
    {
        return $this->model->where('session_id', $sessionId)->delete();
    }

    /**
     * Lấy lịch sử điểm danh của sinh viên trong môn
     */
    public function getStudentAttendanceInCourse(int $studentId, int $courseId): Collection
    {
        return $this->model->with(['session'])
            ->where('student_id', $studentId)
            ->whereHas('session', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })
            ->get();
    }

    /**
     * Lấy tất cả điểm danh của môn học
     */
    public function getByCourseId(int $courseId): Collection
    {
        return $this->model->with(['session', 'student'])
            ->whereHas('session', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })
            ->get();
    }

    /**
     * Đếm số buổi vắng của sinh viên trong môn
     */
    public function countAbsentByStudentAndCourse(int $studentId, int $courseId): int
    {
        return $this->model->where('student_id', $studentId)
            ->where('status', Attendance::STATUS_ABSENT)
            ->whereHas('session', function ($q) use ($courseId) {
                $q->where('course_id', $courseId)
                  ->where('status', 'completed'); // Chỉ tính những buổi đã hoàn thành
            })
            ->count();
    }

    /**
     * Thống kê điểm danh của sinh viên trong môn
     */
    public function getStudentStatsInCourse(int $studentId, int $courseId): array
    {
        $attendances = $this->getStudentAttendanceInCourse($studentId, $courseId);
        
        return [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', Attendance::STATUS_PRESENT)->count(),
            'absent' => $attendances->where('status', Attendance::STATUS_ABSENT)->count(),
            'late' => $attendances->where('status', Attendance::STATUS_LATE)->count(),
            'excused' => $attendances->where('status', Attendance::STATUS_EXCUSED)->count(),
            'not_marked' => $attendances->where('status', Attendance::STATUS_NOT_MARKED)->count(),
        ];
    }

    /**
     * Đánh dấu tất cả là có mặt
     */
    public function markAllPresent(int $sessionId, int $lecturerId): int
    {
        return $this->model->where('session_id', $sessionId)
            ->where('status', Attendance::STATUS_NOT_MARKED)
            ->update([
                'status' => Attendance::STATUS_PRESENT,
                'marked_by' => $lecturerId,
                'marked_at' => now(),
            ]);
    }

    /**
     * Đánh dấu excused cho các buổi trước ngày chỉ định (cho SV đăng ký muộn)
     */
    public function markExcusedBeforeDate(int $studentId, int $courseId, string $date, int $lecturerId): int
    {
        return $this->model->where('student_id', $studentId)
            ->whereHas('session', function ($q) use ($courseId, $date) {
                $q->where('course_id', $courseId)
                  ->where('session_date', '<', $date);
            })
            ->update([
                'status' => Attendance::STATUS_EXCUSED,
                'note' => 'Đăng ký muộn - tự động pass',
                'marked_by' => $lecturerId,
                'marked_at' => now(),
            ]);
    }
}

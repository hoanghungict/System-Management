<?php

namespace Modules\Auth\app\Repositories\AttendanceRepository;

use Modules\Auth\app\Models\Attendance\Course;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository cho Course (Môn học)
 */
class CourseRepository
{
    protected Course $model;

    public function __construct(Course $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy tất cả môn học
     */
    public function all(): Collection
    {
        return $this->model->with(['semester', 'lecturer', 'department'])
            ->withCount('enrollments as students_count')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy môn học với phân trang và filter
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['semester', 'lecturer', 'department'])
            ->withCount('enrollments as students_count');

        // Filter theo học kỳ
        if (!empty($filters['semester_id'])) {
            $query->where('semester_id', $filters['semester_id']);
        }

        // Filter theo giảng viên
        if (!empty($filters['lecturer_id'])) {
            $query->where('lecturer_id', $filters['lecturer_id']);
        }

        // Filter theo trạng thái
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter theo khoa
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // Tìm kiếm
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Tìm theo ID
     */
    public function findById(int $id): ?Course
    {
        return $this->model->with(['semester', 'lecturer', 'department', 'sessions'])
            ->withCount('enrollments as students_count')
            ->find($id);
    }

    /**
     * Tìm theo code và học kỳ
     */
    public function findByCodeAndSemester(string $code, int $semesterId): ?Course
    {
        return $this->model->where('code', $code)
            ->where('semester_id', $semesterId)
            ->first();
    }

    /**
     * Tạo mới
     */
    public function create(array $data): Course
    {
        return $this->model->create($data);
    }

    /**
     * Cập nhật
     */
    public function update(int $id, array $data): bool
    {
        $course = $this->model->find($id);
        if (!$course) {
            return false;
        }
        return $course->update($data);
    }

    /**
     * Xóa (soft delete)
     */
    public function delete(int $id): bool
    {
        $course = $this->model->find($id);
        if (!$course) {
            return false;
        }
        return $course->delete();
    }

    /**
     * Lấy môn học theo giảng viên
     */
    public function getByLecturer(int $lecturerId, ?int $semesterId = null): Collection
    {
        $query = $this->model->with(['semester'])
            ->where('lecturer_id', $lecturerId);
        
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Lấy môn học theo học kỳ
     */
    public function getBySemester(int $semesterId): Collection
    {
        return $this->model->with(['semester', 'lecturer', 'department'])
            ->withCount('enrollments as students_count')
            ->where('semester_id', $semesterId)
            ->orderBy('name')
            ->get();
    }


    /**
     * Lấy môn học đang hoạt động của GV trong học kỳ hiện tại
     */
    public function getActiveCoursesByLecturer(int $lecturerId): Collection
    {
        return $this->model->with(['semester', 'sessions'])
            ->where('lecturer_id', $lecturerId)
            ->where('status', 'active')
            ->whereHas('semester', function ($q) {
                $q->where('is_active', true);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Đếm số sinh viên trong môn
     */
    public function countStudents(int $courseId): int
    {
        $course = $this->model->find($courseId);
        if (!$course) {
            return 0;
        }
        return $course->enrollments()->where('status', 'active')->count();
    }

    /**
     * Đếm số buổi đã hoàn thành
     */
    public function countCompletedSessions(int $courseId): int
    {
        $course = $this->model->find($courseId);
        if (!$course) {
            return 0;
        }
        return $course->sessions()->where('status', 'completed')->count();
    }
}

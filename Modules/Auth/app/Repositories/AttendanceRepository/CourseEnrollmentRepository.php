<?php

namespace Modules\Auth\app\Repositories\AttendanceRepository;

use Modules\Auth\app\Models\Attendance\CourseEnrollment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository cho CourseEnrollment (Đăng ký môn học)
 */
class CourseEnrollmentRepository
{
    protected CourseEnrollment $model;

    public function __construct(CourseEnrollment $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy tất cả đăng ký của môn học
     */
    public function getByCourse(int $courseId): Collection
    {
        return $this->model->with(['student'])
            ->where('course_id', $courseId)
            ->orderBy('enrolled_at')
            ->get();
    }

    /**
     * Lấy sinh viên active trong môn
     */
    public function getActiveStudentsByCourse(int $courseId): Collection
    {
        return $this->model->with(['student'])
            ->where('course_id', $courseId)
            ->where('status', CourseEnrollment::STATUS_ACTIVE)
            ->get();
    }

    /**
     * Tìm theo ID
     */
    public function findById(int $id): ?CourseEnrollment
    {
        return $this->model->with(['course', 'student'])->find($id);
    }

    /**
     * Tìm theo môn học và sinh viên
     */
    public function findByCourseAndStudent(int $courseId, int $studentId): ?CourseEnrollment
    {
        return $this->model->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->first();
    }

    /**
     * Tạo mới
     */
    public function create(array $data): CourseEnrollment
    {
        return $this->model->create($data);
    }

    /**
     * Tạo nhiều bản ghi
     */
    public function createMany(array $enrollments): bool
    {
        return $this->model->insert($enrollments);
    }

    /**
     * Cập nhật
     */
    public function update(int $id, array $data): bool
    {
        $enrollment = $this->model->find($id);
        if (!$enrollment) {
            return false;
        }
        return $enrollment->update($data);
    }

    /**
     * Xóa
     */
    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    /**
     * Xóa theo môn học và sinh viên
     */
    public function deleteByCourseAndStudent(int $courseId, int $studentId): bool
    {
        return $this->model->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->delete() > 0;
    }

    /**
     * Kiểm tra sinh viên đã đăng ký môn chưa
     */
    public function isEnrolled(int $courseId, int $studentId): bool
    {
        return $this->model->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->exists();
    }

    /**
     * Đếm số sinh viên trong môn
     */
    public function countByCourse(int $courseId, ?string $status = null): int
    {
        $query = $this->model->where('course_id', $courseId);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->count();
    }

    /**
     * Lấy danh sách môn của sinh viên
     */
    public function getByStudent(int $studentId): Collection
    {
        return $this->model->with(['course.semester', 'course.lecturer'])
            ->where('student_id', $studentId)
            ->get();
    }

    /**
     * Hủy đăng ký môn học
     */
    public function drop(int $courseId, int $studentId, ?string $reason = null): bool
    {
        $enrollment = $this->findByCourseAndStudent($courseId, $studentId);
        if (!$enrollment) {
            return false;
        }
        return $enrollment->drop($reason);
    }

    /**
     * Lấy ID sinh viên trong môn
     */
    public function getStudentIdsByCourse(int $courseId): array
    {
        return $this->model->where('course_id', $courseId)
            ->where('status', CourseEnrollment::STATUS_ACTIVE)
            ->pluck('student_id')
            ->toArray();
    }
}

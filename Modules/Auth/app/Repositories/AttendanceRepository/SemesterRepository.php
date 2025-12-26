<?php

namespace Modules\Auth\app\Repositories\AttendanceRepository;

use Modules\Auth\app\Models\Attendance\Semester;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository cho Semester
 */
class SemesterRepository
{
    protected Semester $model;

    public function __construct(Semester $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy tất cả học kỳ
     */
    public function all(): Collection
    {
        return $this->model->orderBy('start_date', 'desc')->get();
    }

    /**
     * Lấy học kỳ với phân trang
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderBy('start_date', 'desc')->paginate($perPage);
    }

    /**
     * Tìm theo ID
     */
    public function findById(int $id): ?Semester
    {
        return $this->model->find($id);
    }

    /**
     * Tìm theo code
     */
    public function findByCode(string $code): ?Semester
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Tạo mới
     */
    public function create(array $data): Semester
    {
        return $this->model->create($data);
    }

    /**
     * Cập nhật
     */
    public function update(int $id, array $data): bool
    {
        $semester = $this->findById($id);
        if (!$semester) {
            return false;
        }
        return $semester->update($data);
    }

    /**
     * Xóa
     */
    public function delete(int $id): bool
    {
        $semester = $this->findById($id);
        if (!$semester) {
            return false;
        }
        return $semester->delete();
    }

    /**
     * Lấy học kỳ đang hoạt động
     */
    public function getActive(): ?Semester
    {
        return $this->model->active()->first();
    }

    /**
     * Lấy học kỳ hiện tại (theo ngày)
     */
    public function getCurrent(): ?Semester
    {
        return $this->model->current()->first();
    }

    /**
     * Lấy học kỳ theo năm học
     */
    public function getByAcademicYear(string $year): Collection
    {
        return $this->model->byAcademicYear($year)->orderBy('semester_type')->get();
    }

    /**
     * Kích hoạt học kỳ
     */
    public function activate(int $id): bool
    {
        $semester = $this->findById($id);
        if (!$semester) {
            return false;
        }
        return $semester->activate();
    }
}

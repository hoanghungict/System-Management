<?php

namespace Modules\Auth\app\Services\AttendanceService;

use Modules\Auth\app\Repositories\AttendanceRepository\SemesterRepository;
use Modules\Auth\app\Models\Attendance\Semester;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service quản lý Học kỳ
 */
class SemesterService
{
    protected SemesterRepository $semesterRepository;

    public function __construct(SemesterRepository $semesterRepository)
    {
        $this->semesterRepository = $semesterRepository;
    }

    /**
     * Lấy danh sách học kỳ với phân trang
     */
    public function getAllSemesters(int $perPage = 15): LengthAwarePaginator
    {
        return Cache::remember("semesters:all:per_page:{$perPage}", 1800, function() use ($perPage) {
            return $this->semesterRepository->paginate($perPage);
        });
    }

    /**
     * Lấy chi tiết học kỳ
     */
    public function getSemesterById(int $id): ?Semester
    {
        return Cache::remember("semesters:{$id}", 1800, function() use ($id) {
            return $this->semesterRepository->findById($id);
        });
    }

    /**
     * Tạo học kỳ mới
     */
    public function createSemester(array $data): Semester
    {
        $semester = $this->semesterRepository->create($data);

        /* Log::info('Semester created', [
        /* Log::info('Semester created', [
            'semester_id' => $semester->id,
            'code' => $semester->code,
        ]); */
        ]); */

        $this->clearCache();

        return $semester;
    }

    /**
     * Cập nhật học kỳ
     */
    public function updateSemester(int $id, array $data): bool
    {
        $result = $this->semesterRepository->update($id, $data);

        if ($result) {
            /* Log::info('Semester updated', ['semester_id' => $id]); */
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Xóa học kỳ
     */
    public function deleteSemester(int $id): bool
    {
        $semester = $this->semesterRepository->findById($id);
        
        if (!$semester) {
            return false;
        }

        // Kiểm tra có môn học nào trong học kỳ không
        if ($semester->courses()->exists()) {
            throw new \Exception('Không thể xóa học kỳ đang có môn học');
        }

        $result = $this->semesterRepository->delete($id);

        if ($result) {
            /* Log::info('Semester deleted', ['semester_id' => $id]); */
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Kích hoạt học kỳ
     */
    public function activateSemester(int $id): bool
    {
        $result = $this->semesterRepository->activate($id);

        if ($result) {
            /* Log::info('Semester activated', ['semester_id' => $id]); */
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Lấy học kỳ đang hoạt động
     */
    public function getActiveSemester(): ?Semester
    {
        return Cache::remember('active_semester', 3600, function () {
            return $this->semesterRepository->getActive();
        });
    }

    /**
     * Lấy học kỳ hiện tại (theo ngày)
     */
    public function getCurrentSemester(): ?Semester
    {
        return $this->semesterRepository->getCurrent();
    }

    /**
     * Lấy học kỳ theo năm học
     */
    public function getSemestersByAcademicYear(string $year): Collection
    {
        return Cache::remember("semesters:year:{$year}", 1800, function() use ($year) {
            return $this->semesterRepository->getByAcademicYear($year);
        });
    }

    /**
     * Xóa cache
     */
    private function clearCache(): void
    {
        Cache::forget('active_semester');
        
        // Xóa cache danh sách với các perPage phổ biến
        $perPages = [10, 15, 20, 25, 50];
        foreach ($perPages as $perPage) {
            Cache::forget("semesters:all:per_page:{$perPage}");
        }
    }
}

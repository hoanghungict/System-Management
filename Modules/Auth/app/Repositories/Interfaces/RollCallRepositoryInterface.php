<?php

namespace Modules\Auth\app\Repositories\Interfaces;

use Modules\Auth\app\Models\RollCall;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface RollCallRepositoryInterface
{
    /**
     * Tạo buổi điểm danh mới
     */
    public function create(array $data): RollCall;

    /**
     * Lấy buổi điểm danh theo ID
     */
    public function findById(int $id): ?RollCall;

    /**
     * Lấy danh sách buổi điểm danh theo lớp
     */
    public function getByClass(int $classId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy danh sách buổi điểm danh theo lớp (không phân trang)
     */
    public function getByClassAll(int $classId): Collection;

    /**
     * Cập nhật buổi điểm danh
     */
    public function update(int $id, array $data): bool;

    /**
     * Xóa buổi điểm danh
     */
    public function delete(int $id): bool;

    /**
     * Lấy buổi điểm danh theo ngày
     */
    public function getByDate(string $date): Collection;

    /**
     * Lấy buổi điểm danh theo trạng thái
     */
    public function getByStatus(string $status): Collection;

    /**
     * Lấy buổi điểm danh theo lớp và ngày
     */
    public function getByClassAndDate(int $classId, string $date): Collection;

    /**
     * Lấy thống kê điểm danh theo lớp
     */
    public function getStatisticsByClass(int $classId, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * Đếm số buổi điểm danh theo lớp
     */
    public function countByClass(int $classId): int;

    /**
     * Lấy buổi điểm danh gần nhất của lớp
     */
    public function getLatestByClass(int $classId): ?RollCall;
}

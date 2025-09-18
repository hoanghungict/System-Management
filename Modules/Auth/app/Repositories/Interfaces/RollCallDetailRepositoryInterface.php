<?php

namespace Modules\Auth\app\Repositories\Interfaces;

use Modules\Auth\app\Models\RollCallDetail;
use Illuminate\Database\Eloquent\Collection;

interface RollCallDetailRepositoryInterface
{
    /**
     * Tạo chi tiết điểm danh
     */
    public function create(array $data): RollCallDetail;

    /**
     * Lấy chi tiết điểm danh theo ID
     */
    public function findById(int $id): ?RollCallDetail;

    /**
     * Lấy chi tiết điểm danh theo buổi điểm danh
     */
    public function getByRollCall(int $rollCallId): Collection;

    /**
     * Lấy chi tiết điểm danh theo sinh viên và buổi điểm danh
     */
    public function getByStudentAndRollCall(int $studentId, int $rollCallId): ?RollCallDetail;

    /**
     * Cập nhật chi tiết điểm danh
     */
    public function update(int $id, array $data): bool;

    /**
     * Cập nhật chi tiết điểm danh theo sinh viên và buổi điểm danh
     */
    public function updateByStudentAndRollCall(int $studentId, int $rollCallId, array $data): bool;

    /**
     * Xóa chi tiết điểm danh
     */
    public function delete(int $id): bool;

    /**
     * Xóa tất cả chi tiết điểm danh theo buổi điểm danh
     */
    public function deleteByRollCall(int $rollCallId): bool;

    /**
     * Lấy chi tiết điểm danh theo trạng thái
     */
    public function getByStatus(string $status): Collection;

    /**
     * Lấy chi tiết điểm danh đã điểm danh
     */
    public function getChecked(): Collection;

    /**
     * Lấy chi tiết điểm danh chưa điểm danh
     */
    public function getUnchecked(): Collection;

    /**
     * Đếm số sinh viên theo trạng thái trong buổi điểm danh
     */
    public function countByStatusInRollCall(int $rollCallId, string $status): int;

    /**
     * Lấy thống kê điểm danh của sinh viên
     */
    public function getStudentStatistics(int $studentId, ?string $startDate = null, ?string $endDate = null): array;
}

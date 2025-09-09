<?php

namespace Modules\Task\app\Repositories\Interfaces;

interface EmailRepositoryInterface
{
    /**
     * Lưu log hoạt động email
     *
     * @param array $data
     * @return bool
     */
    public function logEmailActivity(array $data): bool;

    /**
     * Lấy danh sách email logs
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getEmailLogs(array $filters = [], int $limit = 50, int $offset = 0): array;

    /**
     * Lấy thống kê email
     *
     * @param array $filters
     * @return array
     */
    public function getEmailStatistics(array $filters = []): array;

    /**
     * Lưu template email
     *
     * @param array $data
     * @return bool
     */
    public function saveEmailTemplate(array $data): bool;

    /**
     * Lấy template email
     *
     * @param string $name
     * @return array|null
     */
    public function getEmailTemplate(string $name): ?array;

    /**
     * Xóa email log cũ
     *
     * @param int $daysOld
     * @return int
     */
    public function cleanOldEmailLogs(int $daysOld = 30): int;
}

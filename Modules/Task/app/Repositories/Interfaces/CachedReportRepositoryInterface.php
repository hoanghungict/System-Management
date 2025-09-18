<?php

namespace Modules\Task\app\Repositories\Interfaces;

/**
 * Interface cho Cached Report Repository
 * 
 * Tuân thủ Clean Architecture: Interface định nghĩa contract cho cached report operations
 */
interface CachedReportRepositoryInterface
{
    /**
     * Tạo task report với cache
     * 
     * @param array $filters Report filters
     * @param mixed $user User requesting report
     * @return array
     */
    public function generateTaskReport(array $filters, $user): array;

    /**
     * Lấy task analytics với cache
     * 
     * @param array $params Analytics parameters
     * @param mixed $user User requesting analytics
     * @return array
     */
    public function getTaskAnalytics(array $params, $user): array;

    /**
     * Lấy user performance report với cache
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param array $dateRange Date range
     * @return array
     */
    public function getUserPerformanceReport(int $userId, string $userType, array $dateRange): array;

    /**
     * Lấy department performance report với cache
     * 
     * @param int $departmentId Department ID
     * @param array $dateRange Date range
     * @return array
     */
    public function getDepartmentPerformanceReport(int $departmentId, array $dateRange): array;

    /**
     * Lấy class performance report với cache
     * 
     * @param int $classId Class ID
     * @param array $dateRange Date range
     * @return array
     */
    public function getClassPerformanceReport(int $classId, array $dateRange): array;

    /**
     * Lấy task completion statistics với cache
     * 
     * @param array $filters Statistics filters
     * @return array
     */
    public function getTaskCompletionStatistics(array $filters): array;

    /**
     * Lấy task distribution analytics với cache
     * 
     * @param array $filters Distribution filters
     * @return array
     */
    public function getTaskDistributionAnalytics(array $filters): array;

    /**
     * Lấy deadline adherence report với cache
     * 
     * @param array $filters Adherence filters
     * @return array
     */
    public function getDeadlineAdherenceReport(array $filters): array;

    /**
     * Lấy productivity metrics với cache
     * 
     * @param array $filters Productivity filters
     * @return array
     */
    public function getProductivityMetrics(array $filters): array;

    /**
     * Lấy system overview dashboard với cache
     * 
     * @return array
     */
    public function getSystemOverviewDashboard(): array;

    /**
     * Xóa cache cho user reports
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @return bool
     */
    public function clearUserReportCache(int $userId, string $userType): bool;

    /**
     * Xóa cache cho faculty reports
     * 
     * @param int $facultyId Faculty ID
     * @return bool
     */
    public function clearDepartmentReportCache(int $departmentId): bool;

    /**
     * Xóa cache cho class reports
     * 
     * @param int $classId Class ID
     * @return bool
     */
    public function clearClassReportCache(int $classId): bool;

    /**
     * Xóa tất cả report cache
     * 
     * @return bool
     */
    public function clearAllReportCache(): bool;
}

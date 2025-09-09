<?php

namespace Modules\Task\app\Monitoring\Contracts;

/**
 * Monitoring Contract
 * 
 * Interface định nghĩa các method cần thiết cho Monitoring
 */
interface MonitoringInterface
{
    /**
     * Get system health status
     */
    public function getSystemHealth(): array;

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array;

    /**
     * Get database status
     */
    public function getDatabaseStatus(): array;

    /**
     * Get cache status
     */
    public function getCacheStatus(): array;

    /**
     * Get queue status
     */
    public function getQueueStatus(): array;

    /**
     * Get API statistics
     */
    public function getApiStatistics(): array;

    /**
     * Get error logs
     */
    public function getErrorLogs(int $limit = 50): array;

    /**
     * Get system resources
     */
    public function getSystemResources(): array;
}

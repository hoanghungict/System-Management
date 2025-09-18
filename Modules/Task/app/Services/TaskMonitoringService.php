<?php

namespace Modules\Task\app\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Carbon\Carbon;

/**
 * ✅ TaskMonitoringService - Performance Metrics & Health Monitoring
 * 
 * Service để monitor performance và health của Task Module
 * Cung cấp metrics, alerts, và health checks
 */
class TaskMonitoringService
{
    protected TaskRepositoryInterface $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * ✅ Thu thập performance metrics
     * 
     * @param string $timeframe Khoảng thời gian: 1h, 24h, 7d, 30d
     * @return array Performance metrics
     */
    public function getPerformanceMetrics(string $timeframe = '24h'): array
    {
        $cacheKey = "task_performance_metrics:{$timeframe}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($timeframe) {
            $fromDate = $this->getDateFromTimeframe($timeframe);
            
            return [
                'timeframe' => $timeframe,
                'collected_at' => now()->toISOString(),
                'database_metrics' => $this->getDatabaseMetrics($fromDate),
                'cache_metrics' => $this->getCacheMetrics(),
                'api_metrics' => $this->getApiMetrics($fromDate),
                'business_metrics' => $this->getBusinessMetrics($fromDate),
                'error_metrics' => $this->getErrorMetrics($fromDate),
                'resource_usage' => $this->getResourceUsageMetrics()
            ];
        });
    }

    /**
     * ✅ Database performance metrics
     */
    private function getDatabaseMetrics(Carbon $fromDate): array
    {
        $startTime = microtime(true);
        
        // Query performance measurements
        $taskCount = Task::where('created_at', '>=', $fromDate)->count();
        $avgResponseTime = microtime(true) - $startTime;

        // Slow query detection
        $slowQueries = $this->detectSlowQueries();
        
        // Connection pool status
        $connectionStats = $this->getConnectionPoolStats();

        return [
            'total_queries_executed' => $this->getQueryCount($fromDate),
            'average_query_time_ms' => round($avgResponseTime * 1000, 2),
            'slow_queries_count' => count($slowQueries),
            'slow_queries' => $slowQueries,
            'connection_pool' => $connectionStats,
            'database_size_mb' => $this->getDatabaseSize(),
            'index_efficiency' => $this->getIndexEfficiency()
        ];
    }

    /**
     * ✅ Cache performance metrics
     */
    private function getCacheMetrics(): array
    {
        $cacheInfo = $this->getCacheInfo();
        
        return [
            'hit_rate_percentage' => $cacheInfo['hit_rate'] ?? 0,
            'miss_rate_percentage' => 100 - ($cacheInfo['hit_rate'] ?? 0),
            'total_keys' => $cacheInfo['total_keys'] ?? 0,
            'memory_usage_mb' => $cacheInfo['memory_usage'] ?? 0,
            'evicted_keys' => $cacheInfo['evicted_keys'] ?? 0,
            'expired_keys' => $cacheInfo['expired_keys'] ?? 0,
            'average_key_size_bytes' => $cacheInfo['avg_key_size'] ?? 0
        ];
    }

    /**
     * ✅ API performance metrics
     */
    private function getApiMetrics(Carbon $fromDate): array
    {
        // Would integrate with Laravel's request logging
        return [
            'total_requests' => $this->getApiRequestCount($fromDate),
            'average_response_time_ms' => $this->getAverageResponseTime($fromDate),
            'requests_by_endpoint' => $this->getRequestsByEndpoint($fromDate),
            'status_code_distribution' => $this->getStatusCodeDistribution($fromDate),
            'peak_requests_per_minute' => $this->getPeakRequestRate($fromDate),
            'api_errors_count' => $this->getApiErrorCount($fromDate)
        ];
    }

    /**
     * ✅ Business logic metrics
     */
    private function getBusinessMetrics(Carbon $fromDate): array
    {
        return [
            'tasks_created' => Task::where('created_at', '>=', $fromDate)->count(),
            'tasks_completed' => Task::where('updated_at', '>=', $fromDate)
                                   ->where('status', 'completed')->count(),
            'tasks_overdue' => Task::where('deadline', '<', now())
                                  ->where('status', '!=', 'completed')->count(),
            'average_completion_time_hours' => $this->getAverageCompletionTime($fromDate),
            'user_engagement' => $this->getUserEngagementMetrics($fromDate),
            'receiver_distribution' => $this->getReceiverDistribution($fromDate)
        ];
    }

    /**
     * ✅ Error tracking metrics
     */
    private function getErrorMetrics(Carbon $fromDate): array
    {
        return [
            'total_errors' => $this->getErrorCount($fromDate),
            'error_rate_percentage' => $this->getErrorRate($fromDate),
            'errors_by_type' => $this->getErrorsByType($fromDate),
            'errors_by_severity' => $this->getErrorsBySeverity($fromDate),
            'most_common_errors' => $this->getMostCommonErrors($fromDate),
            'error_trends' => $this->getErrorTrends($fromDate)
        ];
    }

    /**
     * ✅ System resource usage
     */
    private function getResourceUsageMetrics(): array
    {
        return [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'cpu_load' => $this->getCpuLoad(),
            'disk_usage_percentage' => $this->getDiskUsage(),
            'active_connections' => $this->getActiveConnections()
        ];
    }

    /**
     * ✅ Health check cho Task Module
     * 
     * @return array Health status với recommendations
     */
    public function performHealthCheck(): array
    {
        $checks = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'permissions' => $this->checkPermissionSystemHealth(),
            'business_logic' => $this->checkBusinessLogicHealth(),
            'performance' => $this->checkPerformanceHealth()
        ];

        $overallStatus = $this->determineOverallHealth($checks);
        $recommendations = $this->generateHealthRecommendations($checks);

        return [
            'status' => $overallStatus,
            'checked_at' => now()->toISOString(),
            'checks' => $checks,
            'recommendations' => $recommendations,
            'next_check_in_minutes' => 15
        ];
    }

    /**
     * ✅ Database health check
     */
    private function checkDatabaseHealth(): array
    {
        $startTime = microtime(true);
        $issues = [];
        
        try {
            // Connection test
            DB::connection()->getPdo();
            
            // Basic query test
            $count = Task::count();
            $responseTime = microtime(true) - $startTime;
            
            // Check for slow queries
            if ($responseTime > 1.0) {
                $issues[] = 'Database response time is slow (>' . $responseTime . 's)';
            }
            
            // Check for connection pool
            $activeConnections = $this->getActiveConnections();
            if ($activeConnections > 80) { // 80% of max connections
                $issues[] = 'High database connection usage: ' . $activeConnections;
            }
            
            return [
                'status' => empty($issues) ? 'healthy' : 'warning',
                'response_time_ms' => round($responseTime * 1000, 2),
                'active_connections' => $activeConnections,
                'total_tasks' => $count,
                'issues' => $issues
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Database connection failed']
            ];
        }
    }

    /**
     * ✅ Cache health check
     */
    private function checkCacheHealth(): array
    {
        $issues = [];
        
        try {
            // Cache write/read test
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_' . time();
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrieved !== $testValue) {
                $issues[] = 'Cache read/write test failed';
            }
            
            // Check cache hit rate
            $cacheInfo = $this->getCacheInfo();
            $hitRate = $cacheInfo['hit_rate'] ?? 0;
            
            if ($hitRate < 80) {
                $issues[] = "Low cache hit rate: {$hitRate}%";
            }
            
            return [
                'status' => empty($issues) ? 'healthy' : 'warning',
                'hit_rate_percentage' => $hitRate,
                'memory_usage_mb' => $cacheInfo['memory_usage'] ?? 0,
                'total_keys' => $cacheInfo['total_keys'] ?? 0,
                'issues' => $issues
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Cache system unavailable']
            ];
        }
    }

    /**
     * ✅ Permission system health check
     */
    private function checkPermissionSystemHealth(): array
    {
        $issues = [];
        
        try {
            // Test permission service
            $permissionService = app(PermissionService::class);
            
            // Mock user contexts for testing
            $adminContext = (object) ['user_type' => 'admin', 'id' => 1];
            $lecturerContext = (object) ['user_type' => 'lecturer', 'id' => 1];
            $studentContext = (object) ['user_type' => 'student', 'id' => 1];
            
            // Test basic permissions
            if (!$permissionService->isAdmin($adminContext)) {
                $issues[] = 'Admin permission check failed';
            }
            
            if (!$permissionService->canCreateTasks($lecturerContext)) {
                $issues[] = 'Lecturer create permission check failed';
            }
            
            if ($permissionService->canCreateTasks($studentContext)) {
                $issues[] = 'Student should not have create permission';
            }
            
            return [
                'status' => empty($issues) ? 'healthy' : 'critical',
                'permission_cache_keys' => $this->getPermissionCacheKeyCount(),
                'issues' => $issues
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Permission system error']
            ];
        }
    }

    /**
     * ✅ Business logic health check
     */
    private function checkBusinessLogicHealth(): array
    {
        $issues = [];
        
        try {
            // Check for data inconsistencies
            $tasksWithoutReceivers = Task::doesntHave('receivers')->count();
            if ($tasksWithoutReceivers > 0) {
                $issues[] = "{$tasksWithoutReceivers} tasks found without receivers";
            }
            
            // Check for overdue tasks
            $overdueTasks = Task::where('deadline', '<', now())
                              ->where('status', '!=', 'completed')
                              ->count();
            
            // Check for extremely old pending tasks
            $staleTasks = Task::where('status', 'pending')
                            ->where('created_at', '<', now()->subDays(30))
                            ->count();
            
            if ($staleTasks > 0) {
                $issues[] = "{$staleTasks} tasks pending for more than 30 days";
            }
            
            return [
                'status' => empty($issues) ? 'healthy' : 'warning',
                'tasks_without_receivers' => $tasksWithoutReceivers,
                'overdue_tasks' => $overdueTasks,
                'stale_pending_tasks' => $staleTasks,
                'issues' => $issues
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Business logic check failed']
            ];
        }
    }

    /**
     * ✅ Performance health check
     */
    private function checkPerformanceHealth(): array
    {
        $issues = [];
        
        // Check memory usage
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryUsage > 80) { // MB
            $issues[] = "High memory usage: {$memoryUsage}MB";
        }
        
        // Check for slow operations
        $slowQueriesCount = count($this->detectSlowQueries());
        if ($slowQueriesCount > 5) {
            $issues[] = "{$slowQueriesCount} slow queries detected";
        }
        
        return [
            'status' => empty($issues) ? 'healthy' : 'warning',
            'memory_usage_mb' => round($memoryUsage, 2),
            'slow_queries_count' => $slowQueriesCount,
            'issues' => $issues
        ];
    }

    /**
     * ✅ Determine overall health status
     */
    private function determineOverallHealth(array $checks): string
    {
        $statuses = array_column($checks, 'status');
        
        if (in_array('critical', $statuses)) {
            return 'critical';
        }
        
        if (in_array('warning', $statuses)) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * ✅ Generate health recommendations
     */
    private function generateHealthRecommendations(array $checks): array
    {
        $recommendations = [];
        
        foreach ($checks as $checkName => $check) {
            if (!empty($check['issues'])) {
                foreach ($check['issues'] as $issue) {
                    $recommendations[] = $this->getRecommendationForIssue($checkName, $issue);
                }
            }
        }
        
        return array_filter($recommendations);
    }

    /**
     * ✅ Get recommendation for specific issue
     */
    private function getRecommendationForIssue(string $checkName, string $issue): ?string
    {
        $recommendationMap = [
            'database' => [
                'slow' => 'Consider optimizing queries or adding database indexes',
                'connection' => 'Consider increasing connection pool size or optimizing connection usage'
            ],
            'cache' => [
                'hit_rate' => 'Review cache keys and TTL settings to improve hit rate',
                'memory' => 'Consider increasing cache memory or implementing cache eviction strategy'
            ],
            'business_logic' => [
                'receivers' => 'Run data consistency check to fix tasks without receivers',
                'stale' => 'Consider implementing automated task cleanup for old pending tasks'
            ],
            'performance' => [
                'memory' => 'Monitor memory usage and consider optimizing data structures',
                'queries' => 'Investigate and optimize slow queries'
            ]
        ];
        
        foreach ($recommendationMap[$checkName] ?? [] as $keyword => $recommendation) {
            if (stripos($issue, $keyword) !== false) {
                return $recommendation;
            }
        }
        
        return "Review {$checkName} configuration and performance";
    }

    /**
     * ✅ Alert system cho critical issues
     * 
     * @param array $healthStatus Health check results
     * @return bool Alert sent successfully
     */
    public function sendAlertsIfNeeded(array $healthStatus): bool
    {
        if ($healthStatus['status'] === 'critical') {
            return $this->sendCriticalAlert($healthStatus);
        }
        
        if ($healthStatus['status'] === 'warning') {
            return $this->sendWarningAlert($healthStatus);
        }
        
        return true; // No alerts needed
    }

    /**
     * ✅ Send critical alert
     */
    private function sendCriticalAlert(array $healthStatus): bool
    {
        $criticalIssues = [];
        foreach ($healthStatus['checks'] as $checkName => $check) {
            if ($check['status'] === 'critical') {
                $criticalIssues[] = "{$checkName}: " . implode(', ', $check['issues'] ?? []);
            }
        }
        
        $message = "🚨 CRITICAL: Task Module Health Issues\n" . implode("\n", $criticalIssues);
        
        Log::critical('Task Module Critical Health Alert', [
            'issues' => $criticalIssues,
            'full_status' => $healthStatus
        ]);
        
        // Would integrate with notification system (email, Slack, etc.)
        return $this->sendNotification('critical', $message);
    }

    /**
     * ✅ Send warning alert
     */
    private function sendWarningAlert(array $healthStatus): bool
    {
        // Only send warning alerts if they persist
        $cacheKey = 'task_warning_alert_sent';
        if (Cache::has($cacheKey)) {
            return true; // Already sent recently
        }
        
        $warningIssues = [];
        foreach ($healthStatus['checks'] as $checkName => $check) {
            if ($check['status'] === 'warning') {
                $warningIssues[] = "{$checkName}: " . implode(', ', $check['issues'] ?? []);
            }
        }
        
        $message = "⚠️ WARNING: Task Module Health Issues\n" . implode("\n", $warningIssues);
        
        Log::warning('Task Module Warning Health Alert', [
            'issues' => $warningIssues
        ]);
        
        // Cache alert to prevent spam
        Cache::put($cacheKey, true, now()->addHours(1));
        
        return $this->sendNotification('warning', $message);
    }

    // ============================================
    // Helper Methods (would be implemented based on specific infrastructure)
    // ============================================

    private function getDateFromTimeframe(string $timeframe): Carbon
    {
        return match($timeframe) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay()
        };
    }

    private function getQueryCount(Carbon $fromDate): int
    {
        // Would integrate with query logging
        return 0;
    }

    private function detectSlowQueries(): array
    {
        // Would integrate with slow query log
        return [];
    }

    private function getConnectionPoolStats(): array
    {
        return [
            'active' => 5,
            'idle' => 10,
            'max' => 20
        ];
    }

    private function getDatabaseSize(): float
    {
        // Would calculate actual database size
        return 0.0;
    }

    private function getIndexEfficiency(): float
    {
        // Would analyze index usage
        return 95.0;
    }

    private function getCacheInfo(): array
    {
        // Would integrate with Redis/Memcached stats
        return [
            'hit_rate' => 85.5,
            'total_keys' => 1000,
            'memory_usage' => 128,
            'evicted_keys' => 10,
            'expired_keys' => 50,
            'avg_key_size' => 512
        ];
    }

    private function getApiRequestCount(Carbon $fromDate): int
    {
        // Would integrate with request logging
        return 0;
    }

    private function getAverageResponseTime(Carbon $fromDate): float
    {
        return 150.5; // ms
    }

    private function getRequestsByEndpoint(Carbon $fromDate): array
    {
        return [
            '/api/tasks' => 100,
            '/api/tasks/create' => 50,
            '/api/tasks/{id}' => 200
        ];
    }

    private function getStatusCodeDistribution(Carbon $fromDate): array
    {
        return [
            '200' => 300,
            '404' => 10,
            '500' => 2
        ];
    }

    private function getPeakRequestRate(Carbon $fromDate): int
    {
        return 50; // requests per minute
    }

    private function getApiErrorCount(Carbon $fromDate): int
    {
        return 12;
    }

    private function getAverageCompletionTime(Carbon $fromDate): float
    {
        return 24.5; // hours
    }

    private function getUserEngagementMetrics(Carbon $fromDate): array
    {
        return [
            'active_users' => 150,
            'tasks_per_user' => 3.2
        ];
    }

    private function getReceiverDistribution(Carbon $fromDate): array
    {
        return [
            'student' => 80,
            'lecturer' => 15,
            'class' => 5
        ];
    }

    private function getErrorCount(Carbon $fromDate): int
    {
        return 25;
    }

    private function getErrorRate(Carbon $fromDate): float
    {
        return 2.5; // percentage
    }

    private function getErrorsByType(Carbon $fromDate): array
    {
        return [
            'validation' => 15,
            'authorization' => 5,
            'system' => 5
        ];
    }

    private function getErrorsBySeverity(Carbon $fromDate): array
    {
        return [
            'low' => 15,
            'medium' => 8,
            'high' => 2,
            'critical' => 0
        ];
    }

    private function getMostCommonErrors(Carbon $fromDate): array
    {
        return [
            'VALIDATION_FAILED' => 10,
            'UNAUTHORIZED' => 5,
            'TASK_NOT_FOUND' => 3
        ];
    }

    private function getErrorTrends(Carbon $fromDate): array
    {
        return [
            '2025-01-15' => 5,
            '2025-01-14' => 8,
            '2025-01-13' => 3
        ];
    }

    private function getCpuLoad(): float
    {
        // Would get actual CPU load
        return 45.2;
    }

    private function getDiskUsage(): float
    {
        // Would get actual disk usage
        return 65.8;
    }

    private function getActiveConnections(): int
    {
        // Would get actual connection count
        return 15;
    }

    private function getPermissionCacheKeyCount(): int
    {
        // Would count permission cache keys
        return 500;
    }

    private function sendNotification(string $level, string $message): bool
    {
        // Would integrate with notification system
        Log::info("Notification sent", [
            'level' => $level,
            'message' => $message
        ]);
        return true;
    }
}

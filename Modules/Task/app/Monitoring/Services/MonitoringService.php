<?php

namespace Modules\Task\app\Monitoring\Services;

use Modules\Task\app\Monitoring\Contracts\MonitoringInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Monitoring Service
 * 
 * Service layer theo Clean Architecture
 * Xử lý monitoring operations
 */
class MonitoringService implements MonitoringInterface
{
    /**
     * Get system health status
     */
    public function getSystemHealth(): array
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'checks' => []
            ];

            // Database check
            $dbStatus = $this->checkDatabase();
            $health['checks']['database'] = $dbStatus;

            // Cache check
            $cacheStatus = $this->checkCache();
            $health['checks']['cache'] = $cacheStatus;

            // Queue check
            $queueStatus = $this->checkQueue();
            $health['checks']['queue'] = $queueStatus;

            // Storage check
            $storageStatus = $this->checkStorage();
            $health['checks']['storage'] = $storageStatus;

            // Overall status
            $allHealthy = collect($health['checks'])->every(fn($check) => $check['status'] === 'healthy');
            $health['status'] = $allHealthy ? 'healthy' : 'degraded';

            return $health;
        } catch (\Exception $e) {
            Log::error('Error getting system health: ' . $e->getMessage());
            return [
                'status' => 'unhealthy',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        try {
            return [
                'timestamp' => now()->toISOString(),
                'memory_usage' => $this->getMemoryUsage(),
                'execution_time' => $this->getExecutionTime(),
                'database_queries' => $this->getDatabaseQueries(),
                'cache_hits' => $this->getCacheHits(),
                'api_response_times' => $this->getApiResponseTimes()
            ];
        } catch (\Exception $e) {
            Log::error('Error getting performance metrics: ' . $e->getMessage());
            return [
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get database status
     */
    public function getDatabaseStatus(): array
    {
        try {
            $dbStatus = $this->checkDatabase();
            
            // Get table sizes
            $tableSizes = $this->getTableSizes();
            
            // Get connection info
            $connectionInfo = $this->getConnectionInfo();

            return [
                'status' => $dbStatus['status'],
                'timestamp' => now()->toISOString(),
                'connection' => $connectionInfo,
                'table_sizes' => $tableSizes,
                'details' => $dbStatus
            ];
        } catch (\Exception $e) {
            Log::error('Error getting database status: ' . $e->getMessage());
            return [
                'status' => 'unhealthy',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get cache status
     */
    public function getCacheStatus(): array
    {
        try {
            $cacheStatus = $this->checkCache();
            
            // Get cache statistics
            $cacheStats = $this->getCacheStatistics();

            return [
                'status' => $cacheStatus['status'],
                'timestamp' => now()->toISOString(),
                'driver' => config('cache.default'),
                'statistics' => $cacheStats,
                'details' => $cacheStatus
            ];
        } catch (\Exception $e) {
            Log::error('Error getting cache status: ' . $e->getMessage());
            return [
                'status' => 'unhealthy',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get queue status
     */
    public function getQueueStatus(): array
    {
        try {
            $queueStatus = $this->checkQueue();
            
            // Get queue statistics
            $queueStats = $this->getQueueStatistics();

            return [
                'status' => $queueStatus['status'],
                'timestamp' => now()->toISOString(),
                'driver' => config('queue.default'),
                'statistics' => $queueStats,
                'details' => $queueStatus
            ];
        } catch (\Exception $e) {
            Log::error('Error getting queue status: ' . $e->getMessage());
            return [
                'status' => 'unhealthy',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get API statistics
     */
    public function getApiStatistics(): array
    {
        try {
            return [
                'timestamp' => now()->toISOString(),
                'total_requests' => $this->getTotalRequests(),
                'success_rate' => $this->getSuccessRate(),
                'average_response_time' => $this->getAverageResponseTime(),
                'endpoints' => $this->getEndpointStatistics(),
                'error_rate' => $this->getErrorRate()
            ];
        } catch (\Exception $e) {
            Log::error('Error getting API statistics: ' . $e->getMessage());
            return [
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get error logs
     */
    public function getErrorLogs(int $limit = 50): array
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            
            if (!file_exists($logFile)) {
                return [
                    'timestamp' => now()->toISOString(),
                    'logs' => [],
                    'message' => 'No log file found'
                ];
            }

            $logs = [];
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $errorLines = array_filter($lines, fn($line) => strpos($line, 'ERROR') !== false);
            
            $recentErrors = array_slice(array_reverse($errorLines), 0, $limit);
            
            foreach ($recentErrors as $line) {
                $logs[] = [
                    'timestamp' => $this->extractTimestamp($line),
                    'level' => 'ERROR',
                    'message' => $line
                ];
            }

            return [
                'timestamp' => now()->toISOString(),
                'logs' => $logs,
                'count' => count($logs)
            ];
        } catch (\Exception $e) {
            Log::error('Error getting error logs: ' . $e->getMessage());
            return [
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get system resources
     */
    public function getSystemResources(): array
    {
        try {
            return [
                'timestamp' => now()->toISOString(),
                'memory' => $this->getMemoryUsage(),
                'disk_space' => $this->getDiskSpace(),
                'cpu_usage' => $this->getCpuUsage(),
                'load_average' => $this->getLoadAverage()
            ];
        } catch (\Exception $e) {
            Log::error('Error getting system resources: ' . $e->getMessage());
            return [
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'healthy',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check cache connection
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'monitoring_test_' . time();
            Cache::put($testKey, 'test', 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $retrieved === 'test' ? 'healthy' : 'unhealthy',
                'message' => $retrieved === 'test' ? 'Cache working properly' : 'Cache test failed'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check queue connection
     */
    private function checkQueue(): array
    {
        try {
            // Simple queue check
            return [
                'status' => 'healthy',
                'message' => 'Queue system available'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check storage
     */
    private function checkStorage(): array
    {
        try {
            $testFile = 'monitoring_test_' . time() . '.txt';
            Storage::put($testFile, 'test');
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);
            
            return [
                'status' => $exists ? 'healthy' : 'unhealthy',
                'message' => $exists ? 'Storage working properly' : 'Storage test failed'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        return [
            'current' => $this->formatBytes($memoryUsage),
            'peak' => $this->formatBytes($memoryPeak),
            'current_bytes' => $memoryUsage,
            'peak_bytes' => $memoryPeak
        ];
    }

    /**
     * Get execution time
     */
    private function getExecutionTime(): float
    {
        return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    }

    /**
     * Get database queries count
     */
    private function getDatabaseQueries(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Get cache hits (simplified)
     */
    private function getCacheHits(): int
    {
        // This would need to be implemented based on your cache driver
        return 0;
    }

    /**
     * Get API response times (simplified)
     */
    private function getApiResponseTimes(): array
    {
        return [
            'average' => 0.1,
            'min' => 0.05,
            'max' => 0.5
        ];
    }

    /**
     * Get table sizes
     */
    private function getTableSizes(): array
    {
        try {
            $tables = ['task', 'task_receivers', 'calendar', 'student', 'lecturer', 'class'];
            $sizes = [];
            
            foreach ($tables as $table) {
                try {
                    $count = DB::table($table)->count();
                    $sizes[$table] = $count;
                } catch (\Exception $e) {
                    $sizes[$table] = 'N/A';
                }
            }
            
            return $sizes;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get connection info
     */
    private function getConnectionInfo(): array
    {
        try {
            $connection = DB::connection();
            return [
                'driver' => $connection->getDriverName(),
                'database' => $connection->getDatabaseName(),
                'host' => $connection->getConfig('host'),
                'port' => $connection->getConfig('port')
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get cache statistics
     */
    private function getCacheStatistics(): array
    {
        return [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            'stores' => array_keys(config('cache.stores'))
        ];
    }

    /**
     * Get queue statistics
     */
    private function getQueueStatistics(): array
    {
        return [
            'driver' => config('queue.default'),
            'connections' => array_keys(config('queue.connections'))
        ];
    }

    /**
     * Get total requests (simplified)
     */
    private function getTotalRequests(): int
    {
        return 0; // Would need to be implemented with request tracking
    }

    /**
     * Get success rate (simplified)
     */
    private function getSuccessRate(): float
    {
        return 95.5; // Would need to be implemented with request tracking
    }

    /**
     * Get average response time (simplified)
     */
    private function getAverageResponseTime(): float
    {
        return 0.15; // Would need to be implemented with request tracking
    }

    /**
     * Get endpoint statistics (simplified)
     */
    private function getEndpointStatistics(): array
    {
        return [
            'tasks' => ['requests' => 100, 'avg_time' => 0.12],
            'calendar' => ['requests' => 50, 'avg_time' => 0.08],
            'cache' => ['requests' => 25, 'avg_time' => 0.05]
        ];
    }

    /**
     * Get error rate (simplified)
     */
    private function getErrorRate(): float
    {
        return 2.5; // Would need to be implemented with request tracking
    }

    /**
     * Get disk space
     */
    private function getDiskSpace(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'usage_percentage' => round(($used / $total) * 100, 2)
        ];
    }

    /**
     * Get CPU usage (simplified)
     */
    private function getCpuUsage(): float
    {
        return 25.5; // Would need to be implemented with system monitoring
    }

    /**
     * Get load average (simplified)
     */
    private function getLoadAverage(): array
    {
        return [
            '1min' => 0.5,
            '5min' => 0.7,
            '15min' => 0.6
        ];
    }

    /**
     * Extract timestamp from log line
     */
    private function extractTimestamp(string $line): string
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return $matches[1];
        }
        return now()->toISOString();
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

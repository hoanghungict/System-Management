<?php

namespace Modules\Task\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Task\app\Monitoring\Contracts\MonitoringInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Monitoring Controller - API cho system monitoring
 * 
 * Controller này cung cấp endpoints để monitor hệ thống
 * Sử dụng Clean Architecture với Service layer
 */
class MonitoringController extends Controller
{
    public function __construct(
        private MonitoringInterface $monitoringService
    ) {}

    /**
     * GET /monitoring/health
     */
    public function health(): JsonResponse
    {
        $health = $this->monitoringService->getSystemHealth();

        return response()->json([
            'success' => true,
            'message' => 'System monitoring service is running',
            'data' => $health
        ]);
    }

    /**
     * GET /monitoring/performance
     */
    public function performance(): JsonResponse
    {
        $metrics = $this->monitoringService->getPerformanceMetrics();

        return response()->json([
            'success' => true,
            'message' => 'Performance metrics retrieved successfully',
            'data' => $metrics
        ]);
    }

    /**
     * GET /monitoring/database
     */
    public function database(): JsonResponse
    {
        $status = $this->monitoringService->getDatabaseStatus();

        return response()->json([
            'success' => true,
            'message' => 'Database status retrieved successfully',
            'data' => $status
        ]);
    }

    /**
     * GET /monitoring/cache
     */
    public function cache(): JsonResponse
    {
        $status = $this->monitoringService->getCacheStatus();

        return response()->json([
            'success' => true,
            'message' => 'Cache status retrieved successfully',
            'data' => $status
        ]);
    }

    /**
     * GET /monitoring/queue
     */
    public function queue(): JsonResponse
    {
        $status = $this->monitoringService->getQueueStatus();

        return response()->json([
            'success' => true,
            'message' => 'Queue status retrieved successfully',
            'data' => $status
        ]);
    }

    /**
     * GET /monitoring/api-stats
     */
    public function apiStats(): JsonResponse
    {
        $stats = $this->monitoringService->getApiStatistics();

        return response()->json([
            'success' => true,
            'message' => 'API statistics retrieved successfully',
            'data' => $stats
        ]);
    }

    /**
     * GET /monitoring/errors
     */
    public function errors(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $logs = $this->monitoringService->getErrorLogs($limit);

        return response()->json([
            'success' => true,
            'message' => 'Error logs retrieved successfully',
            'data' => $logs
        ]);
    }

    /**
     * GET /monitoring/resources
     */
    public function resources(): JsonResponse
    {
        $resources = $this->monitoringService->getSystemResources();

        return response()->json([
            'success' => true,
            'message' => 'System resources retrieved successfully',
            'data' => $resources
        ]);
    }
}

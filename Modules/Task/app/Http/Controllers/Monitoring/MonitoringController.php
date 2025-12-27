<?php

namespace Modules\Task\app\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Modules\Task\app\Monitoring\Contracts\MonitoringInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Monitoring Controller - API cho system monitoring
 */
class MonitoringController extends Controller
{
    public function __construct(
        private MonitoringInterface $monitoringService
    ) {}

    public function health(): JsonResponse
    {
        $health = $this->monitoringService->getSystemHealth();
        return response()->json(['success' => true, 'message' => 'System monitoring service is running', 'data' => $health]);
    }

    public function performance(): JsonResponse
    {
        $metrics = $this->monitoringService->getPerformanceMetrics();
        return response()->json(['success' => true, 'message' => 'Performance metrics retrieved successfully', 'data' => $metrics]);
    }

    public function database(): JsonResponse
    {
        $status = $this->monitoringService->getDatabaseStatus();
        return response()->json(['success' => true, 'message' => 'Database status retrieved successfully', 'data' => $status]);
    }

    public function cache(): JsonResponse
    {
        $status = $this->monitoringService->getCacheStatus();
        return response()->json(['success' => true, 'message' => 'Cache status retrieved successfully', 'data' => $status]);
    }

    public function queue(): JsonResponse
    {
        $status = $this->monitoringService->getQueueStatus();
        return response()->json(['success' => true, 'message' => 'Queue status retrieved successfully', 'data' => $status]);
    }

    public function apiStats(): JsonResponse
    {
        $stats = $this->monitoringService->getApiStatistics();
        return response()->json(['success' => true, 'message' => 'API statistics retrieved successfully', 'data' => $stats]);
    }

    public function errors(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $logs = $this->monitoringService->getErrorLogs($limit);
        return response()->json(['success' => true, 'message' => 'Error logs retrieved successfully', 'data' => $logs]);
    }

    public function resources(): JsonResponse
    {
        $resources = $this->monitoringService->getSystemResources();
        return response()->json(['success' => true, 'message' => 'System resources retrieved successfully', 'data' => $resources]);
    }
}

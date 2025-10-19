<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Statistics;

use App\Http\Controllers\Controller;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Task Statistics Controller
 * 
 * Handles statistics and reporting endpoints for Task Module
 */
class TaskStatisticsController extends Controller
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly ReportService $reportService
    ) {}

    /**
     * Get user task statistics
     */
    public function getUserStatistics(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->only([
                'start_date',
                'end_date',
                'status',
                'priority',
                'creator_type'
            ]);

            $statistics = $this->reportService->getUserStatistics($userId, $userType, $filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'User statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get user statistics', [
                'user_id' => $userId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user statistics'
            ], 500);
        }
    }

    /**
     * Get overall task statistics (Admin only)
     */
    public function getOverallStatistics(Request $request): JsonResponse
    {
        try {
            $userType = $request->attributes->get('jwt_user_type');

            if ($userType !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $filters = $request->only([
                'start_date',
                'end_date',
                'status',
                'priority',
                'creator_type'
            ]);

            $statistics = $this->reportService->getTaskStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Overall statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get overall statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve overall statistics'
            ], 500);
        }
    }

    /**
     * Generate comprehensive report
     */
    public function generateReport(Request $request): JsonResponse
    {
        try {
            $userType = $request->attributes->get('jwt_user_type');

            if ($userType !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $filters = $request->only([
                'start_date',
                'end_date',
                'status',
                'priority',
                'creator_type'
            ]);

            $report = $this->reportService->generateTaskReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Report generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to generate report', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report'
            ], 500);
        }
    }

    /**
     * Export report data
     */
    public function exportReport(Request $request): JsonResponse
    {
        try {
            $userType = $request->attributes->get('jwt_user_type');

            if ($userType !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $filters = $request->only([
                'start_date',
                'end_date',
                'status',
                'priority',
                'creator_type'
            ]);

            $format = $request->input('format', 'json');
            $exportData = $this->reportService->exportReport($filters, $format);

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'format' => $format,
                'message' => 'Report exported successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to export report', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export report'
            ], 500);
        }
    }

    /**
     * Get task status distribution
     */
    public function getStatusDistribution(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'creator_type'
            ]);

            $statistics = $this->reportService->getTaskStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics['by_status'],
                'message' => 'Status distribution retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get status distribution', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve status distribution'
            ], 500);
        }
    }

    /**
     * Get priority distribution
     */
    public function getPriorityDistribution(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'creator_type'
            ]);

            $statistics = $this->reportService->getTaskStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics['by_priority'],
                'message' => 'Priority distribution retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get priority distribution', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve priority distribution'
            ], 500);
        }
    }

    /**
     * Get timeline statistics
     */
    public function getTimelineStatistics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'creator_type'
            ]);

            $statistics = $this->reportService->getTaskStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics['timeline'],
                'message' => 'Timeline statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get timeline statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve timeline statistics'
            ], 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'creator_type'
            ]);

            $statistics = $this->reportService->getTaskStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics['performance'],
                'message' => 'Performance metrics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get performance metrics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance metrics'
            ], 500);
        }
    }

    /**
     * Get reminder statistics
     */
    public function getReminderStatistics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date'
            ]);

            $statistics = $this->reportService->getTaskStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics['reminders'],
                'message' => 'Reminder statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get reminder statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reminder statistics'
            ], 500);
        }
    }

    /**
     * Get department statistics (Admin only)
     */
    public function getDepartmentStatistics(Request $request): JsonResponse
    {
        try {
            $userType = $request->attributes->get('jwt_user_type');

            if ($userType !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $filters = $request->only([
                'start_date',
                'end_date'
            ]);

            $statistics = $this->reportService->getTaskStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics['by_department'],
                'message' => 'Department statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get department statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve department statistics'
            ], 500);
        }
    }

    /**
     * Get task breakdown by class
     */
    public function getTaskBreakdownByClass(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'class_id',
                'department_id'
            ]);

            $statistics = $this->reportService->getTaskBreakdownByClass($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Task breakdown by class retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get task breakdown by class', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task breakdown by class'
            ], 500);
        }
    }

    /**
     * Get task breakdown by department
     */
    public function getTaskBreakdownByDepartment(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'department_id'
            ]);

            $statistics = $this->reportService->getTaskBreakdownByDepartment($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Task breakdown by department retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get task breakdown by department', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task breakdown by department'
            ], 500);
        }
    }

    /**
     * Get task submission rate
     */
    public function getTaskSubmissionRate(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'class_id',
                'department_id'
            ]);

            $statistics = $this->reportService->getTaskSubmissionRate($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Task submission rate retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get task submission rate', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task submission rate'
            ], 500);
        }
    }

    /**
     * Get task grading status
     */
    public function getTaskGradingStatus(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'class_id',
                'department_id'
            ]);

            $statistics = $this->reportService->getTaskGradingStatus($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Task grading status retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskStatisticsController: Failed to get task grading status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task grading status'
            ], 500);
        }
    }
}
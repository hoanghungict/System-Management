<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\ReportService;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

/**
 * Task Report Controller
 * 
 * Handles report generation and export functionality for tasks.
 * Supports multiple export formats and role-based access.
 * 
 * @package Modules\Task\app\Http\Controllers\Reports
 * @author System Management Team
 * @version 1.0.0
 */
class TaskReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly TaskServiceInterface $taskService
    ) {}

    /**
     * Get authenticated user ID from JWT payload
     */
    private function getUserId(Request $request): ?int
    {
        $userId = $request->attributes->get('jwt_user_id');
        return $userId ? (int)$userId : null;
    }

    /**
     * Get authenticated user type from JWT payload
     */
    private function getUserType(Request $request): ?string
    {
        return $request->attributes->get('jwt_user_type');
    }

    /**
     * Export tasks to Excel format
     */
    public function exportExcel(Request $request): Response
    {
        try {
            $userId = $this->getUserId($request);
            $userType = $this->getUserType($request);

            $filters = $request->only([
                'status', 'priority', 'creator_type', 'class_id', 
                'department_id', 'date_from', 'date_to', 'search'
            ]);

            // Add role-based filtering
            if ($userType === 'lecturer') {
                $filters['creator_id'] = $userId;
                $filters['creator_type'] = 'lecturer';
            } elseif ($userType === 'student') {
                $filters['receiver_id'] = $userId;
                $filters['receiver_type'] = 'student';
            }

            $tasks = $this->taskService->getAllTasks($filters, 1000); // Large limit for export

            $filename = 'tasks_export_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            $filepath = 'exports/' . $filename;

            // Generate Excel file using Laravel Excel or similar package
            $this->generateExcelFile($tasks, $filepath);

            return response()->download(Storage::path($filepath), $filename)->deleteFileAfterSend();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export Excel file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export tasks to PDF format
     */
    public function exportPdf(Request $request): Response
    {
        try {
            $userId = $this->getUserId($request);
            $userType = $this->getUserType($request);

            $filters = $request->only([
                'status', 'priority', 'creator_type', 'class_id', 
                'department_id', 'date_from', 'date_to', 'search'
            ]);

            // Add role-based filtering
            if ($userType === 'lecturer') {
                $filters['creator_id'] = $userId;
                $filters['creator_type'] = 'lecturer';
            } elseif ($userType === 'student') {
                $filters['receiver_id'] = $userId;
                $filters['receiver_type'] = 'student';
            }

            $tasks = $this->taskService->getAllTasks($filters, 1000);
            $statistics = $this->reportService->getOverviewStatistics($filters);

            $filename = 'tasks_report_' . now()->format('Y_m_d_H_i_s') . '.pdf';
            $filepath = 'exports/' . $filename;

            // Generate PDF file using DomPDF or similar package
            $this->generatePdfFile($tasks, $statistics, $filepath);

            return response()->download(Storage::path($filepath), $filename)->deleteFileAfterSend();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export PDF file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export tasks to CSV format
     */
    public function exportCsv(Request $request): Response
    {
        try {
            $userId = $this->getUserId($request);
            $userType = $this->getUserType($request);

            $filters = $request->only([
                'status', 'priority', 'creator_type', 'class_id', 
                'department_id', 'date_from', 'date_to', 'search'
            ]);

            // Add role-based filtering
            if ($userType === 'lecturer') {
                $filters['creator_id'] = $userId;
                $filters['creator_type'] = 'lecturer';
            } elseif ($userType === 'student') {
                $filters['receiver_id'] = $userId;
                $filters['receiver_type'] = 'student';
            }

            $tasks = $this->taskService->getAllTasks($filters, 1000);

            $filename = 'tasks_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
            $filepath = 'exports/' . $filename;

            $this->generateCsvFile($tasks, $filepath);

            return response()->download(Storage::path($filepath), $filename)->deleteFileAfterSend();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export CSV file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate comprehensive task report
     */
    public function generateComprehensiveReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date_from', 'date_to', 'class_id', 'department_id'
            ]);

            $report = [
                'overview' => $this->reportService->getOverviewStatistics($filters),
                'completion_rate' => $this->reportService->getTaskCompletionRate($filters),
                'priority_distribution' => $this->reportService->getTaskPriorityDistribution($filters),
                'status_distribution' => $this->reportService->getTaskStatusDistribution($filters),
                'trend_analysis' => $this->reportService->getTaskTrend('month', $filters),
                'class_breakdown' => $this->reportService->getTaskBreakdownByClass($filters),
                'department_breakdown' => $this->reportService->getTaskBreakdownByDepartment($filters),
                'submission_rate' => $this->reportService->getTaskSubmissionRate($filters),
                'grading_status' => $this->reportService->getTaskGradingStatus($filters),
                'dependency_statistics' => $this->reportService->getTaskDependencyStatistics($filters),
            ];

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Comprehensive report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate comprehensive report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate student progress report
     */
    public function generateStudentProgressReport(Request $request, int $studentId): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'class_id']);

            $report = [
                'student_statistics' => $this->reportService->getUserStatistics($studentId, 'student', $filters),
                'task_completion_rate' => $this->reportService->getTaskCompletionRate(array_merge($filters, [
                    'receiver_id' => $studentId,
                    'receiver_type' => 'student'
                ])),
                'submission_rate' => $this->reportService->getTaskSubmissionRate(array_merge($filters, [
                    'receiver_id' => $studentId,
                    'receiver_type' => 'student'
                ])),
            ];

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Student progress report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate student progress report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate class performance report
     */
    public function generateClassPerformanceReport(Request $request, int $classId): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to']);
            $filters['class_id'] = $classId;

            $report = [
                'class_statistics' => $this->reportService->getTaskBreakdownByClass($filters),
                'completion_rate' => $this->reportService->getTaskCompletionRate($filters),
                'submission_rate' => $this->reportService->getTaskSubmissionRate($filters),
                'grading_status' => $this->reportService->getTaskGradingStatus($filters),
            ];

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Class performance report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate class performance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available export formats
     */
    public function getExportFormats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'formats' => [
                    'excel' => [
                        'name' => 'Excel (.xlsx)',
                        'description' => 'Export tasks to Excel format with formatting',
                        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ],
                    'pdf' => [
                        'name' => 'PDF (.pdf)',
                        'description' => 'Export tasks to PDF format with charts and statistics',
                        'mime_type' => 'application/pdf'
                    ],
                    'csv' => [
                        'name' => 'CSV (.csv)',
                        'description' => 'Export tasks to CSV format for data analysis',
                        'mime_type' => 'text/csv'
                    ]
                ]
            ],
            'message' => 'Export formats retrieved successfully'
        ]);
    }

    /**
     * Generate Excel file (placeholder - implement with Laravel Excel)
     */
    private function generateExcelFile($tasks, string $filepath): void
    {
        // TODO: Implement with Laravel Excel package
        // This is a placeholder implementation
        Storage::put($filepath, 'Excel content placeholder');
    }

    /**
     * Generate PDF file (placeholder - implement with DomPDF)
     */
    private function generatePdfFile($tasks, $statistics, string $filepath): void
    {
        // TODO: Implement with DomPDF package
        // This is a placeholder implementation
        Storage::put($filepath, 'PDF content placeholder');
    }

    /**
     * Generate CSV file
     */
    private function generateCsvFile($tasks, string $filepath): void
    {
        $csvData = [];
        
        // Add header row
        $csvData[] = [
            'ID', 'Title', 'Description', 'Status', 'Priority', 'Creator', 
            'Receiver', 'Deadline', 'Created At', 'Updated At'
        ];

        // Add task data
        foreach ($tasks as $task) {
            $csvData[] = [
                $task->id,
                $task->title,
                $task->description,
                $task->status,
                $task->priority,
                $task->creator_type . ':' . $task->creator_id,
                $task->receiver_type . ':' . $task->receiver_id,
                $task->deadline ? $task->deadline->format('Y-m-d H:i:s') : '',
                $task->created_at ? $task->created_at->format('Y-m-d H:i:s') : '',
                $task->updated_at ? $task->updated_at->format('Y-m-d H:i:s') : ''
            ];
        }

        // Write CSV content
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        Storage::put($filepath, $csvContent);
    }

    /**
     * Get dashboard summary
     */
    public function getDashboardSummary(Request $request): JsonResponse
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

            $filters = $request->only(['start_date', 'end_date']);

            $summary = [
                'user_statistics' => $this->reportService->getUserStatistics($userId, $userType, $filters),
                'overview_statistics' => $this->reportService->getOverviewStatistics($filters),
                'recent_activities' => $this->getRecentActivitiesData($userId, $userType, $filters),
                'overdue_tasks' => $this->getOverdueTasksData($userId, $userType, $filters),
                'upcoming_deadlines' => $this->getUpcomingDeadlinesData($userId, $userType, $filters)
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Dashboard summary retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskReportController: Failed to get dashboard summary', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard summary'
            ], 500);
        }
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(Request $request): JsonResponse
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

            $filters = $request->only(['start_date', 'end_date', 'limit']);
            $limit = $filters['limit'] ?? 10;

            $activities = $this->getRecentActivitiesData($userId, $userType, $filters, $limit);

            return response()->json([
                'success' => true,
                'data' => $activities,
                'message' => 'Recent activities retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskReportController: Failed to get recent activities', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent activities'
            ], 500);
        }
    }

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(Request $request): JsonResponse
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

            $filters = $request->only(['start_date', 'end_date', 'limit']);
            $limit = $filters['limit'] ?? 20;

            $overdueTasks = $this->getOverdueTasksData($userId, $userType, $filters, $limit);

            return response()->json([
                'success' => true,
                'data' => $overdueTasks,
                'message' => 'Overdue tasks retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskReportController: Failed to get overdue tasks', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve overdue tasks'
            ], 500);
        }
    }

    /**
     * Get upcoming deadlines
     */
    public function getUpcomingDeadlines(Request $request): JsonResponse
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

            $filters = $request->only(['start_date', 'end_date', 'limit']);
            $limit = $filters['limit'] ?? 20;

            $upcomingDeadlines = $this->getUpcomingDeadlinesData($userId, $userType, $filters, $limit);

            return response()->json([
                'success' => true,
                'data' => $upcomingDeadlines,
                'message' => 'Upcoming deadlines retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('TaskReportController: Failed to get upcoming deadlines', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming deadlines'
            ], 500);
        }
    }

    /**
     * Get recent activities data
     */
    private function getRecentActivitiesData(int $userId, string $userType, array $filters = [], int $limit = 10): array
    {
        // This would typically query the database for recent activities
        // For now, returning mock data structure
        return [
            'activities' => [],
            'total_count' => 0,
            'has_more' => false
        ];
    }

    /**
     * Get overdue tasks data
     */
    private function getOverdueTasksData(int $userId, string $userType, array $filters = [], int $limit = 20): array
    {
        // This would typically query the database for overdue tasks
        // For now, returning mock data structure
        return [
            'overdue_tasks' => [],
            'total_count' => 0,
            'has_more' => false
        ];
    }

    /**
     * Get upcoming deadlines data
     */
    private function getUpcomingDeadlinesData(int $userId, string $userType, array $filters = [], int $limit = 20): array
    {
        // This would typically query the database for upcoming deadlines
        // For now, returning mock data structure
        return [
            'upcoming_deadlines' => [],
            'total_count' => 0,
            'has_more' => false
        ];
    }
}

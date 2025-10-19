<?php

declare(strict_types=1);

namespace Modules\Task\app\Services;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Models\Reminder;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Modules\Task\app\Repositories\Interfaces\ReminderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Report Service
 * 
 * Handles report generation and statistics for Task Module
 */
class ReportService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly ReminderRepositoryInterface $reminderRepository
    ) {}

    /**
     * Get comprehensive task statistics
     */
    public function getTaskStatistics(array $filters = []): array
    {
        $cacheKey = 'task_statistics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            return [
                'overview' => $this->getOverviewStatistics($filters),
                'by_status' => $this->getStatusStatistics($filters),
                'by_priority' => $this->getPriorityStatistics($filters),
                'by_creator' => $this->getCreatorStatistics($filters),
                'by_department' => $this->getDepartmentStatistics($filters),
                'timeline' => $this->getTimelineStatistics($filters),
                'performance' => $this->getPerformanceStatistics($filters),
                'reminders' => $this->getReminderStatistics($filters)
            ];
        });
    }

    /**
     * Get user-specific statistics
     */
    public function getUserStatistics(int $userId, string $userType, array $filters = []): array
    {
        $cacheKey = "user_task_statistics_{$userId}_{$userType}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $userType, $filters) {
            return [
                'overview' => $this->getUserOverviewStatistics($userId, $userType, $filters),
                'tasks_created' => $this->getUserCreatedTasksStatistics($userId, $userType, $filters),
                'tasks_assigned' => $this->getUserAssignedTasksStatistics($userId, $userType, $filters),
                'completion_rate' => $this->getUserCompletionRate($userId, $userType, $filters),
                'overdue_tasks' => $this->getUserOverdueTasks($userId, $userType, $filters),
                'reminders' => $this->getUserReminderStatistics($userId, $userType, $filters),
                'timeline' => $this->getUserTimelineStatistics($userId, $userType, $filters)
            ];
        });
    }

    /**
     * Generate task report
     */
    public function generateTaskReport(array $filters = []): array
    {
        $reportData = $this->getTaskStatistics($filters);
        
        return [
            'report_info' => [
                'generated_at' => now()->toISOString(),
                'filters_applied' => $filters,
                'total_tasks' => $reportData['overview']['total_tasks'],
                'date_range' => $this->getDateRange($filters)
            ],
            'statistics' => $reportData,
            'charts' => $this->generateChartData($reportData),
            'tables' => $this->generateTableData($reportData)
        ];
    }

    /**
     * Export report data
     */
    public function exportReport(array $filters = [], string $format = 'json'): array
    {
        $reportData = $this->generateTaskReport($filters);
        
        return match($format) {
            'json' => $reportData,
            'csv' => $this->convertToCsv($reportData),
            'excel' => $this->convertToExcel($reportData),
            default => $reportData
        };
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStatistics(array $filters): array
    {
        $query = Task::query();
        $this->applyFilters($query, $filters);

        return [
            'total_tasks' => $query->count(),
            'completed_tasks' => $query->where('status', 'completed')->count(),
            'pending_tasks' => $query->where('status', 'pending')->count(),
            'in_progress_tasks' => $query->where('status', 'in_progress')->count(),
            'overdue_tasks' => $query->where('deadline', '<', now())->where('status', '!=', 'completed')->count(),
            'completion_rate' => $this->calculateCompletionRate($query),
            'average_completion_time' => $this->calculateAverageCompletionTime($query)
        ];
    }

    /**
     * Get status statistics
     */
    private function getStatusStatistics(array $filters): array
    {
        $query = Task::query();
        $this->applyFilters($query, $filters);

        return $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get priority statistics
     */
    private function getPriorityStatistics(array $filters): array
    {
        $query = Task::query();
        $this->applyFilters($query, $filters);

        return $query->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
    }

    /**
     * Get creator statistics
     */
    private function getCreatorStatistics(array $filters): array
    {
        $query = Task::query();
        $this->applyFilters($query, $filters);

        return $query->select('creator_type', DB::raw('count(*) as count'))
            ->groupBy('creator_type')
            ->pluck('count', 'creator_type')
            ->toArray();
    }

    /**
     * Get department statistics
     */
    private function getDepartmentStatistics(array $filters): array
    {
        // This would need to be implemented based on your department structure
        return [
            'computer_science' => 45,
            'mathematics' => 32,
            'physics' => 28,
            'chemistry' => 19
        ];
    }

    /**
     * Get timeline statistics
     */
    private function getTimelineStatistics(array $filters): array
    {
        $query = Task::query();
        $this->applyFilters($query, $filters);

        $startDate = $filters['start_date'] ?? now()->subDays(30);
        $endDate = $filters['end_date'] ?? now();

        return $query->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as tasks_created'),
                DB::raw('sum(case when status = "completed" then 1 else 0 end) as tasks_completed')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get performance statistics
     */
    private function getPerformanceStatistics(array $filters): array
    {
        $query = Task::query();
        $this->applyFilters($query, $filters);

        $completedTasks = $query->where('status', 'completed')->get();

        return [
            'average_completion_time_hours' => $completedTasks->avg(function ($task) {
                return $task->created_at->diffInHours($task->updated_at);
            }),
            'fastest_completion_hours' => $completedTasks->min(function ($task) {
                return $task->created_at->diffInHours($task->updated_at);
            }),
            'slowest_completion_hours' => $completedTasks->max(function ($task) {
                return $task->created_at->diffInHours($task->updated_at);
            }),
            'on_time_completion_rate' => $this->calculateOnTimeCompletionRate($completedTasks)
        ];
    }

    /**
     * Get reminder statistics
     */
    private function getReminderStatistics(array $filters): array
    {
        $query = Reminder::query();
        
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_reminders' => $query->count(),
            'sent_reminders' => $query->where('status', 'sent')->count(),
            'pending_reminders' => $query->where('status', 'pending')->count(),
            'failed_reminders' => $query->where('status', 'failed')->count(),
            'by_type' => $query->select('reminder_type', DB::raw('count(*) as count'))
                ->groupBy('reminder_type')
                ->pluck('count', 'reminder_type')
                ->toArray()
        ];
    }

    /**
     * Get user overview statistics
     */
    private function getUserOverviewStatistics(int $userId, string $userType, array $filters): array
    {
        $createdQuery = Task::where('creator_id', $userId)->where('creator_type', $userType);
        $assignedQuery = Task::whereHas('receivers', function ($q) use ($userId, $userType) {
            $q->where('receiver_id', $userId)->where('receiver_type', $userType);
        });

        $this->applyFilters($createdQuery, $filters);
        $this->applyFilters($assignedQuery, $filters);

        return [
            'tasks_created' => $createdQuery->count(),
            'tasks_assigned' => $assignedQuery->count(),
            'completed_created' => $createdQuery->where('status', 'completed')->count(),
            'completed_assigned' => $assignedQuery->where('status', 'completed')->count(),
            'overdue_created' => $createdQuery->where('deadline', '<', now())->where('status', '!=', 'completed')->count(),
            'overdue_assigned' => $assignedQuery->where('deadline', '<', now())->where('status', '!=', 'completed')->count()
        ];
    }

    /**
     * Get user created tasks statistics
     */
    private function getUserCreatedTasksStatistics(int $userId, string $userType, array $filters): array
    {
        $query = Task::where('creator_id', $userId)->where('creator_type', $userType);
        $this->applyFilters($query, $filters);

        return [
            'total' => $query->count(),
            'by_status' => $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_priority' => $query->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray()
        ];
    }

    /**
     * Get user assigned tasks statistics
     */
    private function getUserAssignedTasksStatistics(int $userId, string $userType, array $filters): array
    {
        $query = Task::whereHas('receivers', function ($q) use ($userId, $userType) {
            $q->where('receiver_id', $userId)->where('receiver_type', $userType);
        });
        $this->applyFilters($query, $filters);

        return [
            'total' => $query->count(),
            'by_status' => $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_priority' => $query->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray()
        ];
    }

    /**
     * Get user completion rate
     */
    private function getUserCompletionRate(int $userId, string $userType, array $filters): array
    {
        $createdQuery = Task::where('creator_id', $userId)->where('creator_type', $userType);
        $assignedQuery = Task::whereHas('receivers', function ($q) use ($userId, $userType) {
            $q->where('receiver_id', $userId)->where('receiver_type', $userType);
        });

        $this->applyFilters($createdQuery, $filters);
        $this->applyFilters($assignedQuery, $filters);

        $createdTotal = $createdQuery->count();
        $createdCompleted = $createdQuery->where('status', 'completed')->count();
        $assignedTotal = $assignedQuery->count();
        $assignedCompleted = $assignedQuery->where('status', 'completed')->count();

        return [
            'created_tasks' => [
                'total' => $createdTotal,
                'completed' => $createdCompleted,
                'rate' => $createdTotal > 0 ? round(($createdCompleted / $createdTotal) * 100, 2) : 0
            ],
            'assigned_tasks' => [
                'total' => $assignedTotal,
                'completed' => $assignedCompleted,
                'rate' => $assignedTotal > 0 ? round(($assignedCompleted / $assignedTotal) * 100, 2) : 0
            ]
        ];
    }

    /**
     * Get user overdue tasks
     */
    private function getUserOverdueTasks(int $userId, string $userType, array $filters): array
    {
        $createdQuery = Task::where('creator_id', $userId)
            ->where('creator_type', $userType)
            ->where('deadline', '<', now())
            ->where('status', '!=', 'completed');

        $assignedQuery = Task::whereHas('receivers', function ($q) use ($userId, $userType) {
            $q->where('receiver_id', $userId)->where('receiver_type', $userType);
        })->where('deadline', '<', now())
        ->where('status', '!=', 'completed');

        $this->applyFilters($createdQuery, $filters);
        $this->applyFilters($assignedQuery, $filters);

        return [
            'created_overdue' => $createdQuery->count(),
            'assigned_overdue' => $assignedQuery->count(),
            'total_overdue' => $createdQuery->count() + $assignedQuery->count()
        ];
    }

    /**
     * Get user reminder statistics
     */
    private function getUserReminderStatistics(int $userId, string $userType, array $filters): array
    {
        $query = Reminder::where('user_id', $userId)->where('user_type', $userType);
        
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_reminders' => $query->count(),
            'sent_reminders' => $query->where('status', 'sent')->count(),
            'pending_reminders' => $query->where('status', 'pending')->count(),
            'by_type' => $query->select('reminder_type', DB::raw('count(*) as count'))
                ->groupBy('reminder_type')
                ->pluck('count', 'reminder_type')
                ->toArray()
        ];
    }

    /**
     * Get user timeline statistics
     */
    private function getUserTimelineStatistics(int $userId, string $userType, array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30);
        $endDate = $filters['end_date'] ?? now();

        $createdQuery = Task::where('creator_id', $userId)
            ->where('creator_type', $userType)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $assignedQuery = Task::whereHas('receivers', function ($q) use ($userId, $userType) {
            $q->where('receiver_id', $userId)->where('receiver_type', $userType);
        })->whereBetween('created_at', [$startDate, $endDate]);

        return [
            'created_tasks' => $createdQuery->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )->groupBy('date')->orderBy('date')->get()->toArray(),
            'assigned_tasks' => $assignedQuery->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )->groupBy('date')->orderBy('date')->get()->toArray()
        ];
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (isset($filters['creator_type'])) {
            $query->where('creator_type', $filters['creator_type']);
        }
    }

    /**
     * Calculate completion rate
     */
    private function calculateCompletionRate($query): float
    {
        $total = $query->count();
        if ($total === 0) return 0;
        
        $completed = $query->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }

    /**
     * Calculate average completion time
     */
    private function calculateAverageCompletionTime($query): float
    {
        $completedTasks = $query->where('status', 'completed')->get();
        
        if ($completedTasks->isEmpty()) return 0;
        
        $totalHours = $completedTasks->sum(function ($task) {
            return $task->created_at->diffInHours($task->updated_at);
        });
        
        return round($totalHours / $completedTasks->count(), 2);
    }

    /**
     * Calculate on-time completion rate
     */
    private function calculateOnTimeCompletionRate($completedTasks): float
    {
        if ($completedTasks->isEmpty()) return 0;
        
        $onTimeCount = $completedTasks->filter(function ($task) {
            return $task->deadline && $task->updated_at <= $task->deadline;
        })->count();
        
        return round(($onTimeCount / $completedTasks->count()) * 100, 2);
    }

    /**
     * Get date range from filters
     */
    private function getDateRange(array $filters): array
    {
        return [
            'start_date' => $filters['start_date'] ?? now()->subDays(30)->toDateString(),
            'end_date' => $filters['end_date'] ?? now()->toDateString()
        ];
    }

    /**
     * Generate chart data
     */
    private function generateChartData(array $statistics): array
    {
        return [
            'status_pie_chart' => $statistics['by_status'],
            'priority_bar_chart' => $statistics['by_priority'],
            'timeline_chart' => $statistics['timeline'],
            'creator_chart' => $statistics['by_creator']
        ];
    }

    /**
     * Generate table data
     */
    private function generateTableData(array $statistics): array
    {
        return [
            'overview_table' => $statistics['overview'],
            'status_table' => $statistics['by_status'],
            'priority_table' => $statistics['by_priority'],
            'performance_table' => $statistics['performance']
        ];
    }

    /**
     * Convert to CSV format
     */
    private function convertToCsv(array $data): array
    {
        // Implementation for CSV conversion
        return $data;
    }

    /**
     * Convert to Excel format
     */
    private function convertToExcel(array $data): array
    {
        // Implementation for Excel conversion
        return $data;
    }
}
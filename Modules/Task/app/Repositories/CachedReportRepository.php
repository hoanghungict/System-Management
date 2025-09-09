<?php

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Repositories\Interfaces\CachedReportRepositoryInterface;
use Modules\Task\app\Services\Interfaces\CacheServiceInterface;
use Modules\Task\app\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Cached Report Repository Implementation
 * 
 * Tuân thủ Clean Architecture: Implementation của CachedReportRepositoryInterface
 * Kết hợp cache với report operations
 */
class CachedReportRepository implements CachedReportRepositoryInterface
{
    public function __construct(
        private CacheServiceInterface $cacheService
    ) {}

    /**
     * Tạo task report với cache
     */
    public function generateTaskReport(array $filters, $user): array
    {
        $cacheKey = $this->cacheService->generateKey('task_report', [
            'filters' => $filters,
            'user_id' => $user->id ?? 0,
            'user_type' => $this->getUserType($user)
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($filters, $user) {
            $query = Task::with(['receivers']);
            
            // Apply filters
            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['creator_id'])) {
                $query->where('creator_id', $filters['creator_id']);
            }
            
            $tasks = $query->get();
            
            return [
                'total_tasks' => $tasks->count(),
                'completed_tasks' => $tasks->where('status', 'completed')->count(),
                'pending_tasks' => $tasks->where('status', 'pending')->count(),
                'in_progress_tasks' => $tasks->where('status', 'in_progress')->count(),
                'cancelled_tasks' => $tasks->where('status', 'cancelled')->count(),
                'completion_rate' => $tasks->count() > 0 ? 
                    round(($tasks->where('status', 'completed')->count() / $tasks->count()) * 100, 2) : 0,
                'tasks' => $tasks->toArray(),
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'background');
    }

    /**
     * Lấy task analytics với cache
     */
    public function getTaskAnalytics(array $params, $user): array
    {
        $cacheKey = $this->cacheService->generateKey('task_analytics', [
            'params' => $params,
            'user_id' => $user->id ?? 0,
            'user_type' => $this->getUserType($user)
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($params, $user) {
            $startDate = $params['start_date'] ?? now()->subDays(30)->toDateString();
            $endDate = $params['end_date'] ?? now()->toDateString();
            
            // Tasks created over time
            $tasksOverTime = DB::table('tasks')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray();
            
            // Tasks by status
            $tasksByStatus = DB::table('tasks')
                ->selectRaw('status, COUNT(*) as count')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            // Tasks by priority
            $tasksByPriority = DB::table('tasks')
                ->selectRaw('priority, COUNT(*) as count')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority')
                ->toArray();
            
            return [
                'tasks_over_time' => $tasksOverTime,
                'tasks_by_status' => $tasksByStatus,
                'tasks_by_priority' => $tasksByPriority,
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'background');
    }

    /**
     * Lấy user performance report với cache
     */
    public function getUserPerformanceReport(int $userId, string $userType, array $dateRange): array
    {
        $cacheKey = $this->cacheService->generateKey('user_performance', [
            'user_id' => $userId,
            'user_type' => $userType,
            'date_range' => $dateRange
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($userId, $userType, $dateRange) {
            $startDate = $dateRange['start'] ?? now()->subDays(30)->toDateString();
            $endDate = $dateRange['end'] ?? now()->toDateString();
            
            // Tasks received by user
            $receivedTasks = DB::table('tasks')
                ->join('task_receivers', 'tasks.id', '=', 'task_receivers.task_id')
                ->where('task_receivers.receiver_id', $userId)
                ->where('task_receivers.receiver_type', $userType)
                ->whereBetween('tasks.created_at', [$startDate, $endDate])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            // Tasks created by user (if lecturer)
            $createdTasks = [];
            if ($userType === 'lecturer') {
                $createdTasks = DB::table('tasks')
                    ->where('creator_id', $userId)
                    ->where('creator_type', $userType)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get()
                    ->pluck('count', 'status')
                    ->toArray();
            }
            
            $totalReceived = array_sum($receivedTasks);
            $completedReceived = $receivedTasks['completed'] ?? 0;
            
            return [
                'user_id' => $userId,
                'user_type' => $userType,
                'received_tasks' => $receivedTasks,
                'created_tasks' => $createdTasks,
                'completion_rate' => $totalReceived > 0 ? 
                    round(($completedReceived / $totalReceived) * 100, 2) : 0,
                'total_received' => $totalReceived,
                'total_created' => array_sum($createdTasks),
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'background');
    }

    /**
     * Lấy department performance report với cache
     */
    public function getDepartmentPerformanceReport(int $departmentId, array $dateRange): array
    {
        $cacheKey = $this->cacheService->generateKey('department_performance', [
            'department_id' => $departmentId,
            'date_range' => $dateRange
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($departmentId, $dateRange) {
            $startDate = $dateRange['start'] ?? now()->subDays(30)->toDateString();
            $endDate = $dateRange['end'] ?? now()->toDateString();
            
            // Get department lecturers
            $lecturerIds = DB::table('lecturers')
                ->where('department_id', $departmentId)
                ->pluck('id')
                ->toArray();
            
            // Tasks created by department lecturers
            $departmentTasks = DB::table('tasks')
                ->whereIn('creator_id', $lecturerIds)
                ->where('creator_type', 'lecturer')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            return [
                'department_id' => $departmentId,
                'tasks_by_status' => $departmentTasks,
                'total_tasks' => array_sum($departmentTasks),
                'lecturer_count' => count($lecturerIds),
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'background');
    }

    /**
     * Lấy class performance report với cache
     */
    public function getClassPerformanceReport(int $classId, array $dateRange): array
    {
        $cacheKey = $this->cacheService->generateKey('class_performance', [
            'class_id' => $classId,
            'date_range' => $dateRange
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($classId, $dateRange) {
            $startDate = $dateRange['start'] ?? now()->subDays(30)->toDateString();
            $endDate = $dateRange['end'] ?? now()->toDateString();
            
            // Get class students
            $studentIds = DB::table('students')
                ->where('class_id', $classId)
                ->pluck('id')
                ->toArray();
            
            // Tasks received by class students
            $classTasks = DB::table('tasks')
                ->join('task_receivers', 'tasks.id', '=', 'task_receivers.task_id')
                ->whereIn('task_receivers.receiver_id', $studentIds)
                ->where('task_receivers.receiver_type', 'student')
                ->whereBetween('tasks.created_at', [$startDate, $endDate])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            $totalTasks = array_sum($classTasks);
            $completedTasks = $classTasks['completed'] ?? 0;
            
            return [
                'class_id' => $classId,
                'tasks_by_status' => $classTasks,
                'total_tasks' => $totalTasks,
                'completion_rate' => $totalTasks > 0 ? 
                    round(($completedTasks / $totalTasks) * 100, 2) : 0,
                'student_count' => count($studentIds),
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'background');
    }

    /**
     * Lấy task completion statistics với cache
     */
    public function getTaskCompletionStatistics(array $filters): array
    {
        $cacheKey = $this->cacheService->generateKey('task_completion_stats', ['filters' => $filters]);
        
        return $this->cacheService->remember($cacheKey, function () use ($filters) {
            $startDate = $filters['start_date'] ?? now()->subDays(30)->toDateString();
            $endDate = $filters['end_date'] ?? now()->toDateString();
            
            $stats = DB::table('tasks')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_tasks,
                    SUM(CASE WHEN deadline < NOW() AND status != "completed" THEN 1 ELSE 0 END) as overdue_tasks
                ')
                ->first();
            
            $completionRate = $stats->total_tasks > 0 ? 
                round(($stats->completed_tasks / $stats->total_tasks) * 100, 2) : 0;
            
            return [
                'total_tasks' => $stats->total_tasks,
                'completed_tasks' => $stats->completed_tasks,
                'pending_tasks' => $stats->pending_tasks,
                'in_progress_tasks' => $stats->in_progress_tasks,
                'cancelled_tasks' => $stats->cancelled_tasks,
                'overdue_tasks' => $stats->overdue_tasks,
                'completion_rate' => $completionRate,
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'background');
    }

    /**
     * Lấy task distribution analytics với cache
     */
    public function getTaskDistributionAnalytics(array $filters): array
    {
        $cacheKey = $this->cacheService->generateKey('task_distribution', ['filters' => $filters]);
        
        return $this->cacheService->remember($cacheKey, function () use ($filters) {
            $startDate = $filters['start_date'] ?? now()->subDays(30)->toDateString();
            $endDate = $filters['end_date'] ?? now()->toDateString();
            
            // Distribution by receiver type
            $byReceiverType = DB::table('tasks')
                ->join('task_receivers', 'tasks.id', '=', 'task_receivers.task_id')
                ->whereBetween('tasks.created_at', [$startDate, $endDate])
                ->selectRaw('receiver_type, COUNT(*) as count')
                ->groupBy('receiver_type')
                ->get()
                ->pluck('count', 'receiver_type')
                ->toArray();
            
            // Distribution by priority
            $byPriority = DB::table('tasks')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority')
                ->toArray();
            
            return [
                'by_receiver_type' => $byReceiverType,
                'by_priority' => $byPriority,
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'background');
    }

    /**
     * Lấy deadline adherence report với cache
     */
    public function getDeadlineAdherenceReport(array $filters): array
    {
        $cacheKey = $this->cacheService->generateKey('deadline_adherence', ['filters' => $filters]);
        
        return $this->cacheService->remember($cacheKey, function () use ($filters) {
            $startDate = $filters['start_date'] ?? now()->subDays(30)->toDateString();
            $endDate = $filters['end_date'] ?? now()->toDateString();
            
            $adherence = DB::table('tasks')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = "completed" AND updated_at <= deadline THEN 1 ELSE 0 END) as on_time_completed,
                    SUM(CASE WHEN status = "completed" AND updated_at > deadline THEN 1 ELSE 0 END) as late_completed,
                    SUM(CASE WHEN deadline < NOW() AND status != "completed" THEN 1 ELSE 0 END) as overdue_tasks
                ')
                ->first();
            
            $onTimeRate = $adherence->total_tasks > 0 ? 
                round(($adherence->on_time_completed / $adherence->total_tasks) * 100, 2) : 0;
            
            return [
                'total_tasks' => $adherence->total_tasks,
                'on_time_completed' => $adherence->on_time_completed,
                'late_completed' => $adherence->late_completed,
                'overdue_tasks' => $adherence->overdue_tasks,
                'on_time_rate' => $onTimeRate,
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'background');
    }

    /**
     * Lấy productivity metrics với cache
     */
    public function getProductivityMetrics(array $filters): array
    {
        $cacheKey = $this->cacheService->generateKey('productivity_metrics', ['filters' => $filters]);
        
        return $this->cacheService->remember($cacheKey, function () use ($filters) {
            $startDate = $filters['start_date'] ?? now()->subDays(30)->toDateString();
            $endDate = $filters['end_date'] ?? now()->toDateString();
            
            // Average completion time
            $avgCompletionTime = DB::table('tasks')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
                ->first()
                ->avg_hours ?? 0;
            
            // Tasks per day
            $tasksPerDay = DB::table('tasks')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('COUNT(*) / DATEDIFF(?, ?) as tasks_per_day', [$endDate, $startDate])
                ->first()
                ->tasks_per_day ?? 0;
            
            return [
                'avg_completion_time_hours' => round($avgCompletionTime, 2),
                'tasks_per_day' => round($tasksPerDay, 2),
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'background');
    }

    /**
     * Lấy system overview dashboard với cache
     */
    public function getSystemOverviewDashboard(): array
    {
        $cacheKey = $this->cacheService->generateKey('system_overview');
        
        return $this->cacheService->remember($cacheKey, function () {
            $today = now()->toDateString();
            $thisWeek = now()->startOfWeek()->toDateString();
            $thisMonth = now()->startOfMonth()->toDateString();
            
            // Today's stats
            $todayStats = DB::table('tasks')
                ->whereDate('created_at', $today)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
                ->first();
            
            // This week's stats
            $weekStats = DB::table('tasks')
                ->where('created_at', '>=', $thisWeek)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
                ->first();
            
            // This month's stats
            $monthStats = DB::table('tasks')
                ->where('created_at', '>=', $thisMonth)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
                ->first();
            
            // Overall stats
            $overallStats = DB::table('tasks')
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
                ->first();
            
            return [
                'today' => [
                    'total_tasks' => $todayStats->total,
                    'completed_tasks' => $todayStats->completed,
                    'completion_rate' => $todayStats->total > 0 ? 
                        round(($todayStats->completed / $todayStats->total) * 100, 2) : 0
                ],
                'this_week' => [
                    'total_tasks' => $weekStats->total,
                    'completed_tasks' => $weekStats->completed,
                    'completion_rate' => $weekStats->total > 0 ? 
                        round(($weekStats->completed / $weekStats->total) * 100, 2) : 0
                ],
                'this_month' => [
                    'total_tasks' => $monthStats->total,
                    'completed_tasks' => $monthStats->completed,
                    'completion_rate' => $monthStats->total > 0 ? 
                        round(($monthStats->completed / $monthStats->total) * 100, 2) : 0
                ],
                'overall' => [
                    'total_tasks' => $overallStats->total,
                    'completed_tasks' => $overallStats->completed,
                    'completion_rate' => $overallStats->total > 0 ? 
                        round(($overallStats->completed / $overallStats->total) * 100, 2) : 0
                ],
                'generated_at' => now()->toDateTimeString()
            ];
        }, 'critical');
    }

    /**
     * Xóa cache cho user reports
     */
    public function clearUserReportCache(int $userId, string $userType): bool
    {
        $patterns = [
            'task_report:*user_id=' . $userId . '*',
            'task_analytics:*user_id=' . $userId . '*',
            'user_performance:*user_id=' . $userId . '*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('User report cache cleared', [
            'user_id' => $userId,
            'user_type' => $userType
        ]);
        
        return true;
    }

    /**
     * Xóa cache cho faculty reports
     */
    public function clearDepartmentReportCache(int $departmentId): bool
    {
        $patterns = [
            'department_performance:*department_id=' . $departmentId . '*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('Department report cache cleared', ['department_id' => $departmentId]);
        
        return true;
    }

    /**
     * Xóa cache cho class reports
     */
    public function clearClassReportCache(int $classId): bool
    {
        $patterns = [
            'class_performance:*class_id=' . $classId . '*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('Class report cache cleared', ['class_id' => $classId]);
        
        return true;
    }

    /**
     * Xóa tất cả report cache
     */
    public function clearAllReportCache(): bool
    {
        $patterns = [
            'task_report:*',
            'task_analytics:*',
            'user_performance:*',
            'department_performance:*',
            'class_performance:*',
            'task_completion_stats:*',
            'task_distribution:*',
            'deadline_adherence:*',
            'productivity_metrics:*',
            'system_overview:*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('All report cache cleared');
        
        return true;
    }

    /**
     * Lấy loại user từ model
     */
    private function getUserType($user): string
    {
        // Kiểm tra nếu user là admin (có is_admin = true)
        if (isset($user->account) && isset($user->account['is_admin']) && $user->account['is_admin']) {
            return 'admin';
        }
        
        // Nếu user có user_type property (từ JWT)
        if (isset($user->user_type)) {
            return $user->user_type;
        }
        
        // Kiểm tra instance của model
        if ($user instanceof \Modules\Auth\app\Models\Lecturer) {
            return 'lecturer';
        } elseif ($user instanceof \Modules\Auth\app\Models\Student) {
            return 'student';
        } elseif ($user instanceof \Modules\Auth\app\Models\LecturerAccount) {
            return 'lecturer';
        } elseif ($user instanceof \Modules\Auth\app\Models\StudentAccount) {
            return 'student';
        }
        
        return 'unknown';
    }
}

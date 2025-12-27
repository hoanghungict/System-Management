<?php

namespace Modules\Task\app\Admin\Services;

use Modules\Task\app\Models\Task;
use Modules\Auth\app\Models\Lecturer;
use Modules\Task\app\Models\TaskReceiver;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Department;
use Modules\Task\app\Services\PermissionService;

/**
 * Admin Task Service
 * 
 * Handles admin-specific business logic for task operations
 * Following Clean Architecture principles
 */
class AdminTaskService
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Get all tasks with admin privileges
     * 
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllTasks(array $filters = [], int $perPage = 15)
    {
        $query = Task::with(['receivers', 'files']);

        // Apply filters
        if (isset($filters['receiver_id'])) {
            $query->whereHas('receivers', function($q) use ($filters) {
                $q->where('receiver_id', $filters['receiver_id']);
            });
        }

        if (isset($filters['receiver_type'])) {
            $query->whereHas('receivers', function($q) use ($filters) {
                $q->where('receiver_type', $filters['receiver_type']);
            });
        }

        if (isset($filters['creator_id'])) {
            $query->where('creator_id', $filters['creator_id']);
        }

        if (isset($filters['creator_type'])) {
            $query->where('creator_type', $filters['creator_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get overview statistics for admin dashboard
     * 
     * @return array
     */
    public function getOverviewStatistics(): array
    {
        $totalTasks = Task::count();
        $totalLecturers = Lecturer::count();
        $totalStudents = Student::count();

        $tasksByStatus = Task::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $overdueTasks = Task::where('deadline', '<', now())
            ->where('status', '!=', 'completed')
            ->count();

        $tasksThisMonth = Task::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $tasksThisWeek = Task::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        return [
            'total_tasks' => $totalTasks,
            'total_lecturers' => $totalLecturers,
            'total_students' => $totalStudents,
            'tasks_by_status' => $tasksByStatus,
            'overdue_tasks' => $overdueTasks,
            'tasks_this_month' => $tasksThisMonth,
            'tasks_this_week' => $tasksThisWeek
        ];
    }
}

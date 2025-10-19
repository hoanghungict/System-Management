<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\ReportService;

/**
 * Student Task Controller
 * 
 * Handles task viewing and submission operations for students.
 * Provides read-only access to assigned tasks and submission capabilities.
 * 
 * @package Modules\Task\app\Http\Controllers\Student
 * @author System Management Team
 * @version 1.0.0
 */
class StudentTaskController extends Controller
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly ReportService $reportService
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
     * Get tasks assigned to the student
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->only([
                'status', 'priority', 'class_id', 'date_from', 'date_to', 'search'
            ]);

            // Add student filter
            $filters['receiver_id'] = $userId;
            $filters['receiver_type'] = 'student';

            $tasks = $this->taskService->getTasksByReceiver($userId, 'student', $filters, $request->get('limit', 20));

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'Assigned tasks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific task assigned to the student
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Check if task is assigned to this student
            if ($task->receiver_id !== $userId || $task->receiver_type !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Task retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's personal dashboard data
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->only(['date_from', 'date_to', 'class_id']);

            $statistics = $this->reportService->getUserStatistics($userId, 'student', $filters);

            // Get recent tasks
            $recentTasks = $this->taskService->getTasksByReceiver(
                $userId, 
                'student', 
                array_merge($filters, ['limit' => 5])
            );

            // Get upcoming deadlines
            $upcomingTasks = $this->taskService->getTasksByReceiver(
                $userId, 
                'student', 
                array_merge($filters, [
                    'status' => ['pending', 'in_progress'],
                    'deadline_from' => now(),
                    'deadline_to' => now()->addDays(7),
                    'limit' => 10
                ])
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'recent_tasks' => $recentTasks,
                    'upcoming_deadlines' => $upcomingTasks
                ],
                'message' => 'Dashboard data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tasks by status
     */
    public function getTasksByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validStatuses = ['pending', 'in_progress', 'completed', 'overdue'];
            
            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status'
                ], 400);
            }

            $filters = $request->only(['class_id', 'date_from', 'date_to', 'search']);
            $filters['status'] = $status;
            $filters['receiver_id'] = $userId;
            $filters['receiver_type'] = 'student';

            $tasks = $this->taskService->getTasksByReceiver($userId, 'student', $filters, $request->get('limit', 20));

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => "Tasks with status '{$status}' retrieved successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks by status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->only(['class_id', 'search']);
            $filters['status'] = 'overdue';
            $filters['receiver_id'] = $userId;
            $filters['receiver_type'] = 'student';

            $tasks = $this->taskService->getTasksByReceiver($userId, 'student', $filters, $request->get('limit', 20));

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'Overdue tasks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve overdue tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tasks due soon (within next 7 days)
     */
    public function getDueSoonTasks(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->only(['class_id', 'search']);
            $filters['status'] = ['pending', 'in_progress'];
            $filters['receiver_id'] = $userId;
            $filters['receiver_type'] = 'student';
            $filters['deadline_from'] = now();
            $filters['deadline_to'] = now()->addDays(7);

            $tasks = $this->taskService->getTasksByReceiver($userId, 'student', $filters, $request->get('limit', 20));

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'Tasks due soon retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks due soon',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's task progress statistics
     */
    public function getProgressStatistics(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->only(['date_from', 'date_to', 'class_id']);

            $statistics = $this->reportService->getUserStatistics($userId, 'student', $filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Progress statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve progress statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search tasks assigned to student
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $request->validate([
                'query' => 'required|string|min:2|max:100'
            ]);

            $filters = $request->only(['status', 'priority', 'class_id', 'date_from', 'date_to']);
            $filters['search'] = $request->query;
            $filters['receiver_id'] = $userId;
            $filters['receiver_type'] = 'student';

            $tasks = $this->taskService->getTasksByReceiver($userId, 'student', $filters, $request->get('limit', 20));

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'Search results retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task calendar view for student
     */
    public function getCalendar(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->only(['month', 'year', 'class_id']);
            $filters['receiver_id'] = $userId;
            $filters['receiver_type'] = 'student';

            // Set default to current month if not provided
            $month = $filters['month'] ?? now()->month;
            $year = $filters['year'] ?? now()->year;

            $filters['date_from'] = now()->setYear($year)->setMonth($month)->startOfMonth();
            $filters['date_to'] = now()->setYear($year)->setMonth($month)->endOfMonth();

            $tasks = $this->taskService->getTasksByReceiver($userId, 'student', $filters, 100);

            // Group tasks by date for calendar display
            $calendarData = $tasks->groupBy(function ($task) {
                return $task->deadline ? $task->deadline->format('Y-m-d') : 'no-deadline';
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'tasks' => $calendarData
                ],
                'message' => 'Calendar data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve calendar data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

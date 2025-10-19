<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\ReportService;
use Modules\Task\app\Http\Requests\TaskRequest;

/**
 * Admin Task Controller
 * 
 * Handles all task management operations for administrators.
 * Provides full CRUD access to all tasks across the system.
 * 
 * @package Modules\Task\app\Http\Controllers\Admin
 * @author System Management Team
 * @version 1.0.0
 */
class AdminTaskController extends Controller
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
     * Get authenticated user data from JWT payload
     */
    private function getUserData(Request $request): ?\stdClass
    {
        return $request->attributes->get('jwt_payload');
    }

    /**
     * Get all tasks in the system (Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'priority', 'creator_type', 'class_id', 
                'department_id', 'date_from', 'date_to', 'search'
            ]);

            $tasks = $this->taskService->getAllTasks($filters, $request->get('limit', 20));

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'All tasks retrieved successfully'
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
     * Create a new task (Admin can create for anyone)
     */
    public function store(TaskRequest $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            $userData = $this->getUserData($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $data = $request->validated();
            $data['creator_id'] = $userId;
            $data['creator_type'] = 'admin';

            $task = $this->taskService->createTask($data, $userData);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Task created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific task (Admin can view any task)
     */
    public function show(int $id): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
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
     * Update any task (Admin can modify any task)
     */
    public function update(TaskRequest $request, int $id): JsonResponse
    {
        try {
            $userData = $this->getUserData($request);
            
            if (!$userData) {
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

            $data = $request->validated();
            $updatedTask = $this->taskService->updateTask($task, $data, $userData);

            return response()->json([
                'success' => true,
                'data' => $updatedTask,
                'message' => 'Task updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete any task (Admin can delete any task)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $userData = $this->getUserData($request);
            
            if (!$userData) {
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

            $this->taskService->deleteTask($task, $userData);

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system-wide task statistics (Admin only)
     */
    public function getSystemStatistics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'class_id', 'department_id']);
            
            $statistics = $this->reportService->getOverviewStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'System statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Override task status (Admin only)
     */
    public function overrideStatus(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|string|in:pending,in_progress,completed,cancelled,overdue'
            ]);

            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $task->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Task status overridden successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to override task status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations on tasks (Admin only)
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $userData = $this->getUserData($request);
            
            if (!$userData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $request->validate([
                'action' => 'required|string|in:delete,update_status,assign',
                'task_ids' => 'required|array|min:1',
                'task_ids.*' => 'integer|exists:task,id',
                'data' => 'nullable|array'
            ]);

            $action = $request->action;
            $taskIds = $request->task_ids;
            $data = $request->data ?? [];

            $results = [];

            foreach ($taskIds as $taskId) {
                $task = $this->taskService->getTaskById($taskId);
                
                if ($task) {
                    switch ($action) {
                        case 'delete':
                            $this->taskService->deleteTask($task, $userData);
                            $results[] = ['id' => $taskId, 'status' => 'deleted'];
                            break;
                        case 'update_status':
                            if (isset($data['status'])) {
                                $task->update(['status' => $data['status']]);
                                $results[] = ['id' => $taskId, 'status' => 'updated'];
                            }
                            break;
                        case 'assign':
                            if (isset($data['receiver_id']) && isset($data['receiver_type'])) {
                                $task->update([
                                    'receiver_id' => $data['receiver_id'],
                                    'receiver_type' => $data['receiver_type']
                                ]);
                                $results[] = ['id' => $taskId, 'status' => 'assigned'];
                            }
                            break;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Bulk action completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

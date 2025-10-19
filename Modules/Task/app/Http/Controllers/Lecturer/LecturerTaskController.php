<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\ReportService;
use Modules\Task\app\Http\Requests\TaskRequest;

/**
 * Lecturer Task Controller
 * 
 * Handles task management operations for lecturers.
 * Provides access to tasks for their classes and students.
 * 
 * @package Modules\Task\app\Http\Controllers\Lecturer
 * @author System Management Team
 * @version 1.0.0
 */
class LecturerTaskController extends Controller
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
     * Get tasks created by the lecturer
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

            // Add lecturer filter
            $filters['creator_id'] = $userId;
            $filters['creator_type'] = 'lecturer';

            $tasks = $this->taskService->getTasksByCreator($userId, 'lecturer', $filters, $request->get('limit', 20));

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'Lecturer tasks retrieved successfully'
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
     * Create a new task for lecturer's classes
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
            $data['creator_type'] = 'lecturer';

            // Validate that lecturer can create tasks for the specified class
            if (isset($data['class_id'])) {
                // Add validation logic here to check if lecturer teaches this class
                // This would typically involve checking a lecturer_class relationship
            }

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
     * Get a specific task (only if created by lecturer)
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

            // Check if lecturer created this task
            if ($task->creator_id !== $userId || $task->creator_type !== 'lecturer') {
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
     * Update a task (only if created by lecturer)
     */
    public function update(TaskRequest $request, int $id): JsonResponse
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

            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Check if lecturer created this task
            if ($task->creator_id !== $userId || $task->creator_type !== 'lecturer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
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
     * Delete a task (only if created by lecturer)
     */
    public function destroy(Request $request, int $id): JsonResponse
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

            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Check if lecturer created this task
            if ($task->creator_id !== $userId || $task->creator_type !== 'lecturer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
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
     * Get tasks for a specific class
     */
    public function getClassTasks(Request $request, int $classId): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->only(['status', 'priority', 'date_from', 'date_to']);
            $filters['class_id'] = $classId;
            $filters['creator_id'] = $userId;
            $filters['creator_type'] = 'lecturer';

            $tasks = $this->taskService->getTasksByCreator($userId, 'lecturer', $filters, $request->get('limit', 20));

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'Class tasks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get class task statistics
     */
    public function getClassStatistics(Request $request, int $classId): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to']);
            $filters['class_id'] = $classId;

            $statistics = $this->reportService->getTaskBreakdownByClass($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Class statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lecturer's task creation statistics
     */
    public function getCreationStatistics(Request $request): JsonResponse
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

            $statistics = $this->reportService->getCreatedTaskStatistics($userId, 'lecturer', $filters);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Creation statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve creation statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate a task
     */
    public function duplicate(Request $request, int $id): JsonResponse
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

            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Check if lecturer created this task
            if ($task->creator_id !== $userId || $task->creator_type !== 'lecturer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $data = $task->toArray();
            unset($data['id'], $data['created_at'], $data['updated_at']);
            
            // Update title to indicate it's a copy
            $data['title'] = $data['title'] . ' (Copy)';
            $data['status'] = 'pending';
            $data['creator_id'] = $userId;
            $data['creator_type'] = 'lecturer';

            $newTask = $this->taskService->createTask($data, $userData);

            return response()->json([
                'success' => true,
                'data' => $newTask,
                'message' => 'Task duplicated successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate task',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

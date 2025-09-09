<?php

namespace Modules\Task\app\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Task\app\Admin\UseCases\ForceDeleteTaskUseCase;
use Modules\Task\app\Admin\UseCases\RestoreTaskUseCase;
use Modules\Task\app\Admin\UseCases\AssignTaskToLecturersUseCase;
use Modules\Task\app\Admin\UseCases\GetAssignedTasksUseCase;
use Modules\Task\app\Admin\UseCases\GetTaskDetailUseCase;
use Modules\Task\app\Admin\UseCases\CheckAdminRoleUseCase;
use Modules\Task\app\Admin\Services\AdminTaskService;

/**
 * Admin Task Controller
 * 
 * Handles admin-specific task operations following Clean Architecture
 * All methods require admin permissions
 */
class AdminTaskController
{
    public function __construct(
        private ForceDeleteTaskUseCase $forceDeleteTaskUseCase,
        private RestoreTaskUseCase $restoreTaskUseCase,
        private AssignTaskToLecturersUseCase $assignTaskToLecturersUseCase,
        private GetAssignedTasksUseCase $getAssignedTasksUseCase,
        private GetTaskDetailUseCase $getTaskDetailUseCase,
        private CheckAdminRoleUseCase $checkAdminRoleUseCase,
        private AdminTaskService $adminTaskService
    ) {}

    /**
     * Force delete a task (Admin only)
     */
    public function forceDelete(Request $request, $taskId): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            $result = $this->forceDeleteTaskUseCase->execute($taskId, $userId, $userType);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required' ? 403 : 500);
        }
    }

    /**
     * Restore a soft deleted task (Admin only)
     */
    public function restore(Request $request, $taskId): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            $result = $this->restoreTaskUseCase->execute($taskId, $userId, $userType);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required' ? 403 : 500);
        }
    }

    /**
     * Assign task to lecturers (Admin only)
     */
    public function assignTaskToLecturers(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            $result = $this->assignTaskToLecturersUseCase->execute(
                $request->input('task_id'),
                $request->input('lecturer_ids', []),
                $userId,
                $userType
            );

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required' ? 403 : 500);
        }
    }

    /**
     * Get assigned tasks (Admin only)
     */
    public function getAssignedTasks(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');
            $perPage = $request->get('per_page', 15);

            $result = $this->getAssignedTasksUseCase->execute($userId, $userType, $perPage);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required' ? 403 : 500);
        }
    }

    /**
     * Get task detail for admin (Admin only)
     */
    public function getTaskDetail(Request $request, $taskId): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            $result = $this->getTaskDetailUseCase->execute((int)$taskId, $userId, $userType);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required' ? 403 : 500);
        }
    }

    /**
     * Check admin role (Admin only)
     */
    public function checkAdminRole(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            $result = $this->checkAdminRoleUseCase->execute($userId, $userType);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lecturers for admin assignment
     */
    public function getLecturers(Request $request): JsonResponse
    {
        try {
            $lecturers = $this->adminTaskService->getLecturers();

            return response()->json([
                'success' => true,
                'message' => 'Lecturers retrieved successfully',
                'data' => $lecturers,
                'data_count' => $lecturers->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving lecturers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all tasks (index method for resource route)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['receiver_id', 'receiver_type', 'creator_id', 'creator_type', 'search', 'status']);
            $perPage = $request->get('per_page', 15);
            
            $tasks = $this->adminTaskService->getAllTasks($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $tasks->items(),
                'data_count' => $tasks->total(),
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage()
                ],
                'message' => 'Tasks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get departments for admin
     */
    public function getDepartments(Request $request): JsonResponse
    {
        try {
            $departments = $this->adminTaskService->getDepartments();

            return response()->json([
                'success' => true,
                'message' => 'Departments retrieved successfully',
                'data' => $departments,
                'data_count' => $departments->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving departments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all tasks for admin
     */
    public function getAllTasks(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['receiver_id', 'receiver_type', 'creator_id', 'creator_type', 'search', 'status']);
            $perPage = $request->get('per_page', 15);
            
            $tasks = $this->adminTaskService->getAllTasks($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $tasks->items(),
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage()
                ],
                'message' => 'All tasks retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overview statistics for admin
     */
    public function getOverviewStatistics(Request $request): JsonResponse
    {
        try {
            $stats = $this->adminTaskService->getOverviewStatistics();
            
            return response()->json([
                'success' => true,
                'message' => 'Overview task statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}

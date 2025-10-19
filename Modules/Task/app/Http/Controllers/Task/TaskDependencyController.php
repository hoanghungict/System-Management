<?php

namespace Modules\Task\app\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use Modules\Task\app\Http\Requests\TaskDependencyRequest;
use Modules\Task\app\Services\Interfaces\TaskDependencyServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskDependencyController extends Controller
{
    protected $taskDependencyService;

    public function __construct(TaskDependencyServiceInterface $taskDependencyService)
    {
        $this->taskDependencyService = $taskDependencyService;
    }

    /**
     * Lấy tất cả dependencies
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $dependencies = $this->taskDependencyService->getAllDependencies();

            return response()->json([
                'success' => true,
                'message' => 'Dependencies retrieved successfully',
                'data' => $dependencies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dependencies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo dependency mới
     */
    public function store(TaskDependencyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Thêm thông tin người tạo
            $data['created_by'] = Auth::id();
            $data['created_by_type'] = Auth::user()->user_type ?? 'admin';

            $dependency = $this->taskDependencyService->createDependency($data);

            return response()->json([
                'success' => true,
                'message' => 'Dependency created successfully',
                'data' => $dependency
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create dependency',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Lấy dependency theo ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $dependency = $this->taskDependencyService->getDependencyById($id);

            if (!$dependency) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dependency not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dependency retrieved successfully',
                'data' => $dependency
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dependency',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật dependency
     */
    public function update(TaskDependencyRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $dependency = $this->taskDependencyService->updateDependency($id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Dependency updated successfully',
                'data' => $dependency
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update dependency',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Xóa dependency
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->taskDependencyService->deleteDependency($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dependency not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dependency deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete dependency',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy dependencies của một task
     */
    public function getTaskDependencies(int $taskId): JsonResponse
    {
        try {
            $dependencies = $this->taskDependencyService->getTaskDependencies($taskId);

            return response()->json([
                'success' => true,
                'message' => 'Task dependencies retrieved successfully',
                'data' => [
                    'dependencies' => $dependencies
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task dependencies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy dependencies với thông tin chi tiết
     */
    public function getTaskWithDependencies(Request $request, int $taskId): JsonResponse
    {
        try {
            $taskWithDependencies = $this->taskDependencyService->getTaskWithDependencies($taskId);

            if (!$taskWithDependencies) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task with dependencies retrieved successfully',
                'data' => $taskWithDependencies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task with dependencies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate dependency
     */
    public function validateDependency(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'predecessor_task_id' => 'required|integer|exists:tasks,id',
                'successor_task_id' => 'required|integer|exists:tasks,id',
                'dependency_type' => 'required|string|in:finish_to_start,start_to_start,finish_to_finish,start_to_finish',
                'lag_days' => 'integer|min:0|max:365'
            ]);

            $validation = $this->taskDependencyService->validateDependency($data);

            return response()->json([
                'success' => true,
                'message' => 'Dependency validation completed',
                'data' => $validation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate dependency',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Kiểm tra xem task có thể bắt đầu không
     */
    public function canTaskStart(int $taskId): JsonResponse
    {
        try {
            $canStart = $this->taskDependencyService->canTaskStart($taskId);

            return response()->json([
                'success' => true,
                'message' => 'Task start status checked',
                'data' => [
                    'canStart' => $canStart
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check task start status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tasks bị block bởi task hiện tại
     */
    public function getBlockedTasks(int $taskId): JsonResponse
    {
        try {
            $blockedTasks = $this->taskDependencyService->getBlockedTasks($taskId);

            return response()->json([
                'success' => true,
                'message' => 'Blocked tasks retrieved successfully',
                'data' => [
                    'dependencies' => $blockedTasks
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve blocked tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy dependency chain
     */
    public function getDependencyChain(int $taskId): JsonResponse
    {
        try {
            $chain = $this->taskDependencyService->getDependencyChain($taskId);

            return response()->json([
                'success' => true,
                'message' => 'Dependency chain retrieved successfully',
                'data' => [
                    'chain' => $chain
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dependency chain',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê dependencies
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->taskDependencyService->getDependencyStatistics();

            return response()->json([
                'success' => true,
                'message' => 'Dependency statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dependency statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate dependency (alias for validateDependency)
     */
    public function validate(Request $request): JsonResponse
    {
        return $this->validateDependency($request);
    }

    /**
     * Bulk create dependencies
     */
    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'dependencies' => 'required|array|min:1',
                'dependencies.*.predecessor_task_id' => 'required|integer|exists:tasks,id',
                'dependencies.*.successor_task_id' => 'required|integer|exists:tasks,id',
                'dependencies.*.dependency_type' => 'required|string|in:finish_to_start,start_to_start,finish_to_finish,start_to_finish',
                'dependencies.*.lag_days' => 'integer|min:0|max:365'
            ]);

            $createdDependencies = [];
            foreach ($data['dependencies'] as $dependencyData) {
                $dependencyData['created_by'] = Auth::id();
                $dependencyData['created_by_type'] = Auth::user()->user_type ?? 'admin';

                $dependency = $this->taskDependencyService->createDependency($dependencyData);
                $createdDependencies[] = $dependency;
            }

            return response()->json([
                'success' => true,
                'message' => 'Dependencies created successfully',
                'data' => $createdDependencies
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create dependencies',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Bulk delete dependencies
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'dependency_ids' => 'required|array|min:1',
                'dependency_ids.*' => 'integer|exists:task_dependencies,id'
            ]);

            $deletedCount = 0;
            foreach ($data['dependency_ids'] as $dependencyId) {
                if ($this->taskDependencyService->deleteDependency($dependencyId)) {
                    $deletedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} dependencies",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'total_requested' => count($data['dependency_ids'])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete dependencies',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}

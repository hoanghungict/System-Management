<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\ReportService;
use Modules\Task\app\Services\FileService;
use Modules\Task\app\Http\Requests\TaskRequest;
use Modules\Task\app\Admin\UseCases\GetTaskDetailUseCase;
use Modules\Task\app\Models\TaskFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        private readonly ReportService $reportService,
        private readonly GetTaskDetailUseCase $getTaskDetailUseCase,
        private readonly FileService $fileService
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
            // Admin thực chất là lecturer với is_admin: true
            $data['creator_type'] = $userData->user_type ?? 'lecturer';

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
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            // ✅ Use GetTaskDetailUseCase instead of direct TaskService call
            $result = $this->getTaskDetailUseCase->execute($id, $userId, $userType);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required' ? 403 : ($e->getMessage() === 'Task not found' ? 404 : 500));
        }
    }

    /**
     * Update any task (Admin can modify any task)
     */
    public function update(TaskRequest $request, int $id): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            $userType = $request->attributes->get('jwt_user_type');
            
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

            $data = $request->validated();
            
            // Create user context object với đúng format
            $userContext = (object) [
                'id' => $userId,
                'user_type' => $userType ?? 'admin',
                'role' => 'admin', // For permission check
            ];
            
            $updatedTask = $this->taskService->updateTask($task, $data, $userContext);

            return response()->json([
                'success' => true,
                'data' => $updatedTask,
                'message' => 'Task updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin update task error', [
                'task_id' => $id,
                'admin_id' => $request->attributes->get('jwt_user_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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

    /**
     * Upload files to task
     */
    public function uploadFiles(Request $request, int $id): JsonResponse
    {
        try {
            // Find task manually to avoid route model binding issues
            $task = \Modules\Task\app\Models\Task::with('receivers')->find($id);

            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = (object) [
                'id' => $userId,
                'user_type' => $userType
            ];

            // Admin can always upload files
            // Check permission
            if (!$this->fileService->canUserUploadFiles($task, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền upload files cho task này'
                ], 403);
            }

            // Get files from request
            $uploadedFiles = $request->file('files');

            // Handle both single file and multiple files
            $files = [];
            if ($uploadedFiles) {
                if (is_array($uploadedFiles)) {
                    $files = $uploadedFiles;
                } else {
                    $files = [$uploadedFiles]; // Convert single file to array
                }
            }

            if (empty($files)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có files nào được upload'
                ], 400);
            }

            $result = $this->fileService->uploadFilesToTask($task, $files, $user);

            return response()->json([
                'files' => $result['files']
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete file from task
     */
    public function deleteFile(Request $request, int $id, int $file): JsonResponse
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

            $user = (object) [
                'id' => $userId,
                'user_type' => $userType
            ];

            $result = $this->fileService->deleteFile($file, $user);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete file'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download file from task with original filename
     */
    public function downloadFile(Request $request, int $id, int $file): StreamedResponse|JsonResponse
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

            // Find file record
            $taskFile = TaskFile::where('task_id', $id)->find($file);

            if (!$taskFile) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found for this task'
                ], 404);
            }

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($taskFile->path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found in storage'
                ], 404);
            }

            // Check permission (reuse delete permission logic)
            $user = (object) [
                'id' => $userId,
                'user_type' => $userType
            ];

            // Check permission via FileService
            if (!$this->fileService->canUserDownloadFile($taskFile, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to download this file'
                ], 403);
            }

            // Download file with original filename using Content-Disposition header
            // Lấy tên file gốc từ column 'name' trong database
            $originalFileName = $taskFile->name ?: basename($taskFile->path);
            
            return Storage::disk('public')->download($taskFile->path, $originalFileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file: ' . $e->getMessage()
            ], 500);
        }
    }
}

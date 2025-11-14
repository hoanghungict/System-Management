<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\ReportService;
use Modules\Task\app\Services\FileService;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Http\Requests\TaskRequest;
use Modules\Task\app\Transformers\TaskResource;
use Modules\Task\app\Models\TaskFile;
use Modules\Task\app\Lecturer\UseCases\GetTaskSubmissionsUseCase;
use Modules\Task\app\Lecturer\UseCases\GradeTaskSubmissionUseCase;
use Modules\Task\app\Lecturer\UseCases\GetLecturerTasksUseCase;
use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\UseCases\SubmitTaskUseCase;
use Modules\Task\app\Http\Controllers\Task\TaskSubmitController;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        private readonly ReportService $reportService,
        private readonly FileService $fileService,
        private readonly PermissionService $permissionService,
        private readonly LecturerTaskRepository $lecturerTaskRepository,
        private readonly GetTaskSubmissionsUseCase $getTaskSubmissionsUseCase,
        private readonly GradeTaskSubmissionUseCase $gradeTaskSubmissionUseCase,
        private readonly GetLecturerTasksUseCase $getLecturerTasksUseCase,
        private readonly SubmitTaskUseCase $submitTaskUseCase
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
     * Get tasks created by the lecturer
     */
    public function getCreatedTasks(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->all();
            $tasks = $this->getLecturerTasksUseCase->getCreatedTasks($userId, $filters);

            return response()->json([
                'success' => true,
                'message' => 'Created tasks retrieved successfully',
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ]);
        } catch (\Modules\Task\app\Lecturer\Exceptions\LecturerTaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?? 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve created tasks: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tasks assigned to the lecturer
     */
    public function getAssignedTasks(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->all();
            $tasks = $this->getLecturerTasksUseCase->getAssignedTasks($userId, $filters);

            return response()->json([
                'success' => true,
                'message' => 'Assigned tasks retrieved successfully',
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ]);
        } catch (\Modules\Task\app\Lecturer\Exceptions\LecturerTaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?? 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assigned tasks: ' . $e->getMessage(),
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

            // ✅ Load relationships (files và receivers) để đảm bảo có trong response
            if (!$task->relationLoaded('receivers')) {
                $task->load('receivers');
            }
            if (!$task->relationLoaded('files')) {
                $task->load('files');
            }

            return response()->json([
                'success' => true,
                'data' => new TaskResource($task),
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
            
            // ✅ Remove fields that lecturer cannot modify
            // Lecturer không thể thay đổi creator_id và creator_type
            unset($data['creator_id']);
            unset($data['creator_type']);
            
            // Create user context object với đúng format
            $userContext = (object) [
                'id' => $userId,
                'user_type' => $userType ?? 'lecturer',
            ];
            
            // ✅ Clear permission cache trước khi check để đảm bảo fresh check
            $this->permissionService->clearPermissionCache($userContext, $id);
            
            // ✅ Log task và user info để debug
            \Log::info('Lecturer update task - Permission check', [
                'task_id' => $id,
                'lecturer_id' => $userId,
                'lecturer_type' => $userType,
                'task_creator_id' => $task->creator_id,
                'task_creator_type' => $task->creator_type,
                'is_creator' => $task->creator_id == $userId && $task->creator_type == ($userType ?? 'lecturer'),
            ]);
            
            // ✅ Use PermissionService để check permission
            // PermissionService sẽ check:
            // 1. Lecturer là creator của task
            // 2. HOẶC Lecturer là receiver của task
            // → Cho phép lecturer update task họ tạo HOẶC task họ được assign
            if (!$this->permissionService->canEditTask($userContext, $id)) {
                \Log::warning('Lecturer update task - Permission denied', [
                    'task_id' => $id,
                    'lecturer_id' => $userId,
                    'task_creator_id' => $task->creator_id,
                    'task_creator_type' => $task->creator_type,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You can only update tasks you created or tasks assigned to you.'
                ], 403);
            }
            
            \Log::info('Lecturer update task - Permission allowed', [
                'task_id' => $id,
                'lecturer_id' => $userId,
            ]);
            
            $updatedTask = $this->taskService->updateTask($task, $data, $userContext);

            return response()->json([
                'success' => true,
                'data' => $updatedTask,
                'message' => 'Task updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Lecturer update task error', [
                'task_id' => $id,
                'lecturer_id' => $request->attributes->get('jwt_user_id'),
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
     * Get lecturer statistics (total, pending, completed, etc.)
     */
    public function getLecturerStatistics(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $statistics = $this->getLecturerTasksUseCase->getLecturerStatistics($userId);

            return response()->json([
                'success' => true,
                'message' => 'Lecturer statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Modules\Task\app\Lecturer\Exceptions\LecturerTaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?? 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lecturer statistics: ' . $e->getMessage(),
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

    /**
     * Upload single file to task (tương tự student endpoint)
     */
    public function uploadFile(Request $request, int $task): JsonResponse
    {
        try {
            // Find task manually to avoid route model binding issues
            $taskModel = \Modules\Task\app\Models\Task::with('receivers')->find($task);

            if (!$taskModel) {
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

            // Check permission
            if (!$this->fileService->canUserUploadFiles($taskModel, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền upload files cho task này'
                ], 403);
            }

            // Get single file from request
            $file = $request->file('file');

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có file nào được upload'
                ], 400);
            }

            // Upload single file
            $result = $this->fileService->uploadFilesToTask($taskModel, [$file], $user);

            if (empty($result['files'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload file'
                ], 500);
            }

            $uploadedFile = $result['files'][0];
            
            // Lấy TaskFile model để có đầy đủ thông tin
            $taskFile = \Modules\Task\app\Models\TaskFile::find($uploadedFile['id']);

            // Format response giống student
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'id' => $uploadedFile['id'],
                    'task_id' => $task,
                    'lecturer_id' => $userId,
                    'filename' => $uploadedFile['file_name'],
                    'path' => $taskFile->path ?? '',
                    'size' => $taskFile->size ?? 0,
                    'file_url' => $uploadedFile['file_url'],
                    'uploaded_at' => $uploadedFile['created_at']
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload files to task (multiple files)
     */
    public function uploadFiles(Request $request, int $task): JsonResponse
    {
        try {
            // Find task manually to avoid route model binding issues
            $taskModel = \Modules\Task\app\Models\Task::with('receivers')->find($task);

            if (!$taskModel) {
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

            // Check permission
            if (!$this->fileService->canUserUploadFiles($taskModel, $user)) {
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

            $result = $this->fileService->uploadFilesToTask($taskModel, $files, $user);

            // Chuẩn hóa response format giống student
            return response()->json([
                'success' => true,
                'message' => 'File(s) uploaded successfully',
                'data' => count($result['files']) === 1 
                    ? $result['files'][0]  // Single file: trả về object với id
                    : $result['files'],     // Multiple files: trả về array
                'count' => $result['count']
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
    public function deleteFile(Request $request, int $task, int $file): JsonResponse
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
    public function downloadFile(Request $request, int $task, int $file): StreamedResponse|JsonResponse
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
            $taskFile = TaskFile::where('task_id', $task)->find($file);

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

            // Check permission
            $user = (object) [
                'id' => $userId,
                'user_type' => $userType
            ];

            if (!$this->fileService->canUserDownloadFile($taskFile, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to download this file'
                ], 403);
            }

            // Download file with original filename using Content-Disposition header
            $originalFileName = $taskFile->name ?: basename($taskFile->path);
            
            return Storage::disk('public')->download($taskFile->path, $originalFileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all submissions for a task (only for lecturer who created the task)
     */
    public function getTaskSubmissions(Request $request, int $task): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get pagination params from request
            $pagination = [
                'page' => $request->get('page', 1),
                'per_page' => $request->get('per_page', 15),
            ];

            $result = $this->getTaskSubmissionsUseCase->execute($task, (int)$lecturerId, $pagination);

            return response()->json([
                'success' => true,
                'message' => 'Task submissions retrieved successfully',
                'data' => $result['data'] ?? [],
                'pagination' => $result['pagination'] ?? [],
                'count' => count($result['data'] ?? [])
            ]);
        } catch (\Modules\Task\app\Lecturer\Exceptions\LecturerTaskException $e) {
            \Log::error('Get task submissions error', [
                'task_id' => $task,
                'lecturer_id' => $request->attributes->get('jwt_user_id'),
                'error' => $e->getMessage(),
                'status_code' => $e->getStatusCode() ?? 500
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?? 500);
        } catch (\Exception $e) {
            \Log::error('Get task submissions unexpected error', [
                'task_id' => $task,
                'lecturer_id' => $request->attributes->get('jwt_user_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task submissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Grade a student submission (only for lecturer who created the task)
     */
    public function gradeSubmission(Request $request, int $task, int $submission): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $data = $request->all();
            
            // Validate required fields
            if (!isset($data['status'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status is required (graded or returned)'
                ], 422);
            }

            // Validate status
            if (!in_array($data['status'], ['graded', 'returned'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status must be either "graded" or "returned"'
                ], 422);
            }

            // Nếu status là 'graded' thì phải có grade
            if ($data['status'] === 'graded' && !isset($data['grade'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade is required when status is "graded"'
                ], 422);
            }

            $result = $this->gradeTaskSubmissionUseCase->execute($task, $submission, $data, (int)$lecturerId);

            return response()->json([
                'success' => true,
                'message' => $data['status'] === 'graded' 
                    ? 'Submission graded successfully' 
                    : 'Submission marked as returned',
                'data' => $result
            ]);
        } catch (\Modules\Task\app\Lecturer\Exceptions\LecturerTaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?? 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to grade submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit task được giao từ admin (lecturer nộp task)
     */
    public function submitTask(Request $request, int $task): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type', 'lecturer');
            
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Kiểm tra lecturer có được assign task này không
            $taskModel = \Modules\Task\app\Models\Task::with('receivers')->find($task);
            if (!$taskModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Kiểm tra lecturer có trong receivers không
            $isAssigned = $taskModel->receivers->contains(function($receiver) use ($lecturerId) {
                return $receiver->receiver_id == $lecturerId && $receiver->receiver_type == 'lecturer';
            });

            if (!$isAssigned && $taskModel->creator_id != $lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền nộp task này'
                ], 403);
            }

            // Prepare data for submission
            $data = $request->all();
            $data['task_id'] = $task;
            $data['user_id'] = $lecturerId;
            $data['user_type'] = $userType;
            $data['submission_type'] = $data['submission_type'] ?? 'task_completion';

            // Map field names để hỗ trợ cả format từ frontend và backend
            $mappedData = [
                'submission_content' => $data['submission_content'] ?? $data['content'] ?? null,
                'submission_files' => $data['submission_files'] ?? $data['files'] ?? [],
                'submission_notes' => $data['submission_notes'] ?? $data['notes'] ?? null,
            ];

            // Create user context
            $jwtPayload = $request->attributes->get('jwt_payload');
            $userContext = (object) [
                'id' => $lecturerId,
                'type' => $userType,
                'name' => ($jwtPayload && isset($jwtPayload->name)) ? $jwtPayload->name : 'Lecturer ' . $lecturerId,
                'email' => ($jwtPayload && isset($jwtPayload->email)) ? $jwtPayload->email : 'lecturer' . $lecturerId . '@example.com'
            ];

            // Submit task
            $result = $this->submitTaskUseCase->execute(array_merge($data, $mappedData), $userContext);

            return response()->json([
                'success' => true,
                'message' => 'Task submitted successfully',
                'data' => $result
            ]);
        } catch (\Modules\Task\app\Exceptions\TaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit task: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy submission của lecturer cho task được giao từ admin
     */
    public function getSubmission(Request $request, int $task): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Kiểm tra lecturer có được assign task này không
            $taskModel = \Modules\Task\app\Models\Task::with('receivers')->find($task);
            if (!$taskModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Kiểm tra lecturer có trong receivers không
            $isAssigned = $taskModel->receivers->contains(function($receiver) use ($lecturerId) {
                return $receiver->receiver_id == $lecturerId && $receiver->receiver_type == 'lecturer';
            });

            if (!$isAssigned && $taskModel->creator_id != $lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem submission của task này'
                ], 403);
            }

            // Lấy submission - tạm thời dùng student_id để query (vì TaskSubmission chỉ có student_id)
            // Cần cải thiện sau để hỗ trợ lecturer_id riêng
            $submission = \Modules\Task\app\Models\TaskSubmission::where('task_id', $task)
                ->where('student_id', $lecturerId) // Tạm thời dùng student_id
                ->first();

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa có bài nộp cho task này',
                    'data' => null
                ], 404);
            }

            // Load files nếu có
            $files = [];
            $fileIds = $submission->submission_files ?? [];
            
            if (!empty($fileIds) && is_array($fileIds)) {
                $fileIds = array_map('intval', $fileIds);
                $fileIds = array_filter($fileIds, function($id) { return $id > 0; });
                
                if (!empty($fileIds)) {
                    $taskFiles = \Modules\Task\app\Models\TaskFile::whereIn('id', $fileIds)
                        ->where('task_id', $task)
                        ->get();

                    $files = $taskFiles->map(function($file) {
                        return [
                            'id' => $file->id,
                            'file_name' => $file->name ?? basename($file->path ?? ''),
                            'file_url' => $file->file_url ?? '',
                            'file_size' => $file->size ?? 0,
                            'created_at' => $file->created_at ? $file->created_at->toDateTimeString() : null,
                        ];
                    })->toArray();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Task submission retrieved successfully',
                'data' => [
                    'id' => $submission->id,
                    'task_id' => $submission->task_id,
                    'submission_content' => $submission->submission_content,
                    'submission_files' => $submission->submission_files ?? [],
                    'submission_notes' => $submission->submission_notes ?? null,
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->toDateTimeString() : null,
                    'status' => $submission->status ?? 'pending',
                    'grade' => $submission->grade,
                    'feedback' => $submission->feedback,
                    'graded_at' => $submission->graded_at ? $submission->graded_at->toDateTimeString() : null,
                    'files' => $files,
                    'created_at' => $submission->created_at ? $submission->created_at->toDateTimeString() : null,
                    'updated_at' => $submission->updated_at ? $submission->updated_at->toDateTimeString() : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task submission: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật submission của lecturer cho task được giao từ admin
     */
    public function updateSubmission(Request $request, int $task): JsonResponse
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Kiểm tra lecturer có được assign task này không
            $taskModel = \Modules\Task\app\Models\Task::with('receivers')->find($task);
            if (!$taskModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Kiểm tra lecturer có trong receivers không
            $isAssigned = $taskModel->receivers->contains(function($receiver) use ($lecturerId) {
                return $receiver->receiver_id == $lecturerId && $receiver->receiver_type == 'lecturer';
            });

            if (!$isAssigned && $taskModel->creator_id != $lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật submission của task này'
                ], 403);
            }

            // Map field names
            $data = $request->all();
            $mappedData = [
                'submission_content' => $data['submission_content'] ?? $data['content'] ?? null,
                'submission_files' => $data['submission_files'] ?? $data['files'] ?? [],
                'submission_notes' => $data['submission_notes'] ?? $data['notes'] ?? null,
            ];

            // Lấy submission hiện tại
            $submission = \Modules\Task\app\Models\TaskSubmission::where('task_id', $task)
                ->where('student_id', $lecturerId) // Tạm thời dùng student_id
                ->first();

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submission not found'
                ], 404);
            }

            // Update submission
            $submission->submission_content = $mappedData['submission_content'] ?? $submission->submission_content;
            $submission->submission_files = $mappedData['submission_files'] ?? $submission->submission_files;
            $submission->submission_notes = $mappedData['submission_notes'] ?? $submission->submission_notes;
            $submission->submitted_at = now();
            $submission->save();

            return response()->json([
                'success' => true,
                'message' => 'Task submission updated successfully',
                'data' => $submission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task submission: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

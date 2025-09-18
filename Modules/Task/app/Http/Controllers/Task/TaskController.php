<?php

namespace Modules\Task\app\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\UserContextService;
use Modules\Task\app\Http\Requests\TaskRequest;
use Modules\Task\app\Transformers\TaskResource;
use Modules\Task\app\DTOs\TaskDTO;
use Modules\Task\app\UseCases\CreateTaskUseCase;
use Modules\Task\app\UseCases\CreateTaskWithPermissionsUseCase;
use Modules\Task\app\UseCases\GetFacultiesUseCase;
use Modules\Task\app\UseCases\GetClassesByDepartmentUseCase;
use Modules\Task\app\UseCases\GetStudentsByClassUseCase;
use Modules\Task\app\UseCases\GetLecturersUseCase;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Jobs\ProcessTaskJob;
use Modules\Task\app\Jobs\ProcessTaskFileJob;
use Modules\Task\app\Jobs\GenerateTaskReportJob;
use Modules\Task\app\Jobs\SyncTaskDataJob;
use Modules\Task\app\Services\FileService;
use Modules\Task\app\Services\ReportService;
use Illuminate\Support\Facades\Log;

/**
 * TaskController - Controller quản lý các thao tác với Task
 * 
 * Tuân thủ Clean Architecture:
 * - Chỉ xử lý HTTP requests/responses
 * - Không chứa business logic
 * - Sử dụng dependency injection
 * - Delegate business logic cho Services và Use Cases
 */
class TaskController extends Controller
{
    protected TaskServiceInterface $taskService;
    protected CreateTaskUseCase $createTaskUseCase;
    protected CreateTaskWithPermissionsUseCase $createTaskWithPermissionsUseCase;
    protected UserContextService $userContextService;
    protected GetFacultiesUseCase $getFacultiesUseCase;
    protected GetClassesByDepartmentUseCase $getClassesByDepartmentUseCase;
    protected GetStudentsByClassUseCase $getStudentsByClassUseCase;
    protected GetLecturersUseCase $getLecturersUseCase;
    protected FileService $fileService;

    /**
     * Constructor với dependency injection
     */
    public function __construct(
        TaskServiceInterface $taskService,
        CreateTaskUseCase $createTaskUseCase,
        CreateTaskWithPermissionsUseCase $createTaskWithPermissionsUseCase,
        UserContextService $userContextService,
        GetFacultiesUseCase $getFacultiesUseCase,
        GetClassesByDepartmentUseCase $getClassesByDepartmentUseCase,
        GetStudentsByClassUseCase $getStudentsByClassUseCase,
        GetLecturersUseCase $getLecturersUseCase,
        FileService $fileService
    ) {
        $this->taskService = $taskService;
        $this->createTaskUseCase = $createTaskUseCase;
        $this->createTaskWithPermissionsUseCase = $createTaskWithPermissionsUseCase;
        $this->userContextService = $userContextService;
        $this->getFacultiesUseCase = $getFacultiesUseCase;
        $this->getClassesByDepartmentUseCase = $getClassesByDepartmentUseCase;
        $this->getStudentsByClassUseCase = $getStudentsByClassUseCase;
        $this->getLecturersUseCase = $getLecturersUseCase;
        $this->fileService = $fileService;
    }

    /**
     * Hiển thị danh sách tất cả tasks với phân trang và bộ lọc
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['receiver_id', 'receiver_type', 'creator_id', 'creator_type', 'search']);
        $perPage = $request->get('per_page', 15);
        
        $tasks = $this->taskService->getTasksWithFilters($filters, $perPage);
        
        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks->items()),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage()
            ],
            'message' => 'Tasks retrieved successfully'
        ]);
    }


    /**
     * Store a newly created task
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Add creator information from JWT to data
            $data['creator_id'] = $request->attributes->get('jwt_user_id');
            $data['creator_type'] = $request->attributes->get('jwt_user_type');
            
            $task = $this->createTaskUseCase->execute($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            Log::error('TaskController: Error creating task', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hiển thị thông tin chi tiết của một task
     */
    public function show($taskId): JsonResponse
    {
        $taskData = $this->taskService->getTaskById((int) $taskId);
        
        if (!$taskData) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => new TaskResource($taskData),
            'message' => 'Task retrieved successfully'
        ]);
    }


    /**
     * Xóa một task
     */
    public function destroy(Request $request, $taskId): JsonResponse
    {
        try {
            $task = Task::find($taskId);
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }
            
            $taskId = $task->id;
            $taskTitle = $task->title;
            
            // Soft delete trực tiếp
            $result = $task->delete();
            
            // Kiểm tra xem task có thực sự bị soft delete không
            $deletedTask = Task::withTrashed()->find($taskId);
            $isSoftDeleted = $deletedTask && $deletedTask->trashed();
            
            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully',
                'task_id' => $taskId,
                'title' => $taskTitle,
                'soft_delete_result' => $result,
                'is_soft_deleted' => $isSoftDeleted,
                'deleted_at' => $isSoftDeleted ? $deletedTask->deleted_at : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách tasks của người dùng hiện tại
     */
    public function getMyTasks(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');
        $userType = $request->attributes->get('jwt_user_type');
        
        if (!$userId || !$userType) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        $perPage = $request->get('per_page', 15);
        
        $user = (object) [
            'id' => $userId,
            'user_type' => $userType
        ];
        
        $tasks = $this->taskService->getTasksForCurrentUser($user, $perPage);
        
        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks->items()),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage()
            ],
            'message' => 'My tasks retrieved successfully'
        ]);
    }

    /**
     * Lấy danh sách tasks đã giao (chỉ giảng viên)
     */
    public function getMyAssignedTasks(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');
        $userType = $request->attributes->get('jwt_user_type');
        
        if (!$userId || !$userType) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        if ($userType !== 'lecturer') {
            return response()->json([
                'success' => false,
                'message' => 'Only lecturers can view assigned tasks'
            ], 403);
        }
        
        $perPage = $request->get('per_page', 15);
        
        $user = (object) [
            'id' => $userId,
            'user_type' => $userType
        ];
        
        $tasks = $this->taskService->getTasksCreatedByUser($user, $perPage);
        
        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks->items()),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage()
            ],
            'message' => 'My assigned tasks retrieved successfully'
        ]);
    }


    /**
     * Cập nhật trạng thái task (chỉ người nhận task)
     */
    public function updateStatus(Request $request, $taskId): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');
        $userType = $request->attributes->get('jwt_user_type');
        
        if (!$userId || !$userType) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        // Find task manually
        $task = \Modules\Task\app\Models\Task::with('receivers')->find($taskId);
        
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }
        
        $status = $request->get('status');
        
        $user = (object) [
            'id' => $userId,
            'user_type' => $userType
        ];
        
        $canUpdate = $this->taskService->canUpdateTaskStatus($user, $task);
        
        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật trạng thái task này'
            ], 403);
        }
        
        $updatedTask = $this->taskService->updateTaskStatus($task, $status);
        
        ProcessTaskJob::dispatch($task->toArray(), 'status_updated', ['old_status' => $task->status, 'new_status' => $status])
            ->onQueue('high')
            ->delay(now()->addSeconds(15));
        
        return response()->json([
            'success' => true,
            'data' => new TaskResource($updatedTask),
            'message' => 'Task status updated successfully and is being processed'
        ]);
    }

    /**
     * Lấy thống kê tasks của người dùng hiện tại
     */
    public function getMyStatistics(Request $request): JsonResponse
    {
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
        
        $statistics = $this->taskService->getUserTaskStatistics($user);
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'My task statistics retrieved successfully'
        ]);
    }

    /**
     * Lấy thống kê tasks đã tạo (chỉ giảng viên)
     */
    public function getCreatedStatistics(Request $request): JsonResponse
    {
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
        
        $statistics = $this->taskService->getCreatedTaskStatistics($user);
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'Created task statistics retrieved successfully'
        ]);
    }

    /**
     * Lấy thống kê tổng quan (chỉ admin)
     */
    public function getOverviewStatistics(): JsonResponse
    {
        $statistics = $this->taskService->getOverviewTaskStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'Overview task statistics retrieved successfully'
        ]);
    }

    /**
     * Lấy tất cả tasks (chỉ admin)
     */
    public function getAllTasks(Request $request): JsonResponse
    {
        $filters = $request->only(['receiver_id', 'receiver_type', 'creator_id', 'creator_type', 'search', 'status']);
        $perPage = $request->get('per_page', 15);
        
        $tasks = $this->taskService->getAllTasks($filters, $perPage);
        
        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks->items()),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage()
            ],
            'message' => 'All tasks retrieved successfully'
        ]);
    }

    /**
     * Lấy danh sách departments
     */
    public function getFaculties(Request $request): JsonResponse
    {
        try {
            $user = $this->userContextService->createUserFromJwt($request);
            $faculties = $this->getFacultiesUseCase->execute($user);
            
            return response()->json([
                'success' => true,
                'data' => $faculties,
                'message' => 'Faculties retrieved successfully'
            ]);
        } catch (TaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext()
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving faculties'
            ], 500);
        }
    }

    /**
     * Lấy danh sách departments (alias for getFaculties)
     */
    public function getDepartments(Request $request): JsonResponse
    {
        return $this->getFaculties($request);
    }

    /**
     * Lấy danh sách classes theo department
     */
    public function getClassesByDepartment(Request $request): JsonResponse
    {
        try {
            $departmentId = $request->get('department_id');
            if (!$departmentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department ID is required'
                ], 400);
            }
            
            $user = $this->userContextService->createUserFromJwt($request);
            $classes = $this->getClassesByDepartmentUseCase->execute($user, $departmentId);
            
            return response()->json([
                'success' => true,
                'data' => $classes,
                'message' => 'Classes retrieved successfully'
            ]);
        } catch (TaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext()
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving classes'
            ], 500);
        }
    }

    /**
     * Lấy danh sách students theo class
     */
    public function getStudentsByClass(Request $request): JsonResponse
    {
        try {
            $classId = $request->get('class_id');
            if (!$classId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class ID is required'
                ], 400);
            }
            
            $user = $this->userContextService->createUserFromJwt($request);
            $students = $this->getStudentsByClassUseCase->execute($user, $classId);
            
            return response()->json([
                'success' => true,
                'data' => $students,
                'message' => 'Students retrieved successfully'
            ]);
        } catch (TaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext()
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving students'
            ], 500);
        }
    }

    /**
     * Lấy danh sách lecturers
     */
    public function getLecturers(Request $request): JsonResponse
    {
        try {
            $user = $this->userContextService->createUserFromJwt($request);
            $lecturers = $this->getLecturersUseCase->execute($user);
            
            return response()->json([
                'success' => true,
                'data' => $lecturers,
                'message' => 'Lecturers retrieved successfully'
            ]);
        } catch (TaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext()
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving lecturers'
            ], 500);
        }
    }

    
    
    /**
     * Đồng bộ dữ liệu task với queue processing
     */
    public function syncData(Request $request): JsonResponse
    {
        try {
            $syncType = $request->input('type', 'database');
            $syncParams = $request->input('params', []);
            
            SyncTaskDataJob::dispatch($syncType, $syncParams)
                ->onQueue('sync')
                ->delay(now()->addMinutes(1));
            
            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ dữ liệu đang được thực hiện'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Upload files to task
     */
    public function uploadFiles(Request $request, $taskId): JsonResponse
    {
        try {
            // Find task manually to avoid route model binding issues
            $task = \Modules\Task\app\Models\Task::find($taskId);
            
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
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => [
                    'task_id' => $task->id,
                    'files' => $result['files'],
                    'count' => $result['count']
                ]
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
    public function deleteFile(Request $request, Task $task, int $fileId): JsonResponse
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
            
            $result = $this->fileService->deleteFile($fileId, $user);
            
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
}

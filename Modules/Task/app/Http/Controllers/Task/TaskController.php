<?php

namespace Modules\Task\app\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\UserContextService;
use Modules\Task\app\Transformers\TaskResource;
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
use Modules\Task\app\Services\FileService;
use Modules\Task\app\Services\ReportService;
use Modules\Task\app\Models\TaskFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        UserContextService $userContextService,
        GetFacultiesUseCase $getFacultiesUseCase,
        GetClassesByDepartmentUseCase $getClassesByDepartmentUseCase,
        GetStudentsByClassUseCase $getStudentsByClassUseCase,
        GetLecturersUseCase $getLecturersUseCase,
        FileService $fileService
    ) {
        $this->taskService = $taskService;
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

        $response = [
            'success' => true,
            'data' => TaskResource::collection($tasks->items()),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage()
            ],
            'message' => 'Tasks retrieved successfully'
        ];

        return response()->json($response, 200, [
            'Content-Type' => 'application/json; charset=utf-8'
        ], JSON_UNESCAPED_UNICODE);
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

        // ✅ Load relationships (files và receivers) để đảm bảo có trong response
        if (!$taskData->relationLoaded('receivers')) {
            $taskData->load('receivers');
        }
        if (!$taskData->relationLoaded('files')) {
            $taskData->load('files');
        }

        return response()->json([
            'success' => true,
            'data' => new TaskResource($taskData),
            'message' => 'Task retrieved successfully'
        ]);
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

    // Lecturer methods đã được chuyển sang LecturerTaskController


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

    // Lecturer methods đã được chuyển sang LecturerTaskController

    // Admin methods đã được chuyển sang AdminTaskController

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

    // getDepartments đã được loại bỏ vì chỉ là alias của getFaculties

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



    // Admin methods đã được chuyển sang AdminTaskController



    /**
     * Upload files to task
     */
    public function uploadFiles(Request $request, $taskId): JsonResponse
    {
        try {
            // Find task manually to avoid route model binding issues
            $task = \Modules\Task\app\Models\Task::with('receivers')->find($taskId);

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

    /**
     * Download file from task with original filename
     */
    public function downloadFile(Request $request, Task $task, int $fileId): StreamedResponse|JsonResponse
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
            $taskFile = TaskFile::where('task_id', $task->id)->find($fileId);

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

    // Admin methods đã được chuyển sang AdminTaskController
}

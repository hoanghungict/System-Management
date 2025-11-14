<?php

namespace Modules\Task\app\Lecturer\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Task\app\Lecturer\UseCases\LecturerTaskUseCase;
use Modules\Task\app\Lecturer\UseCases\GetLecturerTasksUseCase;
use Modules\Task\app\UseCases\CreateTaskUseCase;
use Modules\Task\app\Lecturer\UseCases\UpdateTaskUseCase;
use Modules\Task\app\Lecturer\UseCases\AssignTaskUseCase;
use Modules\Task\app\Lecturer\UseCases\RevokeTaskUseCase;
use Modules\Task\app\Lecturer\UseCases\CreateRecurringTaskUseCase;
use Modules\Task\app\UseCases\CreateTaskWithPermissionsUseCase;
use Modules\Task\app\Lecturer\UseCases\ProcessTaskFilesUseCase;
use Modules\Task\app\Lecturer\UseCases\GradeTaskSubmissionUseCase;
use Modules\Task\app\Lecturer\UseCases\GetTaskSubmissionsUseCase;
use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;

/**
 * Lecturer Task Controller
 * 
 * Controller dành riêng cho Giảng viên để quản lý tasks
 * Tuân theo Clean Architecture với Use Cases
 */
class LecturerTaskController extends Controller
{
    protected $lecturerTaskUseCase;
    protected $lecturerTaskRepository;
    protected $getLecturerTasksUseCase;
    protected $createTaskUseCase;
    protected $updateTaskUseCase;
    protected $assignTaskUseCase;
    protected $revokeTaskUseCase;
    protected $createRecurringTaskUseCase;
    protected $createTaskWithPermissionsUseCase;
    protected $processTaskFilesUseCase;
    protected $gradeTaskSubmissionUseCase;
    protected $getTaskSubmissionsUseCase;

    public function __construct(
        LecturerTaskUseCase $lecturerTaskUseCase,
        GetLecturerTasksUseCase $getLecturerTasksUseCase,
        CreateTaskUseCase $createTaskUseCase,
        UpdateTaskUseCase $updateTaskUseCase,
        AssignTaskUseCase $assignTaskUseCase,
        RevokeTaskUseCase $revokeTaskUseCase,
        CreateRecurringTaskUseCase $createRecurringTaskUseCase,
        CreateTaskWithPermissionsUseCase $createTaskWithPermissionsUseCase,
        ProcessTaskFilesUseCase $processTaskFilesUseCase,
        GradeTaskSubmissionUseCase $gradeTaskSubmissionUseCase,
        GetTaskSubmissionsUseCase $getTaskSubmissionsUseCase,
        LecturerTaskRepository $lecturerTaskRepository
    ) {
        $this->lecturerTaskUseCase = $lecturerTaskUseCase;
        $this->getLecturerTasksUseCase = $getLecturerTasksUseCase;
        $this->createTaskUseCase = $createTaskUseCase;
        $this->updateTaskUseCase = $updateTaskUseCase;
        $this->assignTaskUseCase = $assignTaskUseCase;
        $this->revokeTaskUseCase = $revokeTaskUseCase;
        $this->createRecurringTaskUseCase = $createRecurringTaskUseCase;
        $this->createTaskWithPermissionsUseCase = $createTaskWithPermissionsUseCase;
        $this->processTaskFilesUseCase = $processTaskFilesUseCase;
        $this->gradeTaskSubmissionUseCase = $gradeTaskSubmissionUseCase;
        $this->getTaskSubmissionsUseCase = $getTaskSubmissionsUseCase;
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    /**
     * Lấy danh sách tasks của giảng viên
     */
    public function index(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $tasks = $this->getLecturerTasksUseCase->execute($lecturerId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Lecturer tasks retrieved successfully',
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lecturer tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tasks đã tạo bởi giảng viên
     */
    public function getCreatedTasks(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $tasks = $this->getLecturerTasksUseCase->getCreatedTasks($lecturerId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Created tasks retrieved successfully',
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve created tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tasks được giao cho giảng viên
     */
    public function getAssignedTasks(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $tasks = $this->getLecturerTasksUseCase->getAssignedTasks($lecturerId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Assigned tasks retrieved successfully',
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assigned tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê tasks của giảng viên
     */
    public function getLecturerStatistics(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $statistics = $this->getLecturerTasksUseCase->getLecturerStatistics($lecturerId);

            return response()->json([
                'success' => true,
                'message' => 'Lecturer statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lecturer statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo task mới
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            
            // Debug JWT attributes
            $jwtUserId = $request->attributes->get('jwt_user_id');
            \Log::info('JWT Debug', [
                'jwt_user_id' => $jwtUserId,
                'attributes' => $request->attributes->all(),
                'request_data' => $data
            ]);
            
            // Fallback nếu không có JWT user ID
            if (!$jwtUserId) {
                $jwtUserId = $data['creator_id'] ?? 1; // Sử dụng creator_id từ request hoặc default = 1
            }
            
            $data['creator_id'] = $jwtUserId;
            $data['creator_type'] = 'lecturer';

            $task = $this->createTaskUseCase->execute($data);

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Task creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật task
     */
    public function update(Request $request, $taskId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();

            $task = $this->updateTaskUseCase->execute($taskId, $data, $lecturerId, 'lecturer');

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => new \Modules\Task\app\Transformers\TaskResource($task)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xem chi tiết task
     */
    public function show(Request $request, $taskId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $task = $this->lecturerTaskUseCase->getTaskById($taskId, $lecturerId, 'lecturer');

            return response()->json([
                'success' => true,
                'message' => 'Task retrieved successfully',
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa task
     */
    public function destroy(Request $request, $taskId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $this->lecturerTaskUseCase->softDeleteTask($taskId, $lecturerId, 'lecturer');

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Giao task cho sinh viên
     */
    public function assignTask(Request $request, $taskId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();

            $result = $this->assignTaskUseCase->execute($taskId, $data, $lecturerId, 'lecturer');

            return response()->json([
                'success' => true,
                'message' => 'Task assigned successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thu hồi task
     */
    public function revokeTask(Request $request, $taskId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();

            $result = $this->revokeTaskUseCase->execute($taskId, $data, $lecturerId, 'lecturer');

            return response()->json([
                'success' => true,
                'message' => 'Task revoked successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo task định kỳ
     */
    public function createRecurringTask(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            $data['creator_id'] = $lecturerId;
            $data['creator_type'] = 'lecturer';

            $task = $this->createRecurringTaskUseCase->execute($data);

            return response()->json([
                'success' => true,
                'message' => 'Recurring task created successfully',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create recurring task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo task với quyền hạn
     */
    public function createTaskWithPermissions(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            $data['creator_id'] = $lecturerId;
            $data['creator_type'] = 'lecturer';

            // Ensure receivers is properly formatted
            if (!isset($data['receivers']) || !is_array($data['receivers'])) {
                $data['receivers'] = [];
            }

            // Add default receiver if none provided
            if (empty($data['receivers'])) {
                $data['receivers'] = [
                    [
                        'receiver_id' => 1, // Default student ID
                        'receiver_type' => 'student'
                    ]
                ];
            }

            $user = (object) [
                'id' => $lecturerId,
                'user_type' => 'lecturer'
            ];

            $task = $this->createTaskWithPermissionsUseCase->execute($user, $data);

            return response()->json([
                'success' => true,
                'message' => 'Task with permissions created successfully',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task with permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xử lý files của task
     */
    public function processTaskFiles(Request $request, $taskId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();

            $result = $this->processTaskFilesUseCase->execute($taskId, $data, $lecturerId, 'lecturer');

            return response()->json([
                'success' => true,
                'message' => 'Task files processed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process task files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách submissions của task (cho lecturer xem)
     */
    public function getTaskSubmissions(Request $request, $taskId)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            
            if (!$lecturerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $submissions = $this->getTaskSubmissionsUseCase->execute($taskId, $lecturerId);

            return response()->json([
                'success' => true,
                'message' => 'Task submissions retrieved successfully',
                'data' => $submissions
            ]);
        } catch (\Modules\Task\app\Lecturer\Exceptions\LecturerTaskException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?? 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task submissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chấm điểm bài nộp của sinh viên
     * 
     * Status: 'graded' (đạt) hoặc 'returned' (chưa đạt)
     */
    public function gradeSubmission(Request $request, $taskId, $submissionId)
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

            $result = $this->gradeTaskSubmissionUseCase->execute($taskId, $submissionId, $data, $lecturerId);

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
}

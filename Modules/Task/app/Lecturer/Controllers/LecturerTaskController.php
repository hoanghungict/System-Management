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
use Modules\Task\app\Lecturer\UseCases\GenerateReportUseCase;
use Modules\Task\app\Lecturer\UseCases\SendReportEmailUseCase;
use Modules\Task\app\Lecturer\UseCases\CreateRecurringTaskUseCase;
use Modules\Task\app\UseCases\CreateTaskWithPermissionsUseCase;
use Modules\Task\app\Lecturer\UseCases\ProcessTaskFilesUseCase;

/**
 * Lecturer Task Controller
 * 
 * Controller dành riêng cho Giảng viên để quản lý tasks
 * Tuân theo Clean Architecture với Use Cases
 */
class LecturerTaskController extends Controller
{
    protected $lecturerTaskUseCase;
    protected $getLecturerTasksUseCase;
    protected $createTaskUseCase;
    protected $updateTaskUseCase;
    protected $assignTaskUseCase;
    protected $revokeTaskUseCase;
    protected $generateReportUseCase;
    protected $sendReportEmailUseCase;
    protected $createRecurringTaskUseCase;
    protected $createTaskWithPermissionsUseCase;
    protected $processTaskFilesUseCase;

    public function __construct(
        LecturerTaskUseCase $lecturerTaskUseCase,
        GetLecturerTasksUseCase $getLecturerTasksUseCase,
        CreateTaskUseCase $createTaskUseCase,
        UpdateTaskUseCase $updateTaskUseCase,
        AssignTaskUseCase $assignTaskUseCase,
        RevokeTaskUseCase $revokeTaskUseCase,
        GenerateReportUseCase $generateReportUseCase,
        SendReportEmailUseCase $sendReportEmailUseCase,
        CreateRecurringTaskUseCase $createRecurringTaskUseCase,
        CreateTaskWithPermissionsUseCase $createTaskWithPermissionsUseCase,
        ProcessTaskFilesUseCase $processTaskFilesUseCase
    ) {
        $this->lecturerTaskUseCase = $lecturerTaskUseCase;
        $this->getLecturerTasksUseCase = $getLecturerTasksUseCase;
        $this->createTaskUseCase = $createTaskUseCase;
        $this->updateTaskUseCase = $updateTaskUseCase;
        $this->assignTaskUseCase = $assignTaskUseCase;
        $this->revokeTaskUseCase = $revokeTaskUseCase;
        $this->generateReportUseCase = $generateReportUseCase;
        $this->sendReportEmailUseCase = $sendReportEmailUseCase;
        $this->createRecurringTaskUseCase = $createRecurringTaskUseCase;
        $this->createTaskWithPermissionsUseCase = $createTaskWithPermissionsUseCase;
        $this->processTaskFilesUseCase = $processTaskFilesUseCase;
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
            $data['creator_id'] = $request->attributes->get('jwt_user_id');
            $data['creator_type'] = 'lecturer';
            
            $task = $this->createTaskUseCase->execute($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task: ' . $e->getMessage()
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
                'data' => $task
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
     * Tạo báo cáo
     */
    public function generateReport(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            
            $report = $this->generateReportUseCase->execute($lecturerId, $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Report generated successfully',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gửi email báo cáo
     */
    public function sendReportEmail(Request $request)
    {
        try {
            $lecturerId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            
            $result = $this->sendReportEmailUseCase->execute($lecturerId, $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Report email sent successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send report email: ' . $e->getMessage()
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
}
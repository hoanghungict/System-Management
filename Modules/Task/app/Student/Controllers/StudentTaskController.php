<?php

namespace Modules\Task\app\Student\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Task\app\Student\UseCases\StudentTaskUseCase;
use Modules\Task\app\Student\UseCases\GetStudentTasksUseCase;
use Modules\Task\app\Student\UseCases\SubmitTaskUseCase;
use Modules\Task\app\Student\UseCases\UpdateTaskSubmissionUseCase;
use Modules\Task\app\Student\UseCases\GetTaskSubmissionUseCase;
use Modules\Task\app\Student\UseCases\GetTaskStatisticsUseCase;
use Modules\Task\app\Student\UseCases\UploadTaskFileUseCase;
use Modules\Task\app\Student\UseCases\DeleteTaskFileUseCase;
use Modules\Task\app\Student\UseCases\GetTaskFilesUseCase;

/**
 * Student Task Controller
 * 
 * Controller dành riêng cho Sinh viên để quản lý tasks
 * Tuân theo Clean Architecture với Use Cases
 */
class StudentTaskController extends Controller
{
    protected $studentTaskUseCase;
    protected $getStudentTasksUseCase;
    protected $submitTaskUseCase;
    protected $updateTaskSubmissionUseCase;
    protected $getTaskSubmissionUseCase;
    protected $getTaskStatisticsUseCase;
    protected $uploadTaskFileUseCase;
    protected $deleteTaskFileUseCase;
    protected $getTaskFilesUseCase;

    public function __construct(
        StudentTaskUseCase $studentTaskUseCase,
        GetStudentTasksUseCase $getStudentTasksUseCase,
        SubmitTaskUseCase $submitTaskUseCase,
        UpdateTaskSubmissionUseCase $updateTaskSubmissionUseCase,
        GetTaskSubmissionUseCase $getTaskSubmissionUseCase,
        GetTaskStatisticsUseCase $getTaskStatisticsUseCase,
        UploadTaskFileUseCase $uploadTaskFileUseCase,
        DeleteTaskFileUseCase $deleteTaskFileUseCase,
        GetTaskFilesUseCase $getTaskFilesUseCase
    ) {
        $this->studentTaskUseCase = $studentTaskUseCase;
        $this->getStudentTasksUseCase = $getStudentTasksUseCase;
        $this->submitTaskUseCase = $submitTaskUseCase;
        $this->updateTaskSubmissionUseCase = $updateTaskSubmissionUseCase;
        $this->getTaskSubmissionUseCase = $getTaskSubmissionUseCase;
        $this->getTaskStatisticsUseCase = $getTaskStatisticsUseCase;
        $this->uploadTaskFileUseCase = $uploadTaskFileUseCase;
        $this->deleteTaskFileUseCase = $deleteTaskFileUseCase;
        $this->getTaskFilesUseCase = $getTaskFilesUseCase;
    }

    /**
     * Lấy danh sách tasks được giao cho sinh viên
     */
    public function index(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $tasks = $this->getStudentTasksUseCase->execute($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Student tasks retrieved successfully',
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tasks đang chờ submit
     */
    public function getPendingTasks(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $tasks = $this->getStudentTasksUseCase->getPendingTasks($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Pending tasks retrieved successfully',
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tasks đã submit
     */
    public function getSubmittedTasks(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $tasks = $this->getStudentTasksUseCase->getSubmittedTasks($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Submitted tasks retrieved successfully',
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve submitted tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tasks quá hạn
     */
    public function getOverdueTasks(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $tasks = $this->getStudentTasksUseCase->getOverdueTasks($studentId, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Overdue tasks retrieved successfully',
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve overdue tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê tasks của sinh viên
     */
    public function getStudentStatistics(Request $request)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $statistics = $this->getTaskStatisticsUseCase->execute($studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Student statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xem chi tiết task
     */
    public function show(Request $request, $taskId)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $task = $this->studentTaskUseCase->getTaskById($taskId, $studentId, 'student');
            
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
     * Submit task
     */
    public function submitTask(Request $request, $taskId)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            
            $result = $this->submitTaskUseCase->execute($taskId, $data, $studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Task submitted successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật task submission
     */
    public function updateSubmission(Request $request, $taskId)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $data = $request->all();
            
            $result = $this->updateTaskSubmissionUseCase->execute($taskId, $data, $studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Task submission updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy task submission
     */
    public function getSubmission(Request $request, $taskId)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $submission = $this->getTaskSubmissionUseCase->execute($taskId, $studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Task submission retrieved successfully',
                'data' => $submission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file cho task
     */
    public function uploadFile(Request $request, $taskId)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $file = $request->file('file');
            
            $result = $this->uploadTaskFileUseCase->execute($taskId, $file, $studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa file của task
     */
    public function deleteFile(Request $request, $taskId, $fileId)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            
            $this->deleteTaskFileUseCase->execute($taskId, $fileId, $studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách files của task
     */
    public function getFiles(Request $request, $taskId)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $files = $this->getTaskFilesUseCase->execute($taskId, $studentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Task files retrieved successfully',
                'data' => $files
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task files: ' . $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace Modules\Task\app\Student\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;
use Modules\Task\app\Models\TaskFile;
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
            
            // Map field names để hỗ trợ cả format từ frontend và backend
            // Frontend có thể gửi: content, files, notes
            // Backend yêu cầu: submission_content, submission_files, submission_notes
            $mappedData = [
                'submission_content' => $data['submission_content'] ?? $data['content'] ?? null,
                'submission_files' => $data['submission_files'] ?? $data['files'] ?? [],
                'submission_notes' => $data['submission_notes'] ?? $data['notes'] ?? null,
            ];
            
            $result = $this->submitTaskUseCase->execute($taskId, $mappedData, $studentId);
            
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
            
            // Map field names để hỗ trợ cả format từ frontend và backend
            $mappedData = [
                'submission_content' => $data['submission_content'] ?? $data['content'] ?? null,
                'submission_files' => $data['submission_files'] ?? $data['files'] ?? [],
                'submission_notes' => $data['submission_notes'] ?? $data['notes'] ?? null,
            ];
            
            $result = $this->updateTaskSubmissionUseCase->execute($taskId, $mappedData, $studentId);
            
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
     * 
     * Returns 404 nếu không có submission (không phải 500)
     * Returns 200 với files array (luôn là array, không phải null)
     */
    public function getSubmission(Request $request, $taskId)
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            
            if (!$studentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Execute use case - có thể return null
            $submission = $this->getTaskSubmissionUseCase->execute($taskId, $studentId);
            
            // ✅ Return 404 nếu không có submission (không phải 500)
            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa có bài nộp cho task này',
                    'data' => null
                ], 404);
            }

            // ✅ Return 200 với submission data (files luôn là array)
            return response()->json([
                'success' => true,
                'message' => 'Task submission retrieved successfully',
                'data' => $submission
            ]);
        } catch (\Modules\Task\app\Student\Exceptions\StudentTaskException $e) {
            // Handle StudentTaskException với status code đúng
            $statusCode = $e->getStatusCode() ?? 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
        } catch (\Exception $e) {
            // Log error đầy đủ
            \Log::error('Get submission error', [
                'task_id' => $taskId,
                'student_id' => $request->attributes->get('jwt_user_id'),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return 500 chỉ khi có lỗi hệ thống thực sự
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải bài nộp',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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

    /**
     * Download file from task with original filename
     * 
     * @param Request $request
     * @param int $taskId
     * @param int $fileId
     * @return StreamedResponse|JsonResponse
     */
    public function downloadFile(Request $request, int $taskId, int $fileId): StreamedResponse|JsonResponse
    {
        try {
            $studentId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$studentId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Find file record
            $taskFile = TaskFile::where('task_id', $taskId)->find($fileId);

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

            // Check permission: Student chỉ có thể download file của task họ được assigned
            $task = $taskFile->task;
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            // Check if student is receiver of this task
            $isReceiver = false;
            if ($task->receivers) {
                foreach ($task->receivers as $receiver) {
                    if ($receiver->receiver_id == $studentId && $receiver->receiver_type == 'student') {
                        $isReceiver = true;
                        break;
                    }
                }
            }

            if (!$isReceiver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to download this file'
                ], 403);
            }

            // Download file with original filename using Content-Disposition header
            $originalFileName = $taskFile->name ?: basename($taskFile->path);
            
            return Storage::disk('public')->download($taskFile->path, $originalFileName);

        } catch (\Exception $e) {
            \Log::error('Student download file error', [
                'task_id' => $taskId,
                'file_id' => $fileId,
                'student_id' => $request->attributes->get('jwt_user_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download file: ' . $e->getMessage()
            ], 500);
        }
    }
}

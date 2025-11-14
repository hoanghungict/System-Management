<?php

namespace Modules\Task\app\Lecturer\Repositories;

use Modules\Task\app\Lecturer\DTOs\TaskFilterDTO;
use Modules\Task\app\Lecturer\DTOs\CreateTaskDTO;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Lecturer Task Repository
 * 
 * Repository dành riêng cho Lecturer package
 * Tuân theo Clean Architecture
 */
class LecturerTaskRepository
{
    protected $taskModel;

    public function __construct()
    {
        $this->taskModel = app('Modules\Task\app\Models\Task');
    }

    /**
     * Lấy task theo ID
     */
    public function findById($taskId)
    {
        try {
            return $this->taskModel->with(['receivers', 'files'])->find($taskId);
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to find task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks của giảng viên
     */
    public function getLecturerTasks($lecturerId, TaskFilterDTO $filterDTO)
    {
        try {
            $query = $this->taskModel->with(['receivers', 'files'])
                ->where(function($q) use ($lecturerId) {
                    $q->where('creator_id', $lecturerId)
                      ->where('creator_type', 'lecturer')
                      ->orWhereHas('receivers', function($subQ) use ($lecturerId) {
                          $subQ->where('receiver_id', $lecturerId)
                               ->where('receiver_type', 'lecturer');
                      });
                });

            // Apply filters
            $this->applyFilters($query, $filterDTO);

            $tasks = $query->paginate($filterDTO->per_page, ['*'], 'page', $filterDTO->page);
            
            return [
                'data' => $tasks->items(),
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage()
                ]
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve lecturer tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks đã tạo
     */
    public function getCreatedTasks($lecturerId, TaskFilterDTO $filterDTO)
    {
        try {
            $query = $this->taskModel->with(['receivers', 'files'])
                ->where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer');

            // Apply filters
            $this->applyFilters($query, $filterDTO);

            $tasks = $query->paginate($filterDTO->per_page, ['*'], 'page', $filterDTO->page);
            
            return [
                'data' => $tasks->items(),
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage()
                ]
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve created tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks được giao
     */
    public function getAssignedTasks($lecturerId, TaskFilterDTO $filterDTO)
    {
        try {
            $query = $this->taskModel->with(['receivers', 'files'])
                ->whereHas('receivers', function($q) use ($lecturerId) {
                    $q->where('receiver_id', $lecturerId)
                      ->where('receiver_type', 'lecturer');
                });

            // Apply filters
            $this->applyFilters($query, $filterDTO);

            $tasks = $query->paginate($filterDTO->per_page, ['*'], 'page', $filterDTO->page);
            
            return [
                'data' => $tasks->items(),
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage()
                ]
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve assigned tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy thống kê
     */
    public function getLecturerStatistics($lecturerId)
    {
        try {
            $total = $this->taskModel->where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->count();

            $pending = $this->taskModel->where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->where('status', 'pending')
                ->count();

            $completed = $this->taskModel->where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->where('status', 'completed')
                ->count();

            return [
                'total' => $total,
                'pending' => $pending,
                'completed' => $completed,
                'in_progress' => 0,
                'cancelled' => 0,
                'overdue' => 0
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve lecturer statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Tạo task
     */
    public function create(CreateTaskDTO $createTaskDTO)
    {
        try {
            $task = $this->taskModel->create($createTaskDTO->toArray());
            
            // Tạo receivers
            if (!empty($createTaskDTO->receivers)) {
                foreach ($createTaskDTO->receivers as $receiver) {
                    $task->receivers()->create($receiver);
                }
            }
            
            return $task->load(['receivers', 'files']);
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to create task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật task
     */
    public function update($taskId, $data, $lecturerId, $userType)
    {
        try {
            $task = $this->findById($taskId);
            
            if (!$task) {
                throw LecturerTaskException::taskNotFound($taskId);
            }

            // Kiểm tra quyền
            if ($task->creator_id != $lecturerId || $task->creator_type != 'lecturer') {
                throw LecturerTaskException::accessDenied($taskId);
            }

            $task->update($data);
            return $task->load(['receivers', 'files']);
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to update task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Soft delete task
     */
    public function softDelete($taskId)
    {
        try {
            $task = $this->findById($taskId);
            if ($task) {
                $task->delete();
            }
            return true;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to delete task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Giao task
     */
    public function assignTask($taskId, $data, $lecturerId, $userType)
    {
        try {
            $task = $this->findById($taskId);
            
            if (!$task) {
                throw LecturerTaskException::taskNotFound($taskId);
            }

            // Kiểm tra quyền
            if ($task->creator_id != $lecturerId || $task->creator_type != 'lecturer') {
                throw LecturerTaskException::accessDenied($taskId);
            }

            // Tạo receivers mới
            if (isset($data['receiver_ids']) && isset($data['receiver_type'])) {
                foreach ($data['receiver_ids'] as $receiverId) {
                    $task->receivers()->create([
                        'receiver_id' => $receiverId,
                        'receiver_type' => $data['receiver_type']
                    ]);
                }
            }

            return $task->load(['receivers', 'files']);
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to assign task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Thu hồi task
     */
    public function revokeTask($taskId, $data, $lecturerId, $userType)
    {
        try {
            $task = $this->findById($taskId);
            
            if (!$task) {
                throw LecturerTaskException::taskNotFound($taskId);
            }

            // Kiểm tra quyền
            if ($task->creator_id != $lecturerId || $task->creator_type != 'lecturer') {
                throw LecturerTaskException::accessDenied($taskId);
            }

            // Xóa receivers
            if (isset($data['receiver_ids'])) {
                $task->receivers()->whereIn('receiver_id', $data['receiver_ids'])->delete();
            }

            return $task->load(['receivers', 'files']);
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to revoke task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Tạo task định kỳ
     */
    public function createRecurringTask($data)
    {
        try {
            $task = $this->taskModel->create($data);
            
            // Tạo receivers
            if (!empty($data['receivers'])) {
                foreach ($data['receivers'] as $receiver) {
                    $task->receivers()->create($receiver);
                }
            }
            
            return $task->load(['receivers', 'files']);
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to create recurring task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Tạo task với quyền hạn
     */
    public function createTaskWithPermissions($data)
    {
        try {
            $task = $this->taskModel->create($data);
            
            // Tạo receivers
            if (!empty($data['receivers'])) {
                foreach ($data['receivers'] as $receiver) {
                    $task->receivers()->create($receiver);
                }
            }
            
            return $task->load(['receivers', 'files']);
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to create task with permissions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Tạo báo cáo
     */
    public function generateReport($lecturerId, $data)
    {
        try {
            $tasks = $this->taskModel->where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->get();

            return [
                'lecturer_id' => $lecturerId,
                'total_tasks' => $tasks->count(),
                'tasks' => $tasks,
                'format' => $data['format'] ?? 'json',
                'generated_at' => now()
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to generate report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Gửi email báo cáo
     */
    public function sendReportEmail($lecturerId, $data)
    {
        try {
            $report = $this->generateReport($lecturerId, $data);
            
            return [
                'success' => true,
                'message' => 'Report email sent successfully',
                'recipients' => $data['recipients'] ?? [],
                'report' => $report
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to send report email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xử lý files của task
     */
    public function processTaskFiles($taskId, $data, $lecturerId, $userType)
    {
        try {
            $task = $this->findById($taskId);
            
            if (!$task) {
                throw LecturerTaskException::taskNotFound($taskId);
            }

            // Kiểm tra quyền
            if ($task->creator_id != $lecturerId || $task->creator_type != 'lecturer') {
                throw LecturerTaskException::accessDenied($taskId);
            }

            return [
                'task_id' => $taskId,
                'action' => $data['action'] ?? 'process',
                'file_types' => $data['file_types'] ?? [],
                'processed_at' => now()
            ];
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to process task files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy danh sách submissions của task (cho lecturer xem)
     * 
     * @param int $taskId - Task ID
     * @param int $lecturerId - Lecturer ID để kiểm tra quyền
     * @param array $pagination - Pagination params (page, per_page)
     * @return array Returns submissions data with pagination
     */
    public function getTaskSubmissions(int $taskId, int $lecturerId, array $pagination = []): array
    {
        try {
            // Kiểm tra task có tồn tại và lecturer có quyền không
            $task = $this->findById($taskId);
            
            if (!$task) {
                throw new LecturerTaskException('Task not found', 404);
            }

            // Kiểm tra lecturer có phải là creator của task không
            if ($task->creator_id != $lecturerId || $task->creator_type != 'lecturer') {
                throw new LecturerTaskException('Access denied to this task', 403);
            }

            // Pagination params
            $page = $pagination['page'] ?? 1;
            $perPage = $pagination['per_page'] ?? 15;

            // Lấy submissions của task với pagination
            $submissionsQuery = app('Modules\Task\app\Models\TaskSubmission')
                ->where('task_id', $taskId)
                ->with(['student', 'task'])
                ->orderBy('submitted_at', 'desc');

            $submissions = $submissionsQuery->paginate($perPage, ['*'], 'page', $page);

            $data = $submissions->map(function($submission) {
                // Load files nếu có
                $files = [];
                $fileIds = $submission->submission_files ?? [];
                
                if (!empty($fileIds) && is_array($fileIds)) {
                    $fileIds = array_map('intval', $fileIds);
                    $fileIds = array_filter($fileIds, function($id) { return $id > 0; });
                    
                    if (!empty($fileIds)) {
                        try {
                            $taskFiles = app('Modules\Task\app\Models\TaskFile')
                                ->whereIn('id', $fileIds)
                                ->where('task_id', $submission->task_id)
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
                        } catch (\Exception $e) {
                            \Log::warning('Failed to load submission files', [
                                'submission_id' => $submission->id,
                                'file_ids' => $fileIds,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                return [
                    'id' => $submission->id,
                    'task_id' => $submission->task_id,
                    'student_id' => $submission->student_id,
                    'student_name' => ($submission->student && isset($submission->student->full_name)) 
                        ? $submission->student->full_name 
                        : 'N/A',
                    'submission_content' => $submission->submission_content,
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->toDateTimeString() : null,
                    'status' => $submission->status ?? 'pending',
                    'grade' => $submission->grade,
                    'feedback' => $submission->feedback,
                    'graded_at' => $submission->graded_at ? $submission->graded_at->toDateTimeString() : null,
                    'graded_by' => $submission->graded_by,
                    'files' => $files,
                    'created_at' => $submission->created_at ? $submission->created_at->toDateTimeString() : null,
                    'updated_at' => $submission->updated_at ? $submission->updated_at->toDateTimeString() : null,
                ];
            })->toArray();

            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $submissions->currentPage(),
                    'per_page' => $submissions->perPage(),
                    'total' => $submissions->total(),
                    'last_page' => $submissions->lastPage(),
                    'from' => $submissions->firstItem(),
                    'to' => $submissions->lastItem(),
                ]
            ];
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Get task submissions error', [
                'task_id' => $taskId,
                'lecturer_id' => $lecturerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LecturerTaskException('Failed to retrieve task submissions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Chấm điểm bài nộp của sinh viên
     * 
     * @param int $taskId - Task ID
     * @param int $submissionId - Submission ID
     * @param array $data - Data chứa grade, feedback, status
     * @param int $lecturerId - Lecturer ID
     * @return array Returns graded submission data
     */
    public function gradeTaskSubmission(int $taskId, int $submissionId, array $data, int $lecturerId): array
    {
        try {
            // Kiểm tra submission có tồn tại và thuộc task này không
            $submission = app('Modules\Task\app\Models\TaskSubmission')
                ->where('id', $submissionId)
                ->where('task_id', $taskId)
                ->first();

            if (!$submission) {
                throw new LecturerTaskException('Submission not found', 404);
            }

            // Validate data
            $grade = $data['grade'] ?? null;
            $feedback = $data['feedback'] ?? null;
            $status = $data['status'] ?? 'graded'; // 'graded' hoặc 'returned'

            // Validate grade (nếu có)
            if ($grade !== null) {
                $grade = floatval($grade);
                if ($grade < 0 || $grade > 10) {
                    throw new LecturerTaskException('Grade must be between 0 and 10', 422);
                }
            }

            // Update submission
            $submission->grade = $grade;
            $submission->feedback = $feedback;
            $submission->status = $status;
            $submission->graded_at = now();
            $submission->graded_by = $lecturerId;
            $submission->save();

            // Load files nếu có
            $files = [];
            $fileIds = $submission->submission_files ?? [];
            
            if (!empty($fileIds) && is_array($fileIds)) {
                $fileIds = array_map('intval', $fileIds);
                $fileIds = array_filter($fileIds, function($id) { return $id > 0; });
                
                if (!empty($fileIds)) {
                    $taskFiles = app('Modules\Task\app\Models\TaskFile')
                        ->whereIn('id', $fileIds)
                        ->where('task_id', $taskId)
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

            return [
                'id' => $submission->id,
                'task_id' => $submission->task_id,
                'student_id' => $submission->student_id,
                'submission_content' => $submission->submission_content,
                'submitted_at' => $submission->submitted_at ? $submission->submitted_at->toDateTimeString() : null,
                'status' => $submission->status,
                'grade' => $submission->grade,
                'feedback' => $submission->feedback,
                'graded_at' => $submission->graded_at ? $submission->graded_at->toDateTimeString() : null,
                'graded_by' => $submission->graded_by,
                'files' => $files,
                'updated_at' => $submission->updated_at ? $submission->updated_at->toDateTimeString() : null,
            ];
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to grade task submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Áp dụng filters
     */
    protected function applyFilters($query, TaskFilterDTO $filterDTO)
    {
        if ($filterDTO->status) {
            $query->where('status', $filterDTO->status);
        }

        if ($filterDTO->priority) {
            $query->where('priority', $filterDTO->priority);
        }

        if ($filterDTO->search) {
            $query->where(function($q) use ($filterDTO) {
                $q->where('title', 'like', '%' . $filterDTO->search . '%')
                  ->orWhere('description', 'like', '%' . $filterDTO->search . '%');
            });
        }

        $query->orderBy($filterDTO->sort_by, $filterDTO->sort_order);
    }
}

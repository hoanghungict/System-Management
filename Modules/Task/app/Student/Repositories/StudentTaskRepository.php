<?php

namespace Modules\Task\app\Student\Repositories;

use Modules\Task\app\Student\DTOs\StudentTaskFilterDTO;
use Modules\Task\app\Student\DTOs\SubmitTaskDTO;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Student Task Repository
 * 
 * Repository dành riêng cho Student package
 * Tuân theo Clean Architecture
 */
class StudentTaskRepository
{
    protected $taskModel;
    protected $taskSubmissionModel;

    public function __construct()
    {
        $this->taskModel = app('Modules\Task\app\Models\Task');
        // Giả sử có model TaskSubmission
        $this->taskSubmissionModel = app('Modules\Task\app\Models\TaskSubmission');
    }

    /**
     * Lấy task theo ID với kiểm tra quyền
     */
    public function getTaskById($taskId, $studentId, $userType)
    {
        try {
            // Tìm task trực tiếp trước
            $task = $this->taskModel->with(['receivers', 'files'])->find($taskId);
            
            if (!$task) {
                return null;
            }

            // Kiểm tra xem student có được giao task này không
            $hasReceiver = $task->receivers->where('receiver_id', $studentId)
                                          ->where('receiver_type', 'student')
                                          ->isNotEmpty();
            
            // Nếu không có receiver relationship, có thể task được giao cho tất cả students
            // Hoặc có thể là task public
            if (!$hasReceiver) {
                // Kiểm tra xem task có include_new_students không
                if (!$task->include_new_students) {
                    return null;
                }
            }
            
            return $task;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to find task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks của sinh viên
     */
    public function getStudentTasks($studentId, StudentTaskFilterDTO $filterDTO)
    {
        try {
            $query = $this->taskModel->with(['receivers', 'files'])
                ->whereHas('receivers', function($q) use ($studentId) {
                    $q->where('receiver_id', $studentId)
                      ->where('receiver_type', 'student');
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
            throw new StudentTaskException('Failed to retrieve student tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks đang chờ submit
     */
    public function getPendingTasks($studentId, StudentTaskFilterDTO $filterDTO)
    {
        try {
            $query = $this->taskModel->with(['receivers', 'files'])
                ->whereHas('receivers', function($q) use ($studentId) {
                    $q->where('receiver_id', $studentId)
                      ->where('receiver_type', 'student');
                })
                ->where('status', 'pending')
                ->where('deadline', '>', now());

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
            throw new StudentTaskException('Failed to retrieve pending tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks đã submit
     */
    public function getSubmittedTasks($studentId, StudentTaskFilterDTO $filterDTO)
    {
        try {
            $query = $this->taskModel->with(['receivers', 'files'])
                ->whereHas('receivers', function($q) use ($studentId) {
                    $q->where('receiver_id', $studentId)
                      ->where('receiver_type', 'student');
                })
                ->whereHas('submissions', function($q) use ($studentId) {
                    $q->where('student_id', $studentId);
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
            throw new StudentTaskException('Failed to retrieve submitted tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks quá hạn
     */
    public function getOverdueTasks($studentId, StudentTaskFilterDTO $filterDTO)
    {
        try {
            $query = $this->taskModel->with(['receivers', 'files'])
                ->whereHas('receivers', function($q) use ($studentId) {
                    $q->where('receiver_id', $studentId)
                      ->where('receiver_type', 'student');
                })
                ->where('deadline', '<', now())
                ->where('status', '!=', 'completed');

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
            throw new StudentTaskException('Failed to retrieve overdue tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit task
     */
    public function submitTask(SubmitTaskDTO $submitTaskDTO)
    {
        try {
            $submission = $this->taskSubmissionModel->create($submitTaskDTO->toArray());
            return $submission;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to submit task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy task submission
     */
    public function getTaskSubmission($taskId, $studentId)
    {
        try {
            return $this->taskSubmissionModel->where('task_id', $taskId)
                ->where('student_id', $studentId)
                ->first();
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve task submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật task submission
     */
    public function updateTaskSubmission($taskId, $data, $studentId)
    {
        try {
            $submission = $this->getTaskSubmission($taskId, $studentId);
            if ($submission) {
                $submission->update($data);
                return $submission;
            }
            return null;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to update task submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật status của task
     */
    public function updateTaskStatus($taskId, $status)
    {
        try {
            $task = $this->taskModel->find($taskId);
            if ($task) {
                $task->update(['status' => $status]);
            }
            return true;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to update task status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy thống kê của sinh viên
     */
    public function getStudentStatistics($studentId)
    {
        try {
            // Query trực tiếp từ task_submissions thay vì sử dụng relationship
            $totalTasks = $this->taskModel->count();
            
            $pendingTasks = $this->taskModel->where('status', 'pending')->count();

            $submittedTasks = $this->taskSubmissionModel->where('student_id', $studentId)->count();

            $overdueTasks = $this->taskSubmissionModel->where('student_id', $studentId)
                ->where('status', 'overdue')->count();

            $averageScore = $this->taskSubmissionModel->where('student_id', $studentId)
                ->whereNotNull('grade')
                ->avg('grade') ?? 0;

            $onTimeSubmissions = $this->taskSubmissionModel->where('student_id', $studentId)
                ->where('task_submissions.status', 'graded')
                ->join('task', 'task_submissions.task_id', '=', 'task.id')
                ->whereRaw('task_submissions.submitted_at <= task.deadline')
                ->count();

            $lateSubmissions = $this->taskSubmissionModel->where('student_id', $studentId)
                ->where('task_submissions.status', 'graded')
                ->join('task', 'task_submissions.task_id', '=', 'task.id')
                ->whereRaw('task_submissions.submitted_at > task.deadline')
                ->count();

            return [
                'total_tasks' => $totalTasks,
                'pending_tasks' => $pendingTasks,
                'submitted_tasks' => $submittedTasks,
                'overdue_tasks' => $overdueTasks,
                'completed_tasks' => $submittedTasks, // Giả sử submitted = completed
                'average_score' => round($averageScore, 2),
                'total_submissions' => $submittedTasks,
                'on_time_submissions' => $onTimeSubmissions,
                'late_submissions' => $lateSubmissions,
                'completion_rate' => $totalTasks > 0 ? round(($submittedTasks / $totalTasks) * 100, 2) : 0,
                'overdue_rate' => $totalTasks > 0 ? round(($overdueTasks / $totalTasks) * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy task files
     */
    public function getTaskFiles($taskId, $studentId)
    {
        try {
            $task = $this->getTaskById($taskId, $studentId, 'student');
            if ($task) {
                return $task->files;
            }
            return [];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve task files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Upload file cho task
     */
    public function uploadTaskFile($taskId, $file, $studentId)
    {
        try {
            // Giả sử có logic upload file
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('task_files', $filename);
            
            return [
                'task_id' => $taskId,
                'student_id' => $studentId,
                'filename' => $filename,
                'path' => $path,
                'size' => $file->getSize(),
                'uploaded_at' => now()
            ];
        } catch (\Exception $e) {
            throw StudentTaskException::fileUploadFailed($file->getClientOriginalName());
        }
    }

    /**
     * Xóa file của task
     */
    public function deleteTaskFile($fileId, $studentId)
    {
        try {
            // Giả sử có logic xóa file
            return true;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to delete task file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy task file
     */
    public function getTaskFile($fileId, $studentId)
    {
        try {
            // Giả sử có logic lấy file
            return null;
        } catch (\Exception $e) {
            throw StudentTaskException::fileNotFound($fileId);
        }
    }

    /**
     * Áp dụng filters
     */
    protected function applyFilters($query, StudentTaskFilterDTO $filterDTO)
    {
        if ($filterDTO->status) {
            $query->where('status', $filterDTO->status);
        }

        if ($filterDTO->priority) {
            $query->where('priority', $filterDTO->priority);
        }

        if ($filterDTO->due_date_from) {
            $query->where('deadline', '>=', $filterDTO->due_date_from);
        }

        if ($filterDTO->due_date_to) {
            $query->where('deadline', '<=', $filterDTO->due_date_to);
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

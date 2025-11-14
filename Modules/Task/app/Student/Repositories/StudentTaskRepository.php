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
            $data = $submitTaskDTO->toArray();
            
            // Đảm bảo submission_files là array và được encode đúng
            if (isset($data['submission_files'])) {
                // Nếu là array, đảm bảo nó được serialize đúng
                if (is_array($data['submission_files'])) {
                    // Filter out null/empty values và đảm bảo là integers
                    $data['submission_files'] = array_filter(
                        array_map('intval', $data['submission_files']),
                        function($id) { return $id > 0; }
                    );
                    $data['submission_files'] = array_values($data['submission_files']); // Re-index array
                } else {
                    $data['submission_files'] = [];
                }
            } else {
                $data['submission_files'] = [];
            }
            
            // Log để debug
            \Log::info('Submitting task', [
                'task_id' => $data['task_id'],
                'student_id' => $data['student_id'],
                'submission_files' => $data['submission_files'],
                'submission_files_type' => gettype($data['submission_files'])
            ]);
            
            $submission = $this->taskSubmissionModel->create($data);
            
            // Log sau khi create để verify
            \Log::info('Task submitted', [
                'submission_id' => $submission->id,
                'submission_files' => $submission->submission_files,
                'submission_files_type' => gettype($submission->submission_files)
            ]);
            
            return $submission;
        } catch (\Exception $e) {
            \Log::error('Failed to submit task', [
                'error' => $e->getMessage(),
                'data' => $submitTaskDTO->toArray(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new StudentTaskException('Failed to submit task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy task submission đơn giản (không có files)
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
     * Lấy task submission với files và grade được format đúng
     * 
     * @param int $taskId
     * @param int $studentId
     * @return array|null Returns formatted submission data hoặc null nếu không có
     */
    public function getTaskSubmissionWithFiles($taskId, $studentId)
    {
        try {
            // Lấy submission cơ bản
            $submission = $this->taskSubmissionModel->where('task_id', $taskId)
                ->where('student_id', $studentId)
                ->first();

            if (!$submission) {
                return null;
            }

            // Load files từ submission_files (array IDs) trong task_submissions
            $files = [];
            try {
                // Lấy file IDs từ submission_files field (đã được cast thành array)
                $fileIds = $submission->submission_files ?? [];
                
                // Debug: Log để kiểm tra
                \Log::info('Loading submission files', [
                    'task_id' => $taskId,
                    'student_id' => $studentId,
                    'submission_id' => $submission->id,
                    'submission_files_raw' => $submission->getRawOriginal('submission_files') ?? null,
                    'submission_files_casted' => $fileIds,
                    'file_ids_type' => gettype($fileIds),
                    'file_ids_count' => is_array($fileIds) ? count($fileIds) : 0
                ]);
                
                // Nếu có file IDs, load files từ task_file table
                if (!empty($fileIds) && is_array($fileIds)) {
                    // Đảm bảo file IDs là integers
                    $fileIds = array_map('intval', $fileIds);
                    $fileIds = array_filter($fileIds, function($id) { return $id > 0; });
                    
                    if (!empty($fileIds)) {
                        $taskFiles = app('Modules\Task\app\Models\TaskFile')
                            ->whereIn('id', $fileIds)
                            ->where('task_id', $taskId) // Đảm bảo files thuộc đúng task
                            ->get();

                        \Log::info('Files found', [
                            'file_ids_requested' => $fileIds,
                            'files_found_count' => $taskFiles->count(),
                            'files_found_ids' => $taskFiles->pluck('id')->toArray()
                        ]);

                        $files = $taskFiles->map(function($file) {
                            return [
                                'id' => $file->id,
                                'file_name' => $file->name ?? $file->file_name ?? basename($file->path ?? ''),
                                'name' => $file->name ?? $file->file_name ?? basename($file->path ?? ''), // Alias
                                'file_path' => $file->path ?? '',
                                'file_url' => $file->file_url ?? '', // Model có accessor getFileUrlAttribute
                                'file_size' => $file->size ?? 0,
                                'size' => $file->size ?? 0, // Alias
                                'mime_type' => $file->mime_type ?? null,
                                'created_at' => $file->created_at ? $file->created_at->toDateTimeString() : null,
                            ];
                        })->toArray();
                    } else {
                        \Log::warning('File IDs empty after filtering', [
                            'original_file_ids' => $submission->submission_files ?? []
                        ]);
                    }
                } else {
                    \Log::info('No file IDs in submission', [
                        'submission_files' => $fileIds,
                        'is_array' => is_array($fileIds),
                        'is_empty' => empty($fileIds)
                    ]);
                }
                // Nếu không có file IDs, return empty array (không có files trong submission)
            } catch (\Exception $fileError) {
                // Log warning nhưng không fail request
                \Log::error('Failed to load submission files', [
                    'task_id' => $taskId,
                    'student_id' => $studentId,
                    'submission_id' => $submission->id ?? null,
                    'submission_files' => $submission->submission_files ?? null,
                    'submission_files_raw' => isset($submission) ? $submission->getRawOriginal('submission_files') : null,
                    'error' => $fileError->getMessage(),
                    'trace' => $fileError->getTraceAsString()
                ]);
                $files = []; // Return empty array
            }

            // Format grade nếu có
            $grade = null;
            if ($submission->graded_at && $submission->grade !== null) {
                try {
                    $grade = [
                        'score' => (float) $submission->grade,
                        'feedback' => $submission->feedback ?? null,
                        'graded_at' => $submission->graded_at ? $submission->graded_at->toDateTimeString() : null,
                    ];

                    // Load grader info nếu có
                    if ($submission->graded_by) {
                        try {
                            $grader = app('Modules\Auth\app\Models\Lecturer')->find($submission->graded_by);
                            if ($grader) {
                                $grade['graded_by'] = [
                                    'id' => $grader->id,
                                    'name' => $grader->name ?? $grader->lecturer_name ?? 'Unknown'
                                ];
                            }
                        } catch (\Exception $graderError) {
                            \Log::warning('Failed to load grader info', [
                                'graded_by' => $submission->graded_by,
                                'error' => $graderError->getMessage()
                            ]);
                        }
                    }
                } catch (\Exception $gradeError) {
                    \Log::warning('Failed to format grade', [
                        'error' => $gradeError->getMessage()
                    ]);
                    $grade = null;
                }
            }

            // Format response theo cấu trúc database thực tế
            return [
                'id' => $submission->id,
                'task_id' => $submission->task_id,
                'student_id' => $submission->student_id,
                'content' => $submission->submission_content ?? '',
                'submission_content' => $submission->submission_content ?? '', // Alias
                'submitted_at' => $submission->submitted_at ? $submission->submitted_at->toDateTimeString() : null,
                'updated_at' => $submission->updated_at ? $submission->updated_at->toDateTimeString() : null,
                'status' => $submission->status ?? 'submitted',
                'files' => $files, // ✅ Luôn là array, không phải null
                'grade' => $grade, // null nếu chưa chấm
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve task submission with files', [
                'task_id' => $taskId,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
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
                // Đảm bảo submission_files là array và được encode đúng
                if (isset($data['submission_files'])) {
                    if (is_array($data['submission_files'])) {
                        // Filter out null/empty values và đảm bảo là integers
                        $data['submission_files'] = array_filter(
                            array_map('intval', $data['submission_files']),
                            function($id) { return $id > 0; }
                        );
                        $data['submission_files'] = array_values($data['submission_files']); // Re-index array
                    } else {
                        $data['submission_files'] = [];
                    }
                }
                
                \Log::info('Updating submission', [
                    'task_id' => $taskId,
                    'student_id' => $studentId,
                    'submission_files' => $data['submission_files'] ?? null
                ]);
                
                $submission->update($data);
                
                // Refresh để lấy dữ liệu mới nhất
                $submission->refresh();
                
                \Log::info('Submission updated', [
                    'submission_id' => $submission->id,
                    'submission_files' => $submission->submission_files
                ]);
                
                return $submission;
            }
            return null;
        } catch (\Exception $e) {
            \Log::error('Failed to update task submission', [
                'task_id' => $taskId,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
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
            // Store file to public disk with task-specific directory
            $savePath = $file->store("task-files/{$taskId}", 'public');
            
            // Create TaskFile record in database
            $taskFile = \Modules\Task\app\Models\TaskFile::create([
                'task_id' => $taskId,
                'name'    => $file->getClientOriginalName(), // Tên file gốc
                'path'    => $savePath,                      // Đường dẫn trong storage
                'size'    => $file->getSize(),               // Kích thước file
            ]);
            
            // Return file data including ID
            return [
                'id'         => $taskFile->id,
                'task_id'    => $taskId,
                'student_id' => $studentId,
                'filename'   => $taskFile->name,
                'path'       => $taskFile->path,
                'size'       => $taskFile->size,
                'file_url'   => $taskFile->file_url,
                'uploaded_at' => $taskFile->created_at?->toDateTimeString() ?? now()->toDateTimeString(),
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

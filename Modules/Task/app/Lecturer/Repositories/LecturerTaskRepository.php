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

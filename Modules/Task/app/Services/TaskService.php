<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Modules\Task\app\Repositories\Interfaces\CachedTaskRepositoryInterface;
use Modules\Task\app\Repositories\Interfaces\CachedUserRepositoryInterface;
use Modules\Task\app\Repositories\Interfaces\CachedReportRepositoryInterface;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\DTOs\TaskDTO;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Services\ReminderService;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;
use Modules\Task\app\Events\TaskCreated;
use Modules\Task\app\Events\TaskUpdated;
use Modules\Task\app\Events\TaskAssigned;
use Modules\Task\app\Events\TaskSubmitted;
use Modules\Task\app\Events\TaskGraded;
use Modules\Task\app\Jobs\SendTaskCreatedNotificationJob;
use Modules\Task\app\Jobs\SendTaskUpdatedNotificationJob;
use Modules\Task\app\Jobs\SendTaskAssignedNotificationJob;
use Modules\Task\app\Jobs\SendTaskSubmittedNotificationJob;
use Modules\Task\app\Jobs\SendTaskGradedNotificationJob;
/**
 * Service chứa business logic cho Task
 * 
 * Service này chứa tất cả logic nghiệp vụ liên quan đến Task
 * Tuân thủ Clean Architecture: chỉ chứa business logic, không xử lý data access trực tiếp
 */
class TaskService implements TaskServiceInterface
{
    protected $taskRepository;
    protected $cachedTaskRepository;
    protected $cachedUserRepository;
    protected $cachedReportRepository;
    protected $reminderService;
    protected $permissionService;
    protected $kafkaProducer;

    /**
     * Khởi tạo service với dependency injection
     * 
     * @param TaskRepositoryInterface $taskRepository Repository xử lý data access
     * @param CachedTaskRepositoryInterface $cachedTaskRepository Repository có cache cho đọc dữ liệu
     * @param CachedUserRepositoryInterface $cachedUserRepository Repository có cache cho user data
     * @param CachedReportRepositoryInterface $cachedReportRepository Repository có cache cho reports
     * @param PermissionService $permissionService Service xử lý permissions
     * @param ReminderService $reminderService Service xử lý reminders
     * @param KafkaProducerService $kafkaProducer Service xử lý kafka
     */
    public function __construct(
        TaskRepositoryInterface $taskRepository, 
        CachedTaskRepositoryInterface $cachedTaskRepository,
        CachedUserRepositoryInterface $cachedUserRepository,
        CachedReportRepositoryInterface $cachedReportRepository,
        PermissionService $permissionService,
        ReminderService $reminderService,
        KafkaProducerService $kafkaProducer

    ) {
        $this->taskRepository = $taskRepository;
        $this->cachedTaskRepository = $cachedTaskRepository;
        $this->cachedUserRepository = $cachedUserRepository;
        $this->cachedReportRepository = $cachedReportRepository;
        $this->permissionService = $permissionService;
        $this->reminderService = $reminderService;
        $this->kafkaProducer = $kafkaProducer;
    }

    /**
     * Tạo mới một task với business logic
     * 
     * @param array $data Dữ liệu task cần tạo
     * @param object|null $userContext User context cho permission checking
     * @return Task Task vừa được tạo
     * @throws \Exception Nếu có lỗi trong quá trình tạo
     */
    public function createTask(array $data, ?object $userContext = null): Task
    {
        // ✅ Use database transaction to ensure data consistency
        return DB::transaction(function () use ($data, $userContext) {
            try {
                // ✅ Security validation
                if ($userContext) {
                    $this->validateCreateTaskPermissions($userContext, $data);
                }
                
                // Tách receivers ra khỏi data chính
                $receivers = $data['receivers'] ?? [];
                unset($data['receivers']);
                
                // ✅ Validate business rules with security context
                $this->validateTaskData($data, $receivers, $userContext);
                
                // Tạo task
                $task = $this->taskRepository->create($data);
                
                // Thêm receivers cho task trong cùng transaction
                $this->addReceiversToTask($task, $receivers);
                
                // ✅ Load receivers để collect cache keys
                $task->load('receivers');
                
                Log::info('Task created', [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'creator_id' => $task->creator_id,
                    'receivers_count' => count($receivers),
                    'created_by_user' => $userContext ? $userContext->id : 'system'
                ]);
                
                // ✅ Collect và invalidate affected caches
                $affectedCacheKeys = $this->collectAffectedCacheKeys($task);
                $this->invalidateMultipleCaches($affectedCacheKeys);

                $this->kafkaProducer->send('task.assigned', [
                    'user_id' => $task->creator_id,
                    'name' => $task->creator_name ?? "Unknown",
                    'user_name' =>$task->creator_name ?? "Unknown",
                    'user_email' => $task->creator_email ?? 'no-email@example.com'
                ]);

                // ✅ Create automatic reminders for task
                if ($task->deadline) {
                    $this->reminderService->createAutomaticReminders($task);
                }

                // ✅ Dispatch TaskCreated event for notifications
                event(new TaskCreated($task, [
                    'creator_id' => $task->creator_id,
                    'creator_type' => $task->creator_type,
                    'receivers' => $receivers
                ]));

                // ✅ Dispatch notification jobs for each receiver
                foreach ($task->receivers as $receiver) {
                    SendTaskCreatedNotificationJob::dispatch(new TaskCreated($task, [
                        'receiver_id' => $receiver->id,
                        'receiver_type' => $receiver->type
                    ]));
                }

                return $task;
            } catch (\Exception $e) {
                Log::error('Error creating task in transaction', [
                    'error' => $e->getMessage(),
                    'user_context' => $userContext ? $userContext->id : 'system',
                    'data' => $data
                ]);
                throw $e;
            }
        });
    }

    /**
     * Cập nhật một task với business logic
     * 
     * @param Task $task Task cần cập nhật
     * @param array $data Dữ liệu cập nhật
     * @param object|null $userContext User context cho permission checking
     * @return Task Task sau khi cập nhật
     * @throws \Exception Nếu có lỗi trong quá trình cập nhật
     */
    public function updateTask(Task $task, array $data, ?object $userContext = null): Task
    {
        // ✅ Use database transaction to ensure data consistency
        return DB::transaction(function () use ($task, $data, $userContext) {
            try {
                // ✅ Security validation
                if ($userContext) {
                    $this->validateEditTaskPermission($userContext, $task->id);
                }
                
                // ✅ Load receivers trước để tránh N+1 queries
                $task->load('receivers');
                
                // ✅ Track changes for notifications
                $originalData = $task->toArray();
                
                // Collect cache keys trước khi update
                $affectedCacheKeys = $this->collectAffectedCacheKeys($task);
                
                // Tách receivers ra khỏi data chính
                $receivers = $data['receivers'] ?? null;
                unset($data['receivers']);
                
                // ✅ Validate update data
                $this->validateUpdateData($data, $receivers, $userContext, $task);
                
                // Cập nhật task
                $task = $this->taskRepository->update($task, $data);
                
                // Cập nhật receivers nếu có (trong transaction)
                if ($receivers !== null) {
                    $this->updateReceiversForTask($task, $receivers);
                    // Refresh task với receivers mới
                    $task->load('receivers');
                    // Add new cache keys
                    $affectedCacheKeys = array_merge($affectedCacheKeys, $this->collectAffectedCacheKeys($task));
                }
                
                Log::info('Task updated', [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'receivers_updated' => $receivers !== null,
                    'cache_keys_affected' => count($affectedCacheKeys),
                    'updated_by_user' => $userContext ? $userContext->id : 'system'
                ]);
                
                // ✅ Bulk cache invalidation thay vì từng cái một
                $this->invalidateMultipleCaches($affectedCacheKeys);
                
                // ✅ Clear permission cache
                $this->clearTaskPermissionCache($task);

                // ✅ Track changes and dispatch events
                $changes = $this->trackChanges($originalData, $task->toArray());
                
                if (!empty($changes)) {
                    // Dispatch TaskUpdated event
                    event(new TaskUpdated($task, $changes, [
                        'updater_id' => $userContext?->id ?? $task->creator_id,
                        'updater_type' => $userContext?->role ?? $task->creator_type
                    ]));

                    // Dispatch notification jobs for each receiver
                    foreach ($task->receivers as $receiver) {
                        SendTaskUpdatedNotificationJob::dispatch(new TaskUpdated($task, $changes, [
                            'receiver_id' => $receiver->id,
                            'receiver_type' => $receiver->type
                        ]));
                    }
                }

                return $task;
            } catch (\Exception $e) {
                Log::error('Error updating task in transaction', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                    'user_context' => $userContext ? $userContext->id : 'system'
                ]);
                throw $e;
            }
        });
    }

    /**
     * Xóa một task với business logic
     * 
     * @param Task $task Task cần xóa
     * @param object|null $userContext User context cho permission checking
     * @return bool True nếu xóa thành công
     * @throws \Exception Nếu có lỗi trong quá trình xóa
     */
    public function deleteTask(Task $task, ?object $userContext = null): bool
    {
        try {
            // ✅ Security validation
            if ($userContext) {
                $this->validateDeleteTaskPermission($userContext, $task->id);
            }
            
            // ✅ Load receivers trước để tránh N+1 queries
            $task->load('receivers');
            
            // Collect cache keys trước khi delete
            $affectedCacheKeys = $this->collectAffectedCacheKeys($task);
            
            $taskId = $task->id;
            $taskTitle = $task->title;
            
            // ✅ Clear permission cache trước khi delete
            $this->clearTaskPermissionCache($task);
            
            $result = $this->taskRepository->delete($task);
            
            Log::info('Task deleted', [
                'task_id' => $taskId,
                'title' => $taskTitle,
                'cache_keys_affected' => count($affectedCacheKeys),
                'deleted_by_user' => $userContext ? $userContext->id : 'system'
            ]);
            
            // ✅ Bulk cache invalidation
            $this->invalidateMultipleCaches($affectedCacheKeys);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error deleting task: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy task theo ID
     * 
     * @param int $id ID của task
     * @return Task|null Task nếu tìm thấy, null nếu không tìm thấy
     */
    public function getTaskById(int $id): ?Task
    {
        return $this->cachedTaskRepository->findById($id);
    }

    /**
     * Lấy tất cả tasks với filters (admin)
     * 
     * @param array $filters Các bộ lọc
     * @param int $perPage Số lượng items per page
     * @return LengthAwarePaginator
     */
    public function getAllTasks(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getAllTasksWithFilters($filters, $perPage);
    }

    /**
     * Lấy tasks với bộ lọc
     * 
     * @param array $filters Mảng chứa các điều kiện lọc
     * @param int $perPage Số lượng task trên mỗi trang
     * @return LengthAwarePaginator Danh sách tasks đã lọc và phân trang
     */
    public function getTasksWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->cachedTaskRepository->getTasksWithFilters($filters, $perPage);
    }

    /**
     * Lấy tasks theo người nhận
     * 
     * @param int $receiverId ID người nhận
     * @param string $receiverType Loại người nhận
     * @return mixed Danh sách tasks của người nhận
     */
    public function getTasksByReceiver(int $receiverId, string $receiverType)
    {
        return $this->taskRepository->getTasksByReceiver($receiverId, $receiverType);
    }

    /**
     * Lấy tasks theo người tạo
     * 
     * @param int $creatorId ID người tạo
     * @param string $creatorType Loại người tạo
     * @return mixed Danh sách tasks của người tạo
     */
    public function getTasksByCreator(int $creatorId, string $creatorType)
    {
        return $this->taskRepository->getTasksByCreator($creatorId, $creatorType);
    }

    /**
     * Lấy thống kê task
     * 
     * @return array Thống kê về tasks
     */
    public function getTaskStatistics(): array
    {
        return $this->cachedTaskRepository->getTaskStatistics();
    }

    /**
     * Thêm receivers cho task
     * 
     * @param Task $task
     * @param array $receivers
     */
    private function addReceiversToTask(Task $task, array $receivers): void
    {
        foreach ($receivers as $receiver) {
            $task->addReceiver($receiver['receiver_id'], $receiver['receiver_type']);
        }
    }

    /**
     * ✅ Cập nhật receivers cho task với transaction safety
     * 
     * @param Task $task
     * @param array $receivers
     */
    private function updateReceiversForTask(Task $task, array $receivers): void
    {
        // ✅ Thực hiện trong sub-transaction để ensure atomicity
        DB::transaction(function () use ($task, $receivers) {
            // Lấy current receivers để log changes
            $oldReceivers = $task->receivers()->get(['receiver_id', 'receiver_type'])->toArray();
            
            // Xóa tất cả receivers cũ
            $deletedCount = $task->receivers()->delete();
            
            // Thêm receivers mới
            $this->addReceiversToTask($task, $receivers);
            
            Log::info('Task receivers updated', [
                'task_id' => $task->id,
                'old_receivers_count' => $deletedCount,
                'new_receivers_count' => count($receivers),
                'old_receivers' => $oldReceivers,
                'new_receivers' => $receivers
            ]);
        });
    }

    /**
     * Lấy tasks cho một user cụ thể
     * 
     * @param int $userId
     * @param string $userType
     * @return mixed
     */
    public function getTasksForUser(int $userId, string $userType)
    {
        return $this->taskRepository->getTasksForUser($userId, $userType);
    }

    /**
     * Lấy danh sách tasks cho người dùng hiện tại
     * 
     * @param mixed $user User hiện tại
     * @param int $perPage Số lượng items per page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getTasksForCurrentUser($user, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        // Kiểm tra user có tồn tại không
        if (!$user || !isset($user->id)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }
        
        $userType = $this->getUserType($user);
        $userId = $user->id;
        
        return $this->cachedTaskRepository->getTasksForUser($userId, $userType, $perPage);
    }

    /**
     * Lấy danh sách tasks đã tạo bởi người dùng
     * 
     * @param mixed $user User hiện tại
     * @param int $perPage Số lượng items per page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getTasksCreatedByUser($user, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        // Kiểm tra user có tồn tại không
        if (!$user || !isset($user->id)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }
        
        $userType = $this->getUserType($user);
        $userId = $user->id;
        
        return $this->cachedTaskRepository->getTasksCreatedByUser($userId, $userType, $perPage);
    }

    /**
     * Kiểm tra quyền cập nhật trạng thái task
     * 
     * @param mixed $user User hiện tại
     * @param Task $task Task cần kiểm tra
     * @return bool
     */
    public function canUpdateTaskStatus($user, Task $task): bool
    {
        // Kiểm tra user có tồn tại không
        if (!$user || !isset($user->id)) {
            return false;
        }
        
        // Kiểm tra task có tồn tại không
        if (!$task || !$task->id) {
            return false;
        }
        
        // Sử dụng PermissionService để kiểm tra quyền
        $userContext = (object) [
            'id' => $user->id,
            'user_type' => $this->getUserType($user)
        ];
        
        return $this->permissionService->canUpdateTaskStatus($userContext, $task->id);
    }

    /**
     * Cập nhật trạng thái task
     * 
     * @param Task $task Task cần cập nhật
     * @param string $status Trạng thái mới
     * @return Task
     */
    public function updateTaskStatus(Task $task, string $status): Task
    {
        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Trạng thái không hợp lệ');
        }
        
        return $this->taskRepository->update($task, ['status' => $status]);
    }

    /**
     * Kiểm tra quyền upload files cho task
     * 
     * @param mixed $user User hiện tại
     * @param Task $task Task cần kiểm tra
     * @return bool
     */
    public function canUploadFiles($user, Task $task): bool
    {
        // Kiểm tra xem user có phải là người nhận task không
        return $this->canUpdateTaskStatus($user, $task);
    }

    /**
     * Upload files cho task
     * 
     * @param Task $task Task cần upload files
     * @param array $files Files cần upload
     * @return array
     */
    public function uploadTaskFiles(Task $task, array $files): array
    {
        $uploadedFiles = [];
        
        foreach ($files as $file) {
            $fileData = [
                'task_id' => $task->id,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'path' => $file->store('task-files/' . $task->id),
                'uploaded_by' => $user->id ?? (Auth::user() ? Auth::user()->id : null),
                'uploaded_at' => now()
            ];
            
            $uploadedFile = $this->taskRepository->createTaskFile($fileData);
            $uploadedFiles[] = $uploadedFile;
        }
        
        return $uploadedFiles;
    }

    /**
     * Kiểm tra quyền xóa file
     * 
     * @param mixed $user User hiện tại
     * @param Task $task Task chứa file
     * @param int $fileId ID của file
     * @return bool
     */
    public function canDeleteFile($user, Task $task, int $fileId): bool
    {
        $file = $this->taskRepository->findTaskFile($fileId);
        
        if (!$file) {
            return false;
        }
        
        // Kiểm tra xem user có phải là người upload file không
        return $file->uploaded_by == $user->id;
    }

    /**
     * Xóa file của task
     * 
     * @param Task $task Task chứa file
     * @param int $fileId ID của file
     * @return bool
     */
    public function deleteTaskFile(Task $task, int $fileId): bool
    {
        return $this->taskRepository->deleteTaskFile($fileId);
    }

    /**
     * Lấy thống kê tasks của người dùng
     * 
     * @param mixed $user User hiện tại
     * @return array
     */
    public function getUserTaskStatistics($user): array
    {
        // Kiểm tra user có tồn tại không
        if (!$user || !isset($user->id)) {
            return [
                'total' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'cancelled' => 0
            ];
        }
        
        $userType = $this->getUserType($user);
        $userId = $user->id;
        
        return $this->taskRepository->getUserTaskStatistics($userId, $userType);
    }

    /**
     * Lấy thống kê tasks đã tạo
     * 
     * @param mixed $user User hiện tại
     * @return array
     */
    public function getCreatedTaskStatistics($user): array
    {
        // Kiểm tra user có tồn tại không
        if (!$user || !isset($user->id)) {
            return [
                'total' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'cancelled' => 0
            ];
        }
        
        $userType = $this->getUserType($user);
        $userId = $user->id;
        
        return $this->taskRepository->getCreatedTaskStatistics($userId, $userType);
    }

    /**
     * Lấy thống kê tổng quan (admin)
     * 
     * @return array
     */
    public function getOverviewTaskStatistics(): array
    {
        return $this->taskRepository->getOverviewTaskStatistics();
    }

    /**
     * Kiểm tra quyền gán task
     * 
     * @param mixed $user User hiện tại
     * @param Task $task Task cần gán
     * @return bool
     */
    public function canAssignTask($user, Task $task): bool
    {
        // Kiểm tra user có tồn tại không
        if (!$user || !isset($user->id)) {
            return false;
        }
        
        // Chỉ người tạo task mới được gán
        $userType = $this->getUserType($user);
        $userId = $user->id;
        
        return $task->creator_id == $userId && $task->creator_type == $userType;
    }

    /**
     * Gán task cho receiver
     * 
     * @param Task $task Task cần gán
     * @param int $receiverId ID của receiver
     * @param string $receiverType Loại receiver
     * @return Task
     */
    public function assignTaskToReceiver(Task $task, int $receiverId, string $receiverType): Task
    {
        $receiverData = [
            'task_id' => $task->id,
            'receiver_id' => $receiverId,
            'receiver_type' => $receiverType,
            'assigned_at' => now()
        ];
        
        $this->taskRepository->addReceiverToTask($receiverData);
        
        return $task->fresh();
    }

    /**
     * Kiểm tra quyền thu hồi task
     * 
     * @param mixed $user User hiện tại
     * @param Task $task Task cần thu hồi
     * @return bool
     */
    public function canRevokeTask($user, Task $task): bool
    {
        // Chỉ người tạo task mới được thu hồi
        return $this->canAssignTask($user, $task);
    }

    /**
     * Thu hồi task
     * 
     * @param Task $task Task cần thu hồi
     * @return bool
     */
    public function revokeTask(Task $task): bool
    {
        return $this->taskRepository->deleteAllTaskReceivers($task->id);
    }

    /**
     * Tạo tasks định kỳ
     * 
     * @param array $data Dữ liệu task
     * @param mixed $user User tạo task
     * @return array
     */
    public function createRecurringTasks(array $data, $user): array
    {
        $recurringTasks = [];
        $pattern = $data['recurring_pattern'];
        $endDate = \Carbon\Carbon::parse($data['recurring_end_date']);
        $currentDate = \Carbon\Carbon::parse($data['deadline']);
        
        while ($currentDate <= $endDate) {
            $taskData = $data;
            $taskData['deadline'] = $currentDate->format('Y-m-d H:i:s');
            $taskData['creator_id'] = $user->id;
            $taskData['creator_type'] = $this->getUserType($user);
            
            $task = $this->createTask($taskData);
            $recurringTasks[] = $task;
            
            // Tăng ngày theo pattern
            switch ($pattern) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
            }
        }
        
        return $recurringTasks;
    }

    /**
     * Xóa task vĩnh viễn (admin)
     * 
     * @param Task $task Task cần xóa
     * @return bool
     */
    public function forceDeleteTask(Task $task): bool
    {
        return $this->taskRepository->forceDelete($task);
    }

    /**
     * Khôi phục task đã xóa (admin)
     * 
     * @param Task $task Task cần khôi phục
     * @return bool
     */
    public function restoreTask(Task $task): bool
    {
        return $this->taskRepository->restore($task);
    }

    /**
     * Lấy loại user từ model
     * 
     * @param mixed $user User object
     * @return string
     */
    protected function getUserType($user): string
    {
        // Nếu user có user_type property (từ JWT) - ưu tiên cao nhất
        if (isset($user->user_type)) {
            return $user->user_type;
        }
        
        // Kiểm tra nếu user là admin (có is_admin = true)
        if (isset($user->account) && isset($user->account['is_admin']) && $user->account['is_admin']) {
            return 'admin';
        }
        
        // Kiểm tra instance của model
        if ($user instanceof \Modules\Auth\app\Models\Lecturer) {
            return 'lecturer';
        } elseif ($user instanceof \Modules\Auth\app\Models\Student) {
            return 'student';
        } elseif ($user instanceof \Modules\Auth\app\Models\LecturerAccount) {
            return 'lecturer';
        } elseif ($user instanceof \Modules\Auth\app\Models\StudentAccount) {
            return 'student';
        }
        
        return 'unknown';
    }

    /**
     * ✅ Kiểm tra quyền tạo task cho receiver
     * 
     * @param mixed $user User tạo task
     * @param array $taskData Dữ liệu task
     * @return bool
     */
    public function canCreateTaskForReceiver($user, array $taskData): bool
    {
        $userType = $this->getUserType($user);
        
        // Admin có quyền tạo task cho tất cả
        if ($userType === 'admin') {
            return true;
        }
        
        // Lecturer chỉ có quyền tạo task cho student, class, all_students, all_lecturers
        if ($userType === 'lecturer') {
            $receiverType = $taskData['receivers'][0]['receiver_type'] ?? null;
            
            // Kiểm tra quyền tạo task cho lớp
            if ($receiverType === 'class') {
                $classId = $taskData['receivers'][0]['receiver_id'] ?? 0;
                return $this->permissionService->canCreateTasksForClass($user, $classId);
            }
            
            // Kiểm tra quyền tạo task cho khoa
            if ($receiverType === 'all_students' && $taskData['receivers'][0]['receiver_id'] > 0) {
                $departmentId = $taskData['receivers'][0]['receiver_id'];
                return $this->permissionService->canCreateTasksForDepartment($user, $departmentId);
            }
            
            return in_array($receiverType, ['student', 'all_lecturers']);
        }
        
        // Student không có quyền tạo task
        return false;
    }

    /**
     * Lấy danh sách faculties cho user
     * 
     * @param mixed $user User hiện tại
     * @return array
     */
    public function getFacultiesForUser($user): array
    {
        $userType = $this->getUserType($user);
        
        // Admin có thể xem tất cả departments
        if ($userType === 'admin') {
            return \Modules\Auth\app\Models\Department::all()->toArray();
        }
        
        // ✅ Lecturer chỉ có thể xem department của mình - Tối ưu query
        if ($userType === 'lecturer') {
            $lecturer = \Modules\Auth\app\Models\Lecturer::with('department')->find($user->id);
            if ($lecturer && $lecturer->department) {
                return [$lecturer->department->toArray()];
            }
        }
        
        return [];
    }

    /**
     * Lấy danh sách classes theo department cho user
     * 
     * @param mixed $user User hiện tại
     * @param int $departmentId ID của department
     * @return array
     */
    public function getClassesByDepartmentForUser($user, int $departmentId): array
    {
        $userType = $this->getUserType($user);
        
        // ✅ Admin có thể xem tất cả classes - eager load department
        if ($userType === 'admin') {
            return \Modules\Auth\app\Models\Classroom::with('department')
                ->where('department_id', $departmentId)
                ->get()
                ->toArray();
        }
        
        // ✅ Lecturer chỉ có thể xem classes thuộc department của mình - tối ưu query
        if ($userType === 'lecturer') {
            $lecturer = \Modules\Auth\app\Models\Lecturer::select('id', 'department_id')->find($user->id);
            if ($lecturer && $lecturer->department_id == $departmentId) {
                return \Modules\Auth\app\Models\Classroom::with('department')
                    ->where('department_id', $departmentId)
                    ->get()
                    ->toArray();
            }
        }
        
        return [];
    }

    /**
     * Lấy danh sách students theo class cho user
     * 
     * @param mixed $user User hiện tại
     * @param int $classId ID của class
     * @return array
     */
    public function getStudentsByClassForUser($user, int $classId): array
    {
        $userType = $this->getUserType($user);
        
        // ✅ Admin có thể xem tất cả students - eager load classroom
        if ($userType === 'admin') {
            return \Modules\Auth\app\Models\Student::with('classroom')
                ->where('class_id', $classId)
                ->get()
                ->toArray();
        }
        
        // ✅ Lecturer chỉ có thể xem students thuộc department của mình - tối ưu query
        if ($userType === 'lecturer') {
            $lecturer = \Modules\Auth\app\Models\Lecturer::select('id', 'department_id')->find($user->id);
            if ($lecturer && $lecturer->department_id) {
                // Single query với join thay vì 2 queries riêng biệt
                return \Modules\Auth\app\Models\Student::with('classroom')
                    ->whereHas('classroom', function($query) use ($classId, $lecturer) {
                        $query->where('id', $classId)
                              ->where('department_id', $lecturer->department_id);
                    })
                    ->get()
                    ->toArray();
            }
        }
        
        return [];
    }

    /**
     * Lấy danh sách lecturers cho user
     * 
     * @param mixed $user User hiện tại
     * @return array
     */
    public function getLecturersForUser($user): array
    {
        $userType = $this->getUserType($user);
        
        // Admin có thể xem tất cả lecturers
        if ($userType === 'admin') {
            return \Modules\Auth\app\Models\Lecturer::with('department')->get()->toArray();
        }
        
        // Lecturer có thể xem lecturers trong cùng department
        if ($userType === 'lecturer') {
            $lecturer = \Modules\Auth\app\Models\Lecturer::with('department')->find($user->id);
            if ($lecturer && $lecturer->department_id) {
                return \Modules\Auth\app\Models\Lecturer::with('department')
                    ->where('department_id', $lecturer->department_id)
                    ->get()
                    ->toArray();
            }
        }
        
        return [];
    }

    /**
     * Lấy danh sách tất cả students cho user
     * 
     * @param mixed $user User hiện tại
     * @return array
     */
    public function getAllStudentsForUser($user): array
    {
        $userType = $this->getUserType($user);
        
        // Admin có thể xem tất cả students
        if ($userType === 'admin') {
            return \Modules\Auth\app\Models\Student::with('classroom')->get()->toArray();
        }
        
        // Lecturer chỉ có thể xem students thuộc department của mình
        if ($userType === 'lecturer') {
            $lecturer = \Modules\Auth\app\Models\Lecturer::find($user->id);
            if ($lecturer && $lecturer->department_id) {
                return \Modules\Auth\app\Models\Student::whereHas('classroom', function($query) use ($lecturer) {
                    $query->where('department_id', $lecturer->department_id);
                })->with('classroom')->get()->toArray();
            }
        }
        
        return [];
    }

    /**
     * ✅ Thu thập tất cả cache keys bị ảnh hưởng bởi task
     * 
     * @param Task $task Task object với receivers đã load
     * @return array Array of cache keys
     */
    private function collectAffectedCacheKeys(Task $task): array
    {
        $keys = [
            // Task specific cache
            "task:{$task->id}",
            "task_details:{$task->id}",
            
            // Creator cache
            "user_tasks:{$task->creator_type}:{$task->creator_id}",
            "created_tasks:{$task->creator_type}:{$task->creator_id}",
            "user_stats:{$task->creator_type}:{$task->creator_id}",
            
            // Global caches
            "task_stats",
            "overview_stats"
        ];

        // Receiver specific caches
        foreach ($task->receivers as $receiver) {
            $keys[] = "user_tasks:{$receiver->receiver_type}:{$receiver->receiver_id}";
            $keys[] = "user_stats:{$receiver->receiver_type}:{$receiver->receiver_id}";
            
            // Special handling for class and all_students
            if ($receiver->receiver_type === 'class') {
                $keys[] = "class_tasks:{$receiver->receiver_id}";
            } elseif ($receiver->receiver_type === 'all_students') {
                $keys[] = "department_tasks:{$receiver->receiver_id}";
                $keys[] = "all_students_tasks";
            }
        }

        return array_unique($keys);
    }

    /**
     * ✅ Invalidate multiple caches efficiently
     * 
     * @param array $cacheKeys Array of cache keys to invalidate
     * @return bool Success status
     */
    private function invalidateMultipleCaches(array $cacheKeys): bool
    {
        if (empty($cacheKeys)) {
            return true;
        }

        try {
            // Use CacheService for bulk invalidation
            $cacheService = app(\Modules\Task\app\Services\Interfaces\CacheServiceInterface::class);
            $result = $cacheService->forgetMultiple($cacheKeys);
            
            Log::debug('Bulk cache invalidation', [
                'keys_count' => count($cacheKeys),
                'keys' => $cacheKeys,
                'success' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Bulk cache invalidation failed', [
                'keys_count' => count($cacheKeys),
                'error' => $e->getMessage()
            ]);
            
            // Fallback: Individual cache clearing
            return $this->fallbackCacheInvalidation($cacheKeys);
        }
    }

    /**
     * ✅ Fallback cache invalidation method
     * 
     * @param array $cacheKeys Array of cache keys
     * @return bool Success status
     */
    private function fallbackCacheInvalidation(array $cacheKeys): bool
    {
        $success = true;
        
        foreach ($cacheKeys as $key) {
            try {
                if (str_contains($key, 'task:')) {
                    $taskId = (int) str_replace('task:', '', $key);
                    $this->cachedTaskRepository->clearTaskCache($taskId);
                } elseif (str_contains($key, 'user_')) {
                    // Extract user info and clear user cache
                    $parts = explode(':', $key);
                    if (count($parts) >= 3) {
                        $userType = $parts[1];
                        $userId = (int) $parts[2];
                        $this->cachedUserRepository->clearUserCache($userId, $userType);
                    }
                } elseif (str_contains($key, 'stats')) {
                    $this->cachedReportRepository->clearAllReportCache();
                }
            } catch (\Exception $e) {
                Log::warning('Individual cache clear failed', [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * ✅ Validate permissions cho việc tạo task
     */
    private function validateCreateTaskPermissions(object $userContext, array $data): void
    {
        // Validate user context
        $this->permissionService->validateUserContext($userContext);
        
        // Check basic create permission
        if (!$this->permissionService->canCreateTasks($userContext)) {
            throw TaskException::unauthorized('tasks', 'create', [
                'user_id' => $userContext->id,
                'user_type' => $userContext->user_type
            ]);
        }
        
        // Validate receiver permissions
        $receivers = $data['receivers'] ?? [];
        $this->validateReceiverPermissions($userContext, $receivers);
    }

    /**
     * ✅ Validate permissions cho receivers
     */
    private function validateReceiverPermissions(object $userContext, array $receivers): void
    {
        if (empty($receivers)) {
            throw TaskException::validationFailed('receivers', 'At least one receiver is required');
        }

        foreach ($receivers as $receiver) {
            $receiverType = $receiver['receiver_type'] ?? '';
            $receiverId = $receiver['receiver_id'] ?? 0;
            
            // Validate receiver type
            $validTypes = ['student', 'lecturer', 'class', 'all_students', 'all_lecturers'];
            if (!in_array($receiverType, $validTypes)) {
                throw TaskException::validationFailed('receiver_type', 'Invalid receiver type', [
                    'received' => $receiverType,
                    'valid_types' => $validTypes
                ]);
            }
            
            // Security check: Lecturer không thể gán task cho lecturer khác (trừ admin)
            if ($receiverType === 'lecturer' && !$this->permissionService->isAdmin($userContext)) {
                throw TaskException::securityViolation('Lecturer cannot assign tasks to other lecturers', [
                    'user_id' => $userContext->id,
                    'receiver_type' => $receiverType,
                    'receiver_id' => $receiverId
                ]);
            }
            
            // Security check: All students chỉ admin mới được dùng
            if ($receiverType === 'all_students' && !$this->permissionService->isAdmin($userContext)) {
                throw TaskException::securityViolation('Only admin can assign tasks to all students', [
                    'user_id' => $userContext->id,
                    'receiver_type' => $receiverType
                ]);
            }
        }
    }

    /**
     * ✅ Validate task data với security context
     */
    private function validateTaskData(array $data, array $receivers, ?object $userContext): void
    {
        // Required fields validation
        $requiredFields = ['title', 'creator_id', 'creator_type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw TaskException::validationFailed($field, 'Field is required');
            }
        }
        
        // Security: Creator phải match với user context (nếu có)
        if ($userContext) {
            if ($data['creator_id'] != $userContext->id || $data['creator_type'] != $userContext->user_type) {
                throw TaskException::securityViolation('Creator mismatch with authenticated user', [
                    'expected_creator' => [$userContext->id, $userContext->user_type],
                    'provided_creator' => [$data['creator_id'], $data['creator_type']]
                ]);
            }
        }
        
        // Validate deadline không trong quá khứ
        if (!empty($data['deadline'])) {
            $deadline = \Carbon\Carbon::parse($data['deadline']);
            if ($deadline->isPast()) {
                throw TaskException::validationFailed('deadline', 'Deadline cannot be in the past', [
                    'provided_deadline' => $data['deadline'],
                    'current_time' => now()->toISOString()
                ]);
            }
        }
        
        // Validate status
        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        $status = $data['status'] ?? 'pending';
        if (!in_array($status, $validStatuses)) {
            throw TaskException::validationFailed('status', 'Invalid status', [
                'provided_status' => $status,
                'valid_statuses' => $validStatuses
            ]);
        }
        
        // Validate priority
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        $priority = $data['priority'] ?? 'medium';
        if (!in_array($priority, $validPriorities)) {
            throw TaskException::validationFailed('priority', 'Invalid priority', [
                'provided_priority' => $priority,
                'valid_priorities' => $validPriorities
            ]);
        }
        
        // Security: Title length validation để tránh abuse
        if (strlen($data['title']) > 255) {
            throw TaskException::securityViolation('Title too long', [
                'max_length' => 255,
                'provided_length' => strlen($data['title'])
            ]);
        }
        
        // Security: Description length validation
        if (!empty($data['description']) && strlen($data['description']) > 10000) {
            throw TaskException::securityViolation('Description too long', [
                'max_length' => 10000,
                'provided_length' => strlen($data['description'])
            ]);
        }
    }

    /**
     * ✅ Validate data cho update task
     */
    private function validateUpdateData(array $data, ?array $receivers, ?object $userContext, Task $originalTask): void
    {
        // Basic field validation
        if (isset($data['title']) && (empty($data['title']) || strlen($data['title']) > 255)) {
            throw TaskException::validationFailed('title', 'Title must be between 1 and 255 characters');
        }
        
        if (isset($data['description']) && strlen($data['description']) > 10000) {
            throw TaskException::securityViolation('Description too long', [
                'max_length' => 10000,
                'provided_length' => strlen($data['description'])
            ]);
        }
        
        // Validate deadline if being updated
        if (isset($data['deadline']) && !empty($data['deadline'])) {
            $deadline = \Carbon\Carbon::parse($data['deadline']);
            if ($deadline->isPast()) {
                throw TaskException::validationFailed('deadline', 'Deadline cannot be in the past');
            }
        }
        
        // Validate status if being updated
        if (isset($data['status'])) {
            $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
            if (!in_array($data['status'], $validStatuses)) {
                throw TaskException::validationFailed('status', 'Invalid status', [
                    'provided_status' => $data['status'],
                    'valid_statuses' => $validStatuses
                ]);
            }
        }
        
        // Validate priority if being updated
        if (isset($data['priority'])) {
            $validPriorities = ['low', 'medium', 'high', 'urgent'];
            if (!in_array($data['priority'], $validPriorities)) {
                throw TaskException::validationFailed('priority', 'Invalid priority', [
                    'provided_priority' => $data['priority'],
                    'valid_priorities' => $validPriorities
                ]);
            }
        }
        
        // Security: Không cho phép thay đổi creator (trừ admin)
        if ((isset($data['creator_id']) || isset($data['creator_type'])) && 
            $userContext && !$this->permissionService->isAdmin($userContext)) {
            throw TaskException::securityViolation('Only admin can change task creator', [
                'user_id' => $userContext->id,
                'original_creator' => [$originalTask->creator_id, $originalTask->creator_type]
            ]);
        }
        
        // Validate receivers if being updated
        if ($receivers !== null && $userContext) {
            $this->validateReceiverPermissions($userContext, $receivers);
        }
    }

    /**
     * ✅ Validate permission để view task
     */
    public function validateViewTaskPermission(object $userContext, int $taskId): void
    {
        if (!$this->permissionService->canViewTask($userContext, $taskId)) {
            throw TaskException::unauthorized('task', 'view', [
                'user_id' => $userContext->id,
                'task_id' => $taskId
            ]);
        }
    }

    /**
     * ✅ Validate permission để edit task
     */
    public function validateEditTaskPermission(object $userContext, int $taskId): void
    {
        if (!$this->permissionService->canEditTask($userContext, $taskId)) {
            throw TaskException::unauthorized('task', 'edit', [
                'user_id' => $userContext->id,
                'task_id' => $taskId
            ]);
        }
    }

    /**
     * ✅ Validate permission để delete task
     */
    public function validateDeleteTaskPermission(object $userContext, int $taskId): void
    {
        if (!$this->permissionService->canDeleteTask($userContext, $taskId)) {
            throw TaskException::unauthorized('task', 'delete', [
                'user_id' => $userContext->id,
                'task_id' => $taskId
            ]);
        }
    }

    /**
     * ✅ Clear permission cache khi task thay đổi
     */
    private function clearTaskPermissionCache(Task $task): void
    {
        try {
            // Clear cache cho creator
            $creatorContext = (object) [
                'id' => $task->creator_id,
                'user_type' => $task->creator_type
            ];
            $this->permissionService->clearPermissionCache($creatorContext, $task->id);
            
            // Clear cache cho receivers
            if ($task->receivers) {
                foreach ($task->receivers as $receiver) {
                    $receiverContext = (object) [
                        'id' => $receiver->receiver_id,
                        'user_type' => $receiver->receiver_type
                    ];
                    $this->permissionService->clearPermissionCache($receiverContext, $task->id);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear permission cache', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Track changes between original and updated data
     */
    private function trackChanges(array $original, array $updated): array
    {
        $changes = [];
        $trackedFields = ['title', 'description', 'deadline', 'priority', 'status'];

        foreach ($trackedFields as $field) {
            if (isset($original[$field]) && isset($updated[$field])) {
                $oldValue = $original[$field];
                $newValue = $updated[$field];

                // Handle datetime fields
                if ($field === 'deadline') {
                    $oldValue = $oldValue ? date('Y-m-d H:i:s', strtotime($oldValue)) : null;
                    $newValue = $newValue ? date('Y-m-d H:i:s', strtotime($newValue)) : null;
                }

                if ($oldValue !== $newValue) {
                    $changes[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }
        }

        return $changes;
    }
}

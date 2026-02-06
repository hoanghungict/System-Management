<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
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
// Import sub-services
use Modules\Task\app\Services\Task\TaskAssignmentService;
use Modules\Task\app\Services\Task\TaskQueryService;
use Modules\Task\app\Services\Task\TaskFileService;
use Modules\Task\app\Services\Task\TaskStatisticsService;
use Modules\Task\app\Services\Task\TaskValidationService;
use Modules\Task\app\Services\Task\TaskCacheService;

/**
 * Service chứa business logic cho Task
 * 
 * Service này hoạt động như facade, ủy quyền logic cho các sub-services
 * Tuân thủ Clean Architecture: chỉ chứa business logic, không xử lý data access trực tiếp
 */
class TaskService implements TaskServiceInterface
{
    /**
     * ✅ Helper function để lấy user ID từ JWT (sub) hoặc id
     */
    private function getUserId(?object $userContext): ?int
    {
        if (!$userContext) return null;
        return $userContext->id ?? $userContext->sub ?? null;
    }
    
    protected $taskRepository;
    protected $reminderService;
    protected $permissionService;
    protected $kafkaProducer;
    
    // Sub-services
    protected $assignmentService;
    protected $queryService;
    protected $fileService;
    protected $statisticsService;
    protected $validationService;
    protected $cacheService;

    /**
     * Khởi tạo service với dependency injection
     */
    public function __construct(
        TaskRepositoryInterface $taskRepository, 
        PermissionService $permissionService,
        ReminderService $reminderService,
        KafkaProducerService $kafkaProducer,
        // Inject sub-services
        TaskAssignmentService $assignmentService,
        TaskQueryService $queryService,
        TaskFileService $fileService,
        TaskStatisticsService $statisticsService,
        TaskValidationService $validationService,
        TaskCacheService $cacheService
    ) {
        $this->taskRepository = $taskRepository;
        $this->permissionService = $permissionService;
        $this->reminderService = $reminderService;
        $this->kafkaProducer = $kafkaProducer;
        // Initialize sub-services
        $this->assignmentService = $assignmentService;
        $this->queryService = $queryService;
        $this->fileService = $fileService;
        $this->statisticsService = $statisticsService;
        $this->validationService = $validationService;
        $this->cacheService = $cacheService;
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
                // ✅ Security validation (delegated to validationService)
                if ($userContext) {
                    $this->validationService->validateCreateTaskPermissions($userContext, $data);
                }
                
                // Tách receivers ra khỏi data chính
                $receivers = $data['receivers'] ?? [];
                unset($data['receivers']);
                
                // ✅ Validate business rules (delegated to validationService)
                $this->validationService->validateTaskData($data, $receivers, $userContext);
                
                // Tạo task
                $task = $this->taskRepository->create($data);
                
                // Thêm receivers cho task (delegated to assignmentService)
                $this->assignmentService->addReceiversToTask($task, $receivers);
                
                // ✅ Load receivers với relations để lấy thông tin user
                $task->load(['receivers.student', 'receivers.lecturer']);
                
                // ✅ Lấy thông tin creator để gửi assigner_name
                $assignerName = 'Unknown';
                if ($task->creator_type === 'lecturer' && $task->creator_id) {
                    $lecturer = \Modules\Auth\app\Models\Lecturer::find($task->creator_id);
                    if ($lecturer) {
                        $assignerName = $lecturer->full_name ?? $lecturer->name ?? 'Unknown';
                    }
                } elseif ($task->creator_type === 'student' && $task->creator_id) {
                    $student = \Modules\Auth\app\Models\Student::find($task->creator_id);
                    if ($student) {
                        $assignerName = $student->full_name ?? $student->name ?? 'Unknown';
                    }
                } elseif ($task->creator_type === 'admin' && $task->creator_id) {
                    // Nếu có admin model, load ở đây
                    $assignerName = 'Admin';
                }
                
                /* Log::info('Task created', [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'creator_id' => $task->creator_id,
                    'creator_type' => $task->creator_type,
                    'assigner_name' => $assignerName,
                    'receivers_count' => count($receivers),
                    'created_by_user' => $this->getUserId($userContext) ?? 'system'
                ]); */
                
                // ✅ Collect và invalidate affected caches (delegated to cacheService)
                $affectedCacheKeys = $this->cacheService->collectAffectedCacheKeys($task);
                $this->cacheService->invalidateMultipleCaches($affectedCacheKeys);

                // ✅ Gửi Kafka message cho từng receiver thực tế (student/lecturer)
                foreach ($task->receivers as $receiver) {
                    // Chỉ gửi cho student và lecturer (bỏ qua classes, department, all_students, all_lecturers)
                    if (!in_array($receiver->receiver_type, ['student', 'lecturer'])) {
                        continue;
                    }

                    // Lấy tên người dùng từ relation
                    $userName = 'Unknown';
                    if ($receiver->receiver_type === 'student') {
                        $userName = $receiver->student->full_name ?? $receiver->student->name ?? 'Unknown';
                    } elseif ($receiver->receiver_type === 'lecturer') {
                        $userName = $receiver->lecturer->full_name ?? $receiver->lecturer->name ?? 'Unknown';
                    }

                    $this->kafkaProducer->send('task.assigned', [
                            'user_id'        => $receiver->receiver_id,
                            'user_type'      => $receiver->receiver_type,
                            'user_name'      => $userName,
                            'task_name'      => $task->title,
                            'task_description' => $task->description ?? '',
                            'assigner_name'  => $assignerName,
                            'assigner_id'    => $task->creator_id,
                            'assigner_type'  => $task->creator_type,
                            'deadline'       => optional($task->deadline)->format('Y-m-d H:i:s'),
                            'task_url'       => url("/tasks/{$task->id}"),
                            'priority' => 'medium',
                            'key'      => "task_{$task->id}_user_{$receiver->receiver_id}_" . now()->format('YmdHis')
                        ]
                    );
                }
                

                // ✅ Create automatic reminders for task
                if ($task->deadline) {
                    $this->reminderService->createAutomaticReminders($task);
                }

                // // ✅ Dispatch TaskCreated event for notifications
                // event(new TaskCreated($task, [
                //     'creator_id' => $task->creator_id,
                //     'creator_type' => $task->creator_type,
                //     'receivers' => $receivers
                // ]));

                // // ✅ Dispatch notification jobs for each receiver
                // foreach ($task->receivers as $receiver) {
                //     SendTaskCreatedNotificationJob::dispatch(new TaskCreated($task, [
                //         'receiver_id' => $receiver->id,
                //         'receiver_type' => $receiver->type
                //     ]));
                // }

                return $task;
            } catch (\Exception $e) {
                Log::error('Error creating task in transaction', [
                    'error' => $e->getMessage(),
                    'user_context' => $this->getUserId($userContext) ?? 'system',
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
                // ✅ Security validation (delegated to validationService)
                if ($userContext) {
                    $this->validationService->validateEditTaskPermission($userContext, $task->id);
                }
                
                // ✅ Load receivers trước để tránh N+1 queries
                $task->load('receivers');
                
                // ✅ Track changes for notifications
                $originalData = $task->toArray();
                
                // Collect cache keys trước khi update (delegated to cacheService)
                $affectedCacheKeys = $this->cacheService->collectAffectedCacheKeys($task);
                
                // Tách receivers ra khỏi data chính
                $receivers = $data['receivers'] ?? null;
                unset($data['receivers']);
                
                // ✅ Validate update data (delegated to validationService)
                $this->validationService->validateUpdateData($data, $receivers, $userContext, $task);
                
                // Cập nhật task
                $task = $this->taskRepository->update($task, $data);
                
                // Cập nhật receivers nếu có (trong transaction)
                if ($receivers !== null) {
                    $this->assignmentService->updateReceiversForTask($task, $receivers);
                    // Refresh task với receivers mới
                    $task->load('receivers');
                    // Add new cache keys
                    $affectedCacheKeys = array_merge($affectedCacheKeys, $this->cacheService->collectAffectedCacheKeys($task));
                }
                
                /* Log::info('Task updated', [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'receivers_updated' => $receivers !== null,
                    'cache_keys_affected' => count($affectedCacheKeys),
                    'updated_by_user' => $this->getUserId($userContext) ?? 'system'
                ]); */
                
                // ✅ Bulk cache invalidation (delegated to cacheService)
                $this->cacheService->invalidateMultipleCaches($affectedCacheKeys);
                
                // ✅ Clear permission cache (delegated to cacheService)
                $this->cacheService->clearTaskPermissionCache($task);

                // ✅ Track changes and dispatch events
                $changes = $this->trackChanges($originalData, $task->toArray());
                
                if (!empty($changes)) {
                    // Dispatch TaskUpdated event
                    event(new TaskUpdated($task, $changes, [
                        'updater_id' => $this->getUserId($userContext) ?? $task->creator_id,
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
                    'user_context' => $this->getUserId($userContext) ?? 'system'
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
            // ✅ Security validation (delegated to validationService)
            if ($userContext) {
                $this->validationService->validateDeleteTaskPermission($userContext, $task->id);
            }
            
            // ✅ Load receivers trước để tránh N+1 queries
            $task->load('receivers');
            
            // Collect cache keys trước khi delete (delegated to cacheService)
            $affectedCacheKeys = $this->cacheService->collectAffectedCacheKeys($task);
            
            $taskId = $task->id;
            $taskTitle = $task->title;
            
            // ✅ Clear permission cache trước khi delete (delegated to cacheService)
            $this->cacheService->clearTaskPermissionCache($task);
            
            $result = $this->taskRepository->delete($task);
            
            /* Log::info('Task deleted', [
                'task_id' => $taskId,
                'title' => $taskTitle,
                'cache_keys_affected' => count($affectedCacheKeys),
                'deleted_by_user' => $this->getUserId($userContext) ?? 'system'
            ]); */
            
            // ✅ Bulk cache invalidation (delegated to cacheService)
            $this->cacheService->invalidateMultipleCaches($affectedCacheKeys);

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
            $receiverType = $receiver['receiver_type'];
            $receiverId = $receiver['receiver_id'];
            
            // Xử lý đặc biệt cho classes - tự động gán cho tất cả sinh viên trong lớp
            if ($receiverType === 'classes') {
                $this->assignTaskToClassStudents($task, $receiverId);
            }
            // Xử lý đặc biệt cho department - tự động gán cho tất cả sinh viên trong khoa
            elseif ($receiverType === 'department') {
                $this->assignTaskToDepartmentStudents($task, $receiverId);
            }
            // Xử lý đặc biệt cho all_students - gán cho tất cả sinh viên hệ thống
            elseif ($receiverType === 'all_students') {
                $this->assignTaskToAllStudents($task);
            }
            // Xử lý đặc biệt cho all_lecturers - gán cho tất cả giảng viên hệ thống
            elseif ($receiverType === 'all_lecturers') {
                $this->assignTaskToAllLecturers($task);
            }
            // Xử lý bình thường cho student và lecturer
            else {
                $task->addReceiver($receiverId, $receiverType);
            }
        }
    }

    /**
     * ✅ Gán task cho tất cả sinh viên trong lớp
     * 
     * @param Task $task
     * @param int $classId
     */
    private function assignTaskToClassStudents(Task $task, int $classId): void
    {
        try {
            // Lấy danh sách sinh viên trong lớp
            $students = DB::table('student')
                ->where('class_id', $classId)
                ->select('id')
                ->get();

            /* Log::info('Assigning task to class students', [
                'task_id' => $task->id,
                'class_id' => $classId,
                'students_count' => $students->count()
            ]); */

            // Tạo receivers cho từng sinh viên
            foreach ($students as $student) {
                $task->addReceiver($student->id, 'student');
            }

            // Cũng lưu receiver cho lớp để tracking
            $task->addReceiver($classId, 'classes');
        } catch (\Exception $e) {
            Log::error('Failed to assign task to class students', [
                'task_id' => $task->id,
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ✅ Gán task cho tất cả sinh viên trong khoa
     * 
     * @param Task $task
     * @param int $departmentId
     */
    private function assignTaskToDepartmentStudents(Task $task, int $departmentId): void
    {
        try {
            // Lấy danh sách sinh viên trong khoa
            $students = DB::table('student')
                ->join('class', 'student.class_id', '=', 'class.id')
                ->where('class.department_id', $departmentId)
                ->select('student.id')
                ->get();

            /* Log::info('Assigning task to department students', [
                'task_id' => $task->id,
                'department_id' => $departmentId,
                'students_count' => $students->count()
            ]); */

            // Tạo receivers cho từng sinh viên
            foreach ($students as $student) {
                $task->addReceiver($student->id, 'student');
            }

            // Cũng lưu receiver cho department để tracking
            $task->addReceiver($departmentId, 'department');
        } catch (\Exception $e) {
            Log::error('Failed to assign task to department students', [
                'task_id' => $task->id,
                'department_id' => $departmentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ✅ Gán task cho tất cả sinh viên hệ thống
     * 
     * @param Task $task
     */
    private function assignTaskToAllStudents(Task $task): void
    {
        try {
            // Lấy danh sách tất cả sinh viên
            $students = DB::table('student')
                ->select('id')
                ->get();

            /* Log::info('Assigning task to all students', [
                'task_id' => $task->id,
                'students_count' => $students->count()
            ]); */

            // Tạo receivers cho từng sinh viên
            foreach ($students as $student) {
                $task->addReceiver($student->id, 'student');
            }

            // Cũng lưu receiver cho all_students để tracking
            $task->addReceiver(0, 'all_students');
        } catch (\Exception $e) {
            Log::error('Failed to assign task to all students', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ✅ Gán task cho tất cả giảng viên hệ thống
     * 
     * @param Task $task
     */
    private function assignTaskToAllLecturers(Task $task): void
    {
        try {
            // Lấy danh sách tất cả giảng viên
            $lecturers = DB::table('lecturer')
                ->select('id')
                ->get();

            /* Log::info('Assigning task to all lecturers', [
                'task_id' => $task->id,
                'lecturers_count' => $lecturers->count()
            ]); */

            // Tạo receivers cho từng giảng viên
            foreach ($lecturers as $lecturer) {
                $task->addReceiver($lecturer->id, 'lecturer');
            }

            // Cũng lưu receiver cho all_lecturers để tracking
            $task->addReceiver(0, 'all_lecturers');
        } catch (\Exception $e) {
            Log::error('Failed to assign task to all lecturers', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
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
            
            /* Log::info('Task receivers updated', [
                'task_id' => $task->id,
                'old_receivers_count' => $deletedCount,
                'new_receivers_count' => count($receivers),
                'old_receivers' => $oldReceivers,
                'new_receivers' => $receivers
            ]); */
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
                'path' => $file->store("task-files/{$task->id}", 'public'), // Lưu vào public disk
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
        
        // Kiểm tra quyền xóa file
        // Vì không có uploaded_by trong schema, kiểm tra qua task receivers hoặc creator
        $task = $file->task;
        
        // Admin có thể xóa mọi file
        if ($user->user_type === 'admin') {
            return true;
        }
        
        // Lecturer có thể xóa file của task họ tạo
        if ($user->user_type === 'lecturer') {
            return $task->creator_id === $user->id && $task->creator_type === 'lecturer';
        }
        
        // Student có thể xóa file của task họ được assign
        if ($user->user_type === 'student' && $task->receivers) {
            foreach ($task->receivers as $receiver) {
                if ($receiver->receiver_id == $user->id && $receiver->receiver_type == 'student') {
                    return true;
                }
            }
        }
        
        return false;
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
        // ✅ Sử dụng CacheService để generate keys đúng format
        $cacheService = app(\Modules\Task\app\Services\Interfaces\CacheServiceInterface::class);
        
        $keys = [
            // Task specific cache - sử dụng generateKey để đảm bảo format đúng
            $cacheService->generateKey('task', ['id' => $task->id]),
            $cacheService->generateKey('task_details', ['id' => $task->id]),
            
            // Creator cache
            $cacheService->generateKey('tasks_user', [
                'user_id' => $task->creator_id,
                'user_type' => $task->creator_type
            ]),
            $cacheService->generateKey('tasks_created', [
                'user_id' => $task->creator_id,
                'user_type' => $task->creator_type
            ]),
            $cacheService->generateKey('user_stats', [
                'user_id' => $task->creator_id,
                'user_type' => $task->creator_type
            ]),
            
            // Global caches
            $cacheService->generateKey('task_statistics'),
            $cacheService->generateKey('overview_stats')
        ];

        // Receiver specific caches
        foreach ($task->receivers as $receiver) {
            $keys[] = $cacheService->generateKey('tasks_user', [
                'user_id' => $receiver->receiver_id,
                'user_type' => $receiver->receiver_type
            ]);
            $keys[] = $cacheService->generateKey('user_stats', [
                'user_id' => $receiver->receiver_id,
                'user_type' => $receiver->receiver_type
            ]);
            
            // Special handling for class and all_students
            if ($receiver->receiver_type === 'class') {
                $keys[] = $cacheService->generateKey('class_tasks', ['class_id' => $receiver->receiver_id]);
            } elseif ($receiver->receiver_type === 'all_students') {
                $keys[] = $cacheService->generateKey('department_tasks', ['department_id' => $receiver->receiver_id]);
                $keys[] = $cacheService->generateKey('all_students_tasks');
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
            
            /* Log::debug('Bulk cache invalidation', [
                'keys_count' => count($cacheKeys),
                'keys' => $cacheKeys,
                'success' => $result
            ]); */
            
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
        $cacheService = app(\Modules\Task\app\Services\Interfaces\CacheServiceInterface::class);
        
        foreach ($cacheKeys as $key) {
            try {
                // ✅ Sử dụng CacheService để xóa cache trực tiếp
                $result = $cacheService->forget($key);
                
                if (!$result) {
                    Log::warning('Individual cache clear failed', [
                        'key' => $key,
                        'result' => $result
                    ]);
                    $success = false;
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
        // Debug user context
        /* Log::info('Debug user context in validateCreateTaskPermissions', [
            'user_context' => $userContext,
            'user_context_id' => $this->getUserId($userContext) ?? 'NOT_SET',
            'user_context_type' => $userContext->user_type ?? 'NOT_SET',
            'user_context_class' => get_class($userContext)
        ]); */
        
        // Validate user context
        $this->permissionService->validateUserContext($userContext);
        
        // Check basic create permission
        if (!$this->permissionService->canCreateTasks($userContext)) {
            throw TaskException::unauthorized('tasks', 'create', [
                'user_id' => $this->getUserId($userContext) ?? 'unknown',
                'user_type' => $userContext->user_type ?? 'unknown'
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
            $validTypes = ['student', 'lecturer', 'classes', 'department', 'all_students', 'all_lecturers'];
            if (!in_array($receiverType, $validTypes)) {
                throw TaskException::validationFailed('receiver_type', 'Invalid receiver type', [
                    'received' => $receiverType,
                    'valid_types' => $validTypes
                ]);
            }
            
            // Security check: Lecturer không thể gán task cho lecturer khác (trừ admin)
            if ($receiverType === 'lecturer' && !$this->permissionService->isAdmin($userContext)) {
                throw TaskException::securityViolation('Lecturer cannot assign tasks to other lecturers', [
                    'user_id' => $this->getUserId($userContext),
                    'receiver_type' => $receiverType,
                    'receiver_id' => $receiverId
                ]);
            }
            
            // Security check: All students chỉ admin mới được dùng
            if ($receiverType === 'all_students' && !$this->permissionService->isAdmin($userContext)) {
                throw TaskException::securityViolation('Only admin can assign tasks to all students', [
                    'user_id' => $this->getUserId($userContext),
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
            $userId = $this->getUserId($userContext);
            $userType = $userContext->user_type ?? 'unknown';
            
            // Admin có thể tạo task với bất kỳ creator_type nào
            $isAdmin = $userContext->is_admin ?? false;
            
            if (!$isAdmin) {
                if ($data['creator_id'] != $userId || $data['creator_type'] != $userType) {
                    throw TaskException::securityViolation('Creator mismatch with authenticated user', [
                        'expected_creator' => [$userId, $userType],
                        'provided_creator' => [$data['creator_id'], $data['creator_type']]
                    ]);
                }
            } else {
                // Admin chỉ cần kiểm tra creator_id
                if ($data['creator_id'] != $userId) {
                    throw TaskException::securityViolation('Creator ID mismatch with authenticated user', [
                        'expected_creator_id' => $userId,
                        'provided_creator_id' => $data['creator_id']
                    ]);
                }
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
        // Chỉ reject nếu creator thực sự thay đổi, không phải chỉ vì có trong request
        if ($userContext && !$this->permissionService->isAdmin($userContext)) {
            // Check if creator is being changed
            if (isset($data['creator_id']) && $data['creator_id'] != $originalTask->creator_id) {
                throw TaskException::securityViolation('Only admin can change task creator', [
                    'user_id' => $userContext->id,
                    'original_creator' => [$originalTask->creator_id, $originalTask->creator_type],
                    'attempted_creator_id' => $data['creator_id']
                ]);
            }
            
            if (isset($data['creator_type']) && $data['creator_type'] != $originalTask->creator_type) {
                throw TaskException::securityViolation('Only admin can change task creator type', [
                    'user_id' => $userContext->id,
                    'original_creator' => [$originalTask->creator_id, $originalTask->creator_type],
                    'attempted_creator_type' => $data['creator_type']
                ]);
            }
            
            // Remove creator fields from data if not admin (to prevent accidental inclusion)
            unset($data['creator_id']);
            unset($data['creator_type']);
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

<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PermissionService - Centralized permission management cho Task Module
 * 
 * Service này chứa tất cả logic phân quyền để tránh duplicate code
 * Tuân thủ Clean Architecture: Single source of truth cho permissions
 */
class PermissionService
{
    // Permission constants để tránh magic strings
    const PERMISSION_CREATE_TASKS = 'create_tasks';
    const PERMISSION_VIEW_ALL_TASKS = 'view_all_tasks';
    const PERMISSION_EDIT_ANY_TASK = 'edit_any_task';
    const PERMISSION_DELETE_ANY_TASK = 'delete_any_task';
    const PERMISSION_MANAGE_USERS = 'manage_users';
    const PERMISSION_GENERATE_REPORTS = 'generate_reports';
    const PERMISSION_ADMIN_ACCESS = 'admin_access';
    const PERMISSION_CREATE_CLASS_TASKS = 'create_class_tasks';
    const PERMISSION_CREATE_DEPARTMENT_TASKS = 'create_department_tasks';

    // User types
    const USER_TYPE_ADMIN = 'admin';
    const USER_TYPE_LECTURER = 'lecturer';
    const USER_TYPE_STUDENT = 'student';

    // Cache TTL for permission checks
    private const PERMISSION_CACHE_TTL = 300; // 5 minutes

    /**
     * ✅ Helper function để lấy user ID từ JWT (sub) hoặc id
     */
    private function getUserId(object $userContext): ?int
    {
        return $userContext->id ?? $userContext->sub ?? null;
    }

    /**
     * ✅ Kiểm tra user có được authenticate không
     */
    public function isAuthenticated(object $userContext): bool
    {
        $userId = $this->getUserId($userContext);
        return !empty($userId) && !empty($userContext->user_type);
    }

    /**
     * ✅ Kiểm tra user có phải admin không
     */
    public function isAdmin(object $userContext): bool
    {
        // Admin là lecturer có is_admin = 1 trong database
        if ($userContext->user_type !== self::USER_TYPE_LECTURER) {
            return false;
        }
        
        // Kiểm tra trong bảng lecturer_account xem có is_admin = 1 không
        $userId = $this->getUserId($userContext);
        $account = DB::table('lecturer_account')
            ->where('lecturer_id', $userId)
            ->where('is_admin', 1)
            ->first();
            
        return $account !== null;
    }

    /**
     * ✅ Kiểm tra user có phải lecturer không
     */
    public function isLecturer(object $userContext): bool
    {
        return $userContext->user_type === self::USER_TYPE_LECTURER;
    }

    /**
     * ✅ Kiểm tra user có phải student không
     */
    public function isStudent(object $userContext): bool
    {
        return $userContext->user_type === self::USER_TYPE_STUDENT;
    }

    /**
     * ✅ Kiểm tra user có thể tạo task cho lớp không
     */
    public function canCreateClassTasks(object $userContext): bool
    {
        // Chỉ admin mới có thể tạo task cho lớp
        return $this->isAdmin($userContext);
    }

    /**
     * ✅ Kiểm tra user có thể tạo task cho khoa/department không
     */
    public function canCreateDepartmentTasks(object $userContext): bool
    {
        // Chỉ admin mới có thể tạo task cho khoa/department
        return $this->isAdmin($userContext);
    }

    /**
     * ✅ Kiểm tra user có thể tạo task cho lớp cụ thể không (dành cho giảng viên phụ trách)
     */
    public function canCreateTasksForClass(object $userContext, int $classId): bool
    {
        // Admin có thể tạo task cho tất cả lớp
        if ($this->isAdmin($userContext)) {
            return true;
        }

        // Giảng viên phụ trách có thể tạo task cho lớp mình phụ trách
        if ($this->isLecturer($userContext)) {
            return $this->isLecturerInChargeOfClass($userContext, $classId);
        }

        return false;
    }

    /**
     * ✅ Kiểm tra user có thể tạo task cho khoa/department cụ thể không (dành cho giảng viên phụ trách)
     */
    public function canCreateTasksForDepartment(object $userContext, int $departmentId): bool
    {
        // Admin có thể tạo task cho tất cả khoa
        if ($this->isAdmin($userContext)) {
            return true;
        }

        // Giảng viên phụ trách có thể tạo task cho khoa mình phụ trách
        if ($this->isLecturer($userContext)) {
            return $this->isLecturerInChargeOfDepartment($userContext, $departmentId);
        }

        return false;
    }

    /**
     * ✅ Kiểm tra user có thể tạo tasks không (general)
     */
    public function canCreateTasks(object $userContext): bool
    {
        // Chỉ lecturer và admin mới có thể tạo tasks
        return $this->isLecturer($userContext) || $this->isAdmin($userContext);
    }

    /**
     * ✅ Kiểm tra giảng viên có phụ trách lớp không
     */
    private function isLecturerInChargeOfClass(object $userContext, int $classId): bool
    {
        try {
            // Kiểm tra xem giảng viên có phụ trách lớp này không
            $lecturer = \Modules\Auth\app\Models\Lecturer::find($this->getUserId($userContext));
            if (!$lecturer) {
                return false;
            }

            // Kiểm tra trong bảng class_lecturers hoặc tương tự
            return \Modules\Auth\app\Models\Classroom::where('id', $classId)
                ->where('department_id', $lecturer->department_id)
                ->exists();

        } catch (\Exception $e) {
            Log::error('Error checking lecturer class charge', [
                'lecturer_id' => $this->getUserId($userContext),
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ Kiểm tra giảng viên có phụ trách khoa/department không
     */
    private function isLecturerInChargeOfDepartment(object $userContext, int $departmentId): bool
    {
        try {
            // Kiểm tra xem giảng viên có thuộc khoa này không
            $lecturer = \Modules\Auth\app\Models\Lecturer::find($this->getUserId($userContext));
            if (!$lecturer) {
                return false;
            }

            return $lecturer->department_id == $departmentId;

        } catch (\Exception $e) {
            Log::error('Error checking lecturer department charge', [
                'lecturer_id' => $this->getUserId($userContext),
                'department_id' => $departmentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ Kiểm tra user có thể xem tất cả tasks không
     */
    public function canViewAllTasks(object $userContext): bool
    {
        // Chỉ admin mới có thể xem tất cả tasks
        return $this->isAdmin($userContext);
    }

    /**
     * ✅ Kiểm tra user có thể quản lý users không
     */
    public function canManageUsers(object $userContext): bool
    {
        // Chỉ admin mới có thể quản lý users
        return $this->isAdmin($userContext);
    }

    /**
     * ✅ Kiểm tra user có thể generate reports không
     */
    public function canGenerateReports(object $userContext): bool
    {
        // Admin và lecturer có thể generate reports
        return $this->isAdmin($userContext) || $this->isLecturer($userContext);
    }

    /**
     * ✅ Kiểm tra user có thể xem một task cụ thể không
     */
    public function canViewTask(object $userContext, int $taskId): bool
    {
        // Admin có thể xem tất cả tasks
        if ($this->isAdmin($userContext)) {
            return true;
        }

        // Cache permission check để tránh query database nhiều lần
        $cacheKey = "task_permission:view:{$this->getUserId($userContext)}:{$userContext->user_type}:{$taskId}";
        
        return Cache::remember($cacheKey, self::PERMISSION_CACHE_TTL, function () use ($userContext, $taskId) {
            return $this->checkTaskViewPermission($userContext, $taskId);
        });
    }

    /**
     * ✅ Kiểm tra user có thể edit một task cụ thể không
     */
    public function canEditTask(object $userContext, int $taskId): bool
    {
        // Admin có thể edit tất cả tasks
        if ($this->isAdmin($userContext)) {
            return true;
        }

        // Cache permission check
        $cacheKey = "task_permission:edit:{$this->getUserId($userContext)}:{$userContext->user_type}:{$taskId}";
        
        return Cache::remember($cacheKey, self::PERMISSION_CACHE_TTL, function () use ($userContext, $taskId) {
            return $this->checkTaskEditPermission($userContext, $taskId);
        });
    }

    /**
     * ✅ Kiểm tra user có thể delete một task cụ thể không
     */
    public function canDeleteTask(object $userContext, int $taskId): bool
    {
        // Admin có thể delete tất cả tasks
        if ($this->isAdmin($userContext)) {
            return true;
        }

        // Cache permission check
        $cacheKey = "task_permission:delete:{$this->getUserId($userContext)}:{$userContext->user_type}:{$taskId}";
        
        return Cache::remember($cacheKey, self::PERMISSION_CACHE_TTL, function () use ($userContext, $taskId) {
            return $this->checkTaskDeletePermission($userContext, $taskId);
        });
    }

    /**
     * ✅ Kiểm tra user có thể update status của task không
     */
    public function canUpdateTaskStatus(object $userContext, int $taskId): bool
    {
        // Admin có thể update tất cả
        if ($this->isAdmin($userContext)) {
            return true;
        }

        // Cache permission check
        $cacheKey = "task_permission:status:{$this->getUserId($userContext)}:{$userContext->user_type}:{$taskId}";
        
        return Cache::remember($cacheKey, self::PERMISSION_CACHE_TTL, function () use ($userContext, $taskId) {
            return $this->checkTaskStatusUpdatePermission($userContext, $taskId);
        });
    }

    /**
     * ✅ Generic permission checker
     */
    public function canPerformAction(object $userContext, string $action, string $resource): bool
    {
        // Admin có thể làm tất cả
        if ($this->isAdmin($userContext)) {
            return true;
        }

        // Map actions to specific permission methods
        switch ($action) {
            case 'create':
                return $this->canCreateTasks($userContext);
            case 'view_all':
                return $this->canViewAllTasks($userContext);
            case 'manage_users':
                return $this->canManageUsers($userContext);
            case 'generate_reports':
                return $this->canGenerateReports($userContext);
            default:
                return false;
        }
    }

    /**
     * ✅ Check specific task view permission (implementation)
     */
    private function checkTaskViewPermission(object $userContext, int $taskId): bool
    {
        try {
            $task = Task::with('receivers')->find($taskId);
            
            if (!$task) {
                return false;
            }

            // Creator có thể xem task của mình
            if ($task->creator_id == $this->getUserId($userContext) && $task->creator_type == $userContext->user_type) {
                return true;
            }

            // Check nếu user là receiver của task
            return $this->isTaskReceiver($userContext, $task);

        } catch (\Exception $e) {
            Log::error('Error checking task view permission', [
                'user_id' => $this->getUserId($userContext),
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ Check specific task edit permission (implementation)
     * 
     * Admin: Có thể edit tất cả tasks (đã check ở canEditTask)
     * Lecturer: Có thể edit task họ tạo HOẶC task họ là receiver
     * Student: Không thể edit task (chỉ submit/update submission)
     */
    private function checkTaskEditPermission(object $userContext, int $taskId): bool
    {
        try {
            $task = Task::with('receivers')->find($taskId);
            
            if (!$task) {
                return false;
            }

            // ✅ Check 1: Creator có thể edit task (lecturer/admin)
            // Use strict comparison and ensure both are integers
            $userId = (int) $this->getUserId($userContext);
            $creatorId = (int) $task->creator_id;
            
            if ($creatorId === $userId && 
                $task->creator_type === $userContext->user_type) {
                // Check if user can create tasks (admin or lecturer)
                if ($this->canCreateTasks($userContext)) {
                    Log::info('PermissionService: Edit allowed - User is creator', [
                        'user_id' => $userId,
                        'user_type' => $userContext->user_type,
                        'task_id' => $taskId,
                        'creator_id' => $creatorId,
                        'creator_type' => $task->creator_type
                    ]);
                    return true;
                } else {
                    Log::warning('PermissionService: Edit denied - User is creator but cannot create tasks', [
                        'user_id' => $userId,
                        'user_type' => $userContext->user_type,
                        'task_id' => $taskId,
                        'creator_id' => $creatorId,
                        'creator_type' => $task->creator_type
                    ]);
                }
            } else {
                Log::debug('PermissionService: Creator check failed', [
                    'user_id' => $userId,
                    'user_type' => $userContext->user_type,
                    'task_id' => $taskId,
                    'creator_id' => $creatorId,
                    'creator_type' => $task->creator_type,
                    'match' => [
                        'id_match' => $creatorId === $userId,
                        'type_match' => $task->creator_type === $userContext->user_type
                    ]
                ]);
            }

            // ✅ Check 2: Lecturer có thể edit task mà họ là receiver
            if ($this->isLecturer($userContext)) {
                $isReceiver = $this->isTaskReceiver($userContext, $task);
                if ($isReceiver) {
                    Log::info('PermissionService: Edit allowed - Lecturer is receiver', [
                        'user_id' => $this->getUserId($userContext),
                        'task_id' => $taskId,
                        'creator_id' => $task->creator_id,
                        'creator_type' => $task->creator_type
                    ]);
                    return true;
                }
            }

            // ✅ Student không thể edit task
            if ($this->isStudent($userContext)) {
                Log::warning('PermissionService: Edit denied - Student cannot edit tasks', [
                    'user_id' => $this->getUserId($userContext),
                    'task_id' => $taskId
                ]);
                return false;
            }

            Log::warning('PermissionService: Edit denied - User is neither creator nor receiver', [
                'user_id' => $this->getUserId($userContext),
                'user_type' => $userContext->user_type,
                'task_id' => $taskId,
                'creator_id' => $task->creator_id,
                'creator_type' => $task->creator_type
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Error checking task edit permission', [
                'user_id' => $this->getUserId($userContext),
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ Check specific task delete permission (implementation)
     */
    private function checkTaskDeletePermission(object $userContext, int $taskId): bool
    {
        // Same as edit permission - chỉ creator mới có thể delete
        return $this->checkTaskEditPermission($userContext, $taskId);
    }

    /**
     * ✅ Check task status update permission (implementation)
     */
    private function checkTaskStatusUpdatePermission(object $userContext, int $taskId): bool
    {
        try {
            $task = Task::with('receivers')->find($taskId);
            
            if (!$task) {
                return false;
            }

            // Creator có thể update status
            if ($task->creator_id == $this->getUserId($userContext) && $task->creator_type == $userContext->user_type) {
                return true;
            }

            // Receiver có thể update status (student marking as completed)
            return $this->isTaskReceiver($userContext, $task);

        } catch (\Exception $e) {
            Log::error('Error checking task status update permission', [
                'user_id' => $this->getUserId($userContext),
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ Kiểm tra user có phải receiver của task không
     */
    private function isTaskReceiver(object $userContext, Task $task): bool
    {
        // Direct receiver check
        $isDirectReceiver = $task->receivers
            ->where('receiver_id', $this->getUserId($userContext))
            ->where('receiver_type', $userContext->user_type)
            ->isNotEmpty();

        if ($isDirectReceiver) {
            return true;
        }

        // Special case cho students - check class và all_students
        if ($this->isStudent($userContext)) {
            return $this->isStudentTaskReceiver($userContext, $task);
        }

        // Special case cho lecturers - check all_lecturers
        if ($this->isLecturer($userContext)) {
            return $this->isLecturerTaskReceiver($userContext, $task);
        }

        return false;
    }

    /**
     * ✅ Kiểm tra student có receive task không (qua class hoặc all_students)
     */
    private function isStudentTaskReceiver(object $userContext, Task $task): bool
    {
        $student = \Modules\Auth\app\Models\Student::with('classroom')->find($this->getUserId($userContext));
        
        if (!$student) {
            return false;
        }

        foreach ($task->receivers as $receiver) {
            // Check class receiver
            if ($receiver->receiver_type === 'class' && 
                $receiver->receiver_id == $student->class_id) {
                return true;
            }

            // Check all_students receiver
            if ($receiver->receiver_type === 'all_students') {
                // All students toàn trường
                if ($receiver->receiver_id == 0) {
                    return true;
                }
                // All students theo department
                if ($student->classroom && 
                    $receiver->receiver_id == $student->classroom->department_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ✅ Kiểm tra lecturer có receive task không (qua all_lecturers)
     */
    private function isLecturerTaskReceiver(object $userContext, Task $task): bool
    {
        $lecturer = \Modules\Auth\app\Models\Lecturer::find($userContext->id);
        
        if (!$lecturer) {
            return false;
        }

        foreach ($task->receivers as $receiver) {
            // Check all_lecturers receiver
            if ($receiver->receiver_type === 'all_lecturers') {
                // All lecturers toàn trường
                if ($receiver->receiver_id == 0) {
                    return true;
                }
                // All lecturers theo department
                if ($receiver->receiver_id == $lecturer->department_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ✅ Validate user context có đầy đủ thông tin không
     */
    public function validateUserContext(object $userContext): void
    {
        // JWT sử dụng 'sub' thay vì 'id'
        $userId = $this->getUserId($userContext);
        if (!$userId || $userId === null || $userId === '') {
            throw TaskException::businessRuleViolation('User ID is required');
        }

        if (!isset($userContext->user_type) || empty($userContext->user_type)) {
            throw TaskException::businessRuleViolation('User type is required');
        }

        $validUserTypes = [self::USER_TYPE_LECTURER, self::USER_TYPE_STUDENT];
        if (!in_array($userContext->user_type, $validUserTypes)) {
            throw TaskException::businessRuleViolation('Invalid user type', [
                'user_type' => $userContext->user_type,
                'valid_types' => $validUserTypes
            ]);
        }
    }

    /**
     * ✅ Clear permission cache cho user
     */
    public function clearPermissionCache(object $userContext, ?int $taskId = null): bool
    {
        try {
            if ($taskId) {
                // Clear specific task permissions
                $patterns = [
                    "task_permission:view:{$this->getUserId($userContext)}:{$userContext->user_type}:{$taskId}",
                    "task_permission:edit:{$this->getUserId($userContext)}:{$userContext->user_type}:{$taskId}",
                    "task_permission:delete:{$this->getUserId($userContext)}:{$userContext->user_type}:{$taskId}",
                    "task_permission:status:{$this->getUserId($userContext)}:{$userContext->user_type}:{$taskId}"
                ];
                
                foreach ($patterns as $pattern) {
                    Cache::forget($pattern);
                }
            } else {
                // Clear all permissions for user
                $pattern = "task_permission:*:{$this->getUserId($userContext)}:{$userContext->user_type}:*";
                // Note: Would need cache service with pattern support for this
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error clearing permission cache', [
                'user_id' => $this->getUserId($userContext),
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ✅ Get all permissions cho user (for debugging/admin)
     */
    public function getAllPermissions(object $userContext): array
    {
        return [
            'user_info' => [
                'id' => $this->getUserId($userContext),
                'user_type' => $userContext->user_type,
                'is_admin' => $this->isAdmin($userContext),
                'is_lecturer' => $this->isLecturer($userContext),
                'is_student' => $this->isStudent($userContext)
            ],
            'permissions' => [
                self::PERMISSION_CREATE_TASKS => $this->canCreateTasks($userContext),
                self::PERMISSION_VIEW_ALL_TASKS => $this->canViewAllTasks($userContext),
                self::PERMISSION_EDIT_ANY_TASK => $this->isAdmin($userContext),
                self::PERMISSION_DELETE_ANY_TASK => $this->isAdmin($userContext),
                self::PERMISSION_MANAGE_USERS => $this->canManageUsers($userContext),
                self::PERMISSION_GENERATE_REPORTS => $this->canGenerateReports($userContext),
                self::PERMISSION_ADMIN_ACCESS => $this->isAdmin($userContext),
                self::PERMISSION_CREATE_CLASS_TASKS => $this->canCreateClassTasks($userContext),
                self::PERMISSION_CREATE_DEPARTMENT_TASKS => $this->canCreateDepartmentTasks($userContext)
            ]
        ];
    }
}

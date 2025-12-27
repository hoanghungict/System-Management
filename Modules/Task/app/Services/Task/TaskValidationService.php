<?php

namespace Modules\Task\app\Services\Task;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Support\Facades\Log;

/**
 * Service xử lý validation cho task
 */
class TaskValidationService
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Validate permissions cho việc tạo task
     */
    public function validateCreateTaskPermissions(object $userContext, array $data): void
    {
        $userId = $userContext->id ?? $userContext->sub ?? null;
        $userRole = $userContext->role ?? $userContext->user_type ?? null;

        if (!$this->permissionService->canCreateTask($userContext)) {
            Log::warning('Permission denied for creating task', [
                'user_id' => $userId,
                'user_role' => $userRole
            ]);
            throw new \Exception('Bạn không có quyền tạo task');
        }
    }

    /**
     * Validate permission để view task
     */
    public function validateViewTaskPermission(object $userContext, int $taskId): void
    {
        if (!$this->permissionService->canViewTask($userContext, $taskId)) {
            throw new \Exception('Bạn không có quyền xem task này');
        }
    }

    /**
     * Validate permission để edit task
     */
    public function validateEditTaskPermission(object $userContext, int $taskId): void
    {
        if (!$this->permissionService->canEditTask($userContext, $taskId)) {
            throw new \Exception('Bạn không có quyền chỉnh sửa task này');
        }
    }

    /**
     * Validate permission để delete task
     */
    public function validateDeleteTaskPermission(object $userContext, int $taskId): void
    {
        if (!$this->permissionService->canDeleteTask($userContext, $taskId)) {
            throw new \Exception('Bạn không có quyền xóa task này');
        }
    }

    /**
     * Validate task data
     */
    public function validateTaskData(array $data, array $receivers, ?object $userContext): void
    {
        // Validate required fields
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Tiêu đề task là bắt buộc');
        }

        if (strlen($data['title']) > 255) {
            throw new \InvalidArgumentException('Tiêu đề task không được vượt quá 255 ký tự');
        }

        // Validate deadline
        if (isset($data['deadline'])) {
            $deadline = \Carbon\Carbon::parse($data['deadline']);
            if ($deadline->isPast()) {
                throw new \InvalidArgumentException('Deadline không thể là thời gian trong quá khứ');
            }
        }

        // Validate priority
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (isset($data['priority']) && !in_array($data['priority'], $validPriorities)) {
            throw new \InvalidArgumentException('Độ ưu tiên không hợp lệ');
        }

        // Validate status
        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        if (isset($data['status']) && !in_array($data['status'], $validStatuses)) {
            throw new \InvalidArgumentException('Trạng thái không hợp lệ');
        }
    }

    /**
     * Validate update data
     */
    public function validateUpdateData(array $data, ?array $receivers, ?object $userContext, Task $originalTask): void
    {
        if (isset($data['title']) && strlen($data['title']) > 255) {
            throw new \InvalidArgumentException('Tiêu đề task không được vượt quá 255 ký tự');
        }

        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (isset($data['priority']) && !in_array($data['priority'], $validPriorities)) {
            throw new \InvalidArgumentException('Độ ưu tiên không hợp lệ');
        }

        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        if (isset($data['status']) && !in_array($data['status'], $validStatuses)) {
            throw new \InvalidArgumentException('Trạng thái không hợp lệ');
        }
    }

    /**
     * Validate receiver permissions
     */
    public function validateReceiverPermissions(object $userContext, array $receivers): void
    {
        // Validation logic for receivers
        foreach ($receivers as $receiver) {
            if (!isset($receiver['receiver_type']) || !isset($receiver['receiver_id'])) {
                throw new \InvalidArgumentException('Receiver phải có receiver_type và receiver_id');
            }
        }
    }
}

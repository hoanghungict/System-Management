<?php

declare(strict_types=1);

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Services\CacheInvalidationService;
use Modules\Task\app\Admin\UseCases\TaskCacheEvent;
use Modules\Task\app\Services\TaskStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Update Task Use Case
 * 
 * Handles task updates with proper permission checking and validation
 * Follows Clean Architecture principles
 */
class UpdateTaskUseCase
{
    public function __construct(
        private PermissionService $permissionService,
        private CacheInvalidationService $cacheInvalidationService,
        private TaskStatusService $taskStatusService
    ) {}

    /**
     * Execute the update task use case
     * 
     * @param int $taskId
     * @param array $data
     * @param int $userId
     * @param string $userType
     * @return array
     * @throws \Exception
     */
    public function execute(int $taskId, array $data, int $userId, string $userType): array
    {
        try {
            // Check admin permissions
            $userContext = (object) [
                'id' => $userId,
                'user_type' => $userType
            ];

            if (!$this->permissionService->isAdmin($userContext)) {
                throw new \Exception('Unauthorized: Admin access required');
            }

            // Find the task
            $task = Task::find($taskId);
            if (!$task) {
                throw new \Exception('Task not found');
            }

            // Check if trying to complete task - validate dependencies first
            if (isset($data['status']) && $data['status'] === 'completed') {
                if (!$this->taskStatusService->canTaskBeCompleted($taskId)) {
                    throw new \Exception('Cannot complete task: Not all dependencies are completed');
                }
            }

            // Validate input data
            $validator = Validator::make($data, [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'status' => 'sometimes|in:pending,in_progress,completed,overdue,cancelled',
                'priority' => 'sometimes|in:low,medium,high',
                'due_date' => 'sometimes|date|after:now',
                'deadline' => 'sometimes|date|after:now',
                'receivers' => 'sometimes|array|min:1',
                'receivers.*.receiver_id' => 'required_with:receivers|integer|min:0',
                'receivers.*.receiver_type' => 'required_with:receivers|in:student,lecturer,class,all_students,all_lecturers',
                'include_new_students' => 'sometimes|boolean',
                'include_new_lecturers' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
            }

            DB::beginTransaction();

            try {
                // Update task fields
                $updateData = $validator->validated();

                // Handle deadline/due_date alias
                if (isset($updateData['due_date'])) {
                    $updateData['deadline'] = $updateData['due_date'];
                } elseif (isset($updateData['deadline'])) {
                    $updateData['due_date'] = $updateData['deadline'];
                }

                $task->update($updateData);

                // Handle receivers update if provided
                if (isset($data['receivers'])) {
                    // Delete existing receivers
                    $task->receivers()->delete();

                    // Create new receivers
                    foreach ($data['receivers'] as $receiver) {
                        $task->receivers()->create([
                            'receiver_id' => $receiver['receiver_id'],
                            'receiver_type' => $receiver['receiver_type']
                        ]);
                    }
                }

                // Invalidate related caches
                $this->cacheInvalidationService->invalidateTaskCache($taskId);
                $this->cacheInvalidationService->invalidateUserCache($userId);

                // Dispatch cache invalidation event
                event(new TaskCacheEvent('task_updated', [
                    'task_id' => $taskId,
                    'user_id' => $userId,
                    'user_type' => $userType,
                    'updated_fields' => array_keys($updateData)
                ]));

                DB::commit();

                Log::info('Task updated successfully', [
                    'task_id' => $taskId,
                    'user_id' => $userId,
                    'user_type' => $userType,
                    'updated_fields' => array_keys($updateData)
                ]);

                // Check if task status was updated to completed
                if (isset($updateData['status']) && $updateData['status'] === 'completed') {
                    // Trigger status check for dependent tasks
                    $this->taskStatusService->processPendingTasks();
                }

                return [
                    'success' => true,
                    'message' => 'Task updated successfully',
                    'data' => new \Modules\Task\app\Transformers\TaskResource($task->fresh()->load('receivers'))
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Failed to update task', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}

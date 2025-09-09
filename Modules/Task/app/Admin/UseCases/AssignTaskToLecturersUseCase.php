<?php

namespace Modules\Task\app\Admin\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Auth\app\Models\Lecturer;
use Modules\Task\app\Models\TaskReceiver;
use Modules\Task\app\Services\PermissionService;

/**
 * Use Case: Assign Task to Lecturers (Admin only)
 * 
 * This use case handles the business logic for assigning a task to multiple lecturers
 * following Clean Architecture principles
 */
class AssignTaskToLecturersUseCase
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Execute assign task to lecturers
     * 
     * @param int $taskId
     * @param array $lecturerIds
     * @param int $userId
     * @param string $userType
     * @return array
     * @throws \Exception
     */
    public function execute(int $taskId, array $lecturerIds, int $userId, string $userType): array
    {
        // Validate input parameters
        if (empty($taskId)) {
            throw new \Exception('Task ID is required');
        }

        if (empty($lecturerIds) || !is_array($lecturerIds)) {
            throw new \Exception('Lecturer IDs must be a non-empty array');
        }

        // Check admin permission
        $userContext = (object) [
            'id' => $userId,
            'user_type' => $userType
        ];
        
        if (!$this->permissionService->isAdmin($userContext)) {
            throw new \Exception('Unauthorized: Admin access required');
        }

        // Find task
        $task = Task::find($taskId);
        
        if (!$task) {
            throw new \Exception('Task not found');
        }

        // Validate lecturer IDs exist
        $lecturers = Lecturer::whereIn('id', $lecturerIds)->get();
        
        if ($lecturers->count() !== count($lecturerIds)) {
            throw new \Exception('Some lecturer IDs are invalid');
        }

        // Create task assignments
        $assignments = [];
        foreach ($lecturers as $lecturer) {
            // Check if assignment already exists
            $existingAssignment = TaskReceiver::where('task_id', $task->id)
                ->where('receiver_id', $lecturer->id)
                ->where('receiver_type', 'lecturer')
                ->first();

            if (!$existingAssignment) {
                $assignment = TaskReceiver::create([
                    'task_id' => $task->id,
                    'receiver_id' => $lecturer->id,
                    'receiver_type' => 'lecturer'
                ]);
                $assignments[] = $assignment;
            }
        }

        return [
            'success' => true,
            'message' => 'Task assigned to lecturers successfully',
            'task_id' => $task->id,
            'assigned_lecturers' => $lecturers->pluck('id')->toArray(),
            'assignments_count' => count($assignments),
            'new_assignments' => count($assignments)
        ];
    }
}

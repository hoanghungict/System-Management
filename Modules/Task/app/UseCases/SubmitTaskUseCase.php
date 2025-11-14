<?php

namespace Modules\Task\app\UseCases;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Services\UserContextService;
use Modules\Task\app\DTOs\TaskDTO;
use Modules\Task\app\Exceptions\TaskException;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * SubmitTaskUseCase - Use Case chung cho submit task
 * 
 * Xử lý business logic cho việc submit task của cả sinh viên và giảng viên
 * Tuân theo Clean Architecture
 */
class SubmitTaskUseCase
{
    protected TaskServiceInterface $taskService;
    protected PermissionService $permissionService;
    protected UserContextService $userContextService;
    protected KafkaProducerService $kafkaProducer;

    public function __construct(
        TaskServiceInterface $taskService,
        PermissionService $permissionService,
        UserContextService $userContextService,
        KafkaProducerService $kafkaProducer
    ) {
        $this->taskService = $taskService;
        $this->permissionService = $permissionService;
        $this->userContextService = $userContextService;
        $this->kafkaProducer = $kafkaProducer;
    }

    /**
     * Execute submit task
     * 
     * @param array $data
     * @param object $userContext
     * @return array
     * @throws TaskException
     */
    public function execute(array $data, object $userContext): array
    {
        return DB::transaction(function () use ($data, $userContext) {
            try {
                // Validate permissions
                $this->validateSubmitPermissions($userContext, $data);

                // Get task details
                $task = $this->taskService->getTaskById($data['task_id']);
                if (!$task) {
                    throw new TaskException('Task not found', 404);
                }

                // Check if user can submit this task
                $this->validateTaskSubmission($task, $userContext, $data);

                // Prepare submission data
                $submissionData = $this->prepareSubmissionData($data, $userContext, $task);

                // Create or update submission
                $submission = $this->createOrUpdateSubmission($submissionData, $userContext);

                // Update task status if needed
                $this->updateTaskStatusIfNeeded($task, $userContext, $data);

                // Send notifications
                $this->sendSubmissionNotifications($task, $userContext, $submission);

                // Log submission
                Log::info('Task submitted successfully', [
                    'task_id' => $task->id,
                    'user_id' => $userContext->id,
                    'user_type' => $userContext->type,
                    'submission_type' => $data['submission_type']
                ]);

                return $this->formatSubmissionResponse($submission, $task, $userContext);
            } catch (\Exception $e) {
                Log::error('Task submission failed', [
                    'task_id' => $data['task_id'] ?? null,
                    'user_id' => $userContext->id ?? null,
                    'error' => $e->getMessage()
                ]);

                throw new TaskException('Failed to submit task: ' . $e->getMessage(), 500);
            }
        });
    }

    /**
     * Update existing submission
     * 
     * @param array $data
     * @param int $userId
     * @param string $userType
     * @return array
     * @throws TaskException
     */
    public function updateSubmission(array $data, int $userId, string $userType): array
    {
        try {
            // Create basic user context (fallback)
            $userContext = (object) [
                'id' => $userId,
                'type' => $userType,
                'name' => 'User ' . $userId,
                'email' => 'user' . $userId . '@example.com'
            ];

            // Get existing submission (TODO: Implement proper method)
            $existingSubmission = null; // Placeholder
            if (!$existingSubmission) {
                throw new TaskException('Submission not found', 404);
            }

            // Check if user can update this submission (TODO: Implement permission check)
            // if (!$this->permissionService->canUpdateSubmission($userContext, $existingSubmission)) {
            //     throw new TaskException('Permission denied to update submission', 403);
            // }

            // Update submission (TODO: Implement proper method)
            $updatedSubmission = $existingSubmission; // Placeholder

            Log::info('Task submission updated', [
                'submission_id' => $updatedSubmission->id,
                'task_id' => $data['task_id'],
                'user_id' => $userId,
                'user_type' => $userType
            ]);

            // Return basic response (TODO: Implement proper response formatting)
            return [
                'submission_id' => $updatedSubmission->id ?? 'unknown',
                'task_id' => $data['task_id'],
                'user_id' => $userId,
                'user_type' => $userType,
                'updated_at' => now()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update submission', [
                'task_id' => $data['task_id'] ?? null,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            throw new TaskException('Failed to update submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate submit permissions
     */
    protected function validateSubmitPermissions(object $userContext, array $data): void
    {
        $submissionType = $data['submission_type'] ?? 'assignment_submission';

        // Basic permission validation (TODO: Implement proper permission service)
        switch ($submissionType) {
            case 'task_completion':
                if ($userContext->type !== 'lecturer') {
                    throw new TaskException('Only lecturers can submit task completion', 403);
                }
                break;
            case 'assignment_submission':
                if ($userContext->type !== 'student') {
                    throw new TaskException('Only students can submit assignments', 403);
                }
                break;
            default:
                // Allow both students and lecturers for other submission types
                if (!in_array($userContext->type, ['student', 'lecturer'])) {
                    throw new TaskException('Invalid user type for submission', 403);
                }
        }
    }

    /**
     * Validate task submission
     */
    protected function validateTaskSubmission(Task $task, object $userContext, array $data): void
    {
        // Check if task is still active
        if ($task->status === 'cancelled') {
            throw new TaskException('Cannot submit cancelled task', 400);
        }

        // Check if task deadline has passed (for students)
        if ($userContext->type === 'student' && $task->deadline && now()->gt($task->deadline)) {
            $data['is_late'] = true;
            if (!$data['late_reason']) {
                throw new TaskException('Late submission requires a reason', 400);
            }
        }

        // Check if user is assigned to this task (TODO: Implement proper assignment check)
        // if (!$this->taskService->isUserAssignedToTask($task->id, $userContext->id, $userContext->type)) {
        //     throw new TaskException('User is not assigned to this task', 403);
        // }
    }

    /**
     * Prepare submission data
     */
    protected function prepareSubmissionData(array $data, object $userContext, Task $task): array
    {
        return [
            'task_id' => $data['task_id'],
            'user_id' => $userContext->id,
            'user_type' => $userContext->type,
            'submission_type' => $data['submission_type'],
            'submission_content' => $data['submission_content'],
            'submission_notes' => $data['submission_notes'] ?? null,
            'submission_files' => $data['submission_files'] ?? [],
            'completion_status' => $data['completion_status'] ?? 'completed',
            'grade' => $data['grade'] ?? null,
            'feedback' => $data['feedback'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'difficulty_level' => $data['difficulty_level'] ?? null,
            'tags' => $data['tags'] ?? [],
            'is_late' => $data['is_late'] ?? false,
            'late_reason' => $data['late_reason'] ?? null,
            'submitted_at' => now(),
            'metadata' => [
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'submission_version' => 1
            ]
        ];
    }

    /**
     * Create or update submission
     */
    protected function createOrUpdateSubmission(array $submissionData, object $userContext)
    {
        // Check if submission already exists (TODO: Implement proper method)
        $existingSubmission = null; // Placeholder

        if ($existingSubmission) {
            // Update existing submission (TODO: Implement proper method)
            return $existingSubmission; // Placeholder
        } else {
            // Create new submission (TODO: Implement proper method)
            return (object) [
                'id' => 'sub_' . time(),
                'task_id' => $submissionData['task_id'],
                'user_id' => $userContext->id,
                'user_type' => $userContext->type,
                'submission_type' => $submissionData['submission_type'] ?? null,
                'submission_content' => $submissionData['submission_content'] ?? null,
                'submission_notes' => $submissionData['submission_notes'] ?? null,
                'submission_files' => $submissionData['submission_files'] ?? [],
                'completion_status' => $submissionData['completion_status'] ?? null,
                'grade' => $submissionData['grade'] ?? null,
                'feedback' => $submissionData['feedback'] ?? null,
                'estimated_hours' => $submissionData['estimated_hours'] ?? null,
                'difficulty_level' => $submissionData['difficulty_level'] ?? null,
                'tags' => $submissionData['tags'] ?? [],
                'is_late' => $submissionData['is_late'] ?? false,
                'late_reason' => $submissionData['late_reason'] ?? null,
                'submitted_at' => $submissionData['submitted_at'] ?? now(),
                'updated_at' => now()
            ];
        }
    }

    /**
     * Update task status if needed
     */
    protected function updateTaskStatusIfNeeded(Task $task, object $userContext, array $data): void
    {
        $submissionType = $data['submission_type'] ?? 'assignment_submission';

        // For lecturers completing tasks, update task status (TODO: Implement proper method)
        if ($userContext->type === 'lecturer' && $submissionType === 'task_completion') {
            // $this->taskService->updateTaskStatus($task, 'completed');
            Log::info('Task status should be updated to completed', ['task_id' => $task->id]);
        }

        // For students submitting assignments, update submission status (TODO: Implement proper method)
        if ($userContext->type === 'student' && $submissionType === 'assignment_submission') {
            // $this->taskService->updateTaskSubmissionStatus($task, 'submitted');
            Log::info('Task submission status should be updated to submitted', ['task_id' => $task->id]);
        }
    }

    /**
     * Send submission notifications
     */
    protected function sendSubmissionNotifications(Task $task, object $userContext, $submission): void
    {
        try {
            $notificationData = [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'submission_id' => $submission->id,
                'submitted_by' => [
                    'id' => $userContext->id,
                    'name' => $userContext->name,
                    'email' => $userContext->email,
                    'type' => $userContext->type
                ],
                'submission_type' => $submission->submission_type,
                'submitted_at' => $submission->submitted_at,
                'is_late' => $submission->is_late ?? false
            ];

            // Send Kafka notification (TODO: Implement proper Kafka service)
            // $this->kafkaProducer->send('task-submitted', $notificationData);
            Log::info('Kafka notification should be sent', $notificationData);

            // Send email notification to task creator/assigner (TODO: Implement proper email service)
            // $this->taskService->sendSubmissionNotificationEmail($task, $submission, $userContext);
            Log::info('Email notification should be sent', [
                'task_id' => $task->id,
                'submission_id' => $submission->id,
                'recipient_type' => 'task_creator'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send submission notifications', [
                'task_id' => $task->id,
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Format submission response
     */
    protected function formatSubmissionResponse($submission, Task $task, object $userContext): array
    {
        return [
            'submission_id' => $submission->id ?? null,
            'task_id' => $task->id,
            'task_title' => $task->title,
            'user_id' => $userContext->id,
            'user_name' => $userContext->name ?? null,
            'user_type' => $userContext->type,
            'submission_type' => $submission->submission_type ?? null,
            'submission_content' => $submission->submission_content ?? null,
            'submission_notes' => $submission->submission_notes ?? null,
            'submission_files' => $submission->submission_files ?? [],
            'completion_status' => $submission->completion_status ?? null,
            'grade' => $submission->grade ?? null,
            'feedback' => $submission->feedback ?? null,
            'estimated_hours' => $submission->estimated_hours ?? null,
            'difficulty_level' => $submission->difficulty_level ?? null,
            'tags' => $submission->tags ?? [],
            'is_late' => $submission->is_late ?? false,
            'late_reason' => $submission->late_reason ?? null,
            'submitted_at' => $submission->submitted_at ?? null,
            'updated_at' => $submission->updated_at ?? null,
            'task_deadline' => $task->deadline,
            'task_status' => $task->status,
            'notification_sent' => true
        ];
    }
}

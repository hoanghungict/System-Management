<?php

declare(strict_types=1);

namespace Modules\Notifications\app\Handlers\TaskHandle;

use Modules\Notifications\app\Services\NotificationService;
use Modules\Notifications\app\Contracts\NotificationEventHandler;
use Illuminate\Support\Facades\Log;

/**
 * Task Submitted Handler
 * 
 * Handles notifications when a task is submitted.
 * Sends notifications to the task creator/lecturer.
 * 
 * @package Modules\Notifications\app\Handlers\TaskHandle
 * @author System Management Team
 * @version 1.0.0
 */
class TaskSubmittedHandler implements NotificationEventHandler
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle task submitted notification
     */
    public function handle(string $channel, array $data): void
    {
        try {
            // Validate required data
            if (!isset($data['task_id']) || !isset($data['creator_id']) || !isset($data['creator_type'])) {
                Log::error('TaskSubmittedHandler: Missing required data', ['data' => $data]);
                return;
            }

            $taskId = $data['task_id'];
            $creatorId = $data['creator_id'];
            $creatorType = $data['creator_type'];
            $taskTitle = $data['task_title'] ?? 'Task';
            $submitterName = $data['submitter_name'] ?? 'Student';
            $submissionContent = $data['submission_content'] ?? '';
            $submittedAt = $data['submitted_at'] ?? now()->format('Y-m-d H:i:s');

            // Prepare template data
            $templateData = [
                'task_id' => $taskId,
                'task_title' => $taskTitle,
                'submitter_name' => $submitterName,
                'submission_content' => $submissionContent,
                'submitted_at' => $submittedAt,
                'creator_name' => $data['creator_name'] ?? 'Lecturer',
                'task_url' => $data['task_url'] ?? '#',
                'submission_url' => $data['submission_url'] ?? '#',
                'is_late' => $data['is_late'] ?? false,
                'days_late' => $data['days_late'] ?? 0,
            ];

            // Determine priority based on submission timing
            $priority = $data['is_late'] ? 'high' : 'normal';

            // Send notification
            $this->notificationService->sendNotification(
                'task_submitted', // Template name
                [['user_id' => $creatorId, 'user_type' => $creatorType]],
                $templateData,
                ['priority' => $priority]
            );

            Log::info('Task submitted notification sent', [
                'task_id' => $taskId,
                'creator_id' => $creatorId,
                'creator_type' => $creatorType,
                'submitter' => $submitterName,
                'is_late' => $data['is_late'] ?? false
            ]);

        } catch (\Exception $e) {
            Log::error('TaskSubmittedHandler: Failed to send notification', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

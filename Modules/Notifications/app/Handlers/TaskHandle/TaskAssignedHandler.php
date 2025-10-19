<?php

declare(strict_types=1);

namespace Modules\Notifications\app\Handlers\TaskHandle;

use Modules\Notifications\app\Services\NotificationService;
use Modules\Notifications\app\Contracts\NotificationEventHandler;
use Illuminate\Support\Facades\Log;

/**
 * Task Assigned Handler
 * 
 * Handles notifications when a task is assigned to a user.
 * Sends notifications to the assigned user.
 * 
 * @package Modules\Notifications\app\Handlers\TaskHandle
 * @author System Management Team
 * @version 1.0.0
 */
class TaskAssignedHandler implements NotificationEventHandler
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle task assigned notification
     */
    public function handle(string $channel, array $data): void
    {
        try {
            // Validate required data
            if (!isset($data['task_id']) || !isset($data['receiver_id']) || !isset($data['receiver_type'])) {
                Log::error('TaskAssignedHandler: Missing required data', ['data' => $data]);
                return;
            }

            $taskId = $data['task_id'];
            $receiverId = $data['receiver_id'];
            $receiverType = $data['receiver_type'];
            $taskTitle = $data['task_title'] ?? 'New Task';
            $taskDescription = $data['task_description'] ?? '';
            $deadline = $data['deadline'] ?? null;
            $priority = $data['priority'] ?? 'medium';
            $assignerName = $data['assigner_name'] ?? 'System';

            // Prepare template data
            $templateData = [
                'task_id' => $taskId,
                'task_title' => $taskTitle,
                'task_description' => $taskDescription,
                'deadline' => $deadline ? date('Y-m-d H:i', strtotime($deadline)) : 'No deadline',
                'priority' => ucfirst($priority),
                'assigner_name' => $assignerName,
                'receiver_name' => $data['receiver_name'] ?? 'User',
                'task_url' => $data['task_url'] ?? '#',
                'assigned_at' => now()->format('Y-m-d H:i:s'),
                'due_in_days' => $deadline ? now()->diffInDays($deadline, false) : null,
            ];

            // Determine priority based on task priority and deadline
            $notificationPriority = $this->getNotificationPriority($priority, $deadline);

            // Send notification
            $this->notificationService->sendNotification(
                'task_assigned', // Template name
                [['user_id' => $receiverId, 'user_type' => $receiverType]],
                $templateData,
                ['priority' => $notificationPriority]
            );

            Log::info('Task assigned notification sent', [
                'task_id' => $taskId,
                'receiver_id' => $receiverId,
                'receiver_type' => $receiverType,
                'assigner' => $assignerName
            ]);

        } catch (\Exception $e) {
            Log::error('TaskAssignedHandler: Failed to send notification', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get notification priority based on task priority and deadline
     */
    private function getNotificationPriority(string $taskPriority, ?string $deadline): string
    {
        // High priority if task is high priority
        if ($taskPriority === 'high') {
            return 'high';
        }

        // High priority if deadline is within 24 hours
        if ($deadline && now()->diffInHours($deadline) <= 24) {
            return 'high';
        }

        return 'normal';
    }
}
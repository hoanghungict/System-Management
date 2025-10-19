<?php

declare(strict_types=1);

namespace Modules\Notifications\app\Handlers\TaskHandle;

use Modules\Notifications\app\Services\NotificationService;
use Modules\Notifications\app\Contracts\NotificationEventHandler;
use Illuminate\Support\Facades\Log;

/**
 * Task Created Handler
 * 
 * Handles notifications when a new task is created.
 * Sends notifications to assigned users and relevant stakeholders.
 * 
 * @package Modules\Notifications\app\Handlers\TaskHandle
 * @author System Management Team
 * @version 1.0.0
 */
class TaskCreatedHandler implements NotificationEventHandler
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle task created notification
     */
    public function handle(string $channel, array $data): void
    {
        try {
            // Validate required data
            if (!isset($data['task_id']) || !isset($data['receiver_id']) || !isset($data['receiver_type'])) {
                Log::error('TaskCreatedHandler: Missing required data', ['data' => $data]);
                return;
            }

            $taskId = $data['task_id'];
            $receiverId = $data['receiver_id'];
            $receiverType = $data['receiver_type'];
            $taskTitle = $data['task_title'] ?? 'New Task';
            $taskDescription = $data['task_description'] ?? '';
            $deadline = $data['deadline'] ?? null;
            $priority = $data['priority'] ?? 'medium';
            $creatorName = $data['creator_name'] ?? 'System';

            // Prepare template data
            $templateData = [
                'task_id' => $taskId,
                'task_title' => $taskTitle,
                'task_description' => $taskDescription,
                'deadline' => $deadline ? date('Y-m-d H:i', strtotime($deadline)) : 'No deadline',
                'priority' => ucfirst($priority),
                'creator_name' => $creatorName,
                'receiver_name' => $data['receiver_name'] ?? 'User',
                'task_url' => $data['task_url'] ?? '#',
                'created_at' => now()->format('Y-m-d H:i:s'),
            ];

            // Send notification
            $this->notificationService->sendNotification(
                'task_created', // Template name
                [['user_id' => $receiverId, 'user_type' => $receiverType]],
                $templateData,
                ['priority' => $priority === 'high' ? 'high' : 'normal']
            );

            Log::info('Task created notification sent', [
                'task_id' => $taskId,
                'receiver_id' => $receiverId,
                'receiver_type' => $receiverType
            ]);

        } catch (\Exception $e) {
            Log::error('TaskCreatedHandler: Failed to send notification', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

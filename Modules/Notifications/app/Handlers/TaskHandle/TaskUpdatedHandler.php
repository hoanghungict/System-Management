<?php

declare(strict_types=1);

namespace Modules\Notifications\app\Handlers\TaskHandle;

use Modules\Notifications\app\Services\NotificationService;
use Modules\Notifications\app\Contracts\NotificationEventHandler;
use Illuminate\Support\Facades\Log;

/**
 * Task Updated Handler
 * 
 * Handles notifications when a task is updated.
 * Sends notifications to assigned users about changes.
 * 
 * @package Modules\Notifications\app\Handlers\TaskHandle
 * @author System Management Team
 * @version 1.0.0
 */
class TaskUpdatedHandler implements NotificationEventHandler
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle task updated notification
     */
    public function handle(string $channel, array $data): void
    {
        try {
            // Validate required data
            if (!isset($data['task_id']) || !isset($data['receiver_id']) || !isset($data['receiver_type'])) {
                Log::error('TaskUpdatedHandler: Missing required data', ['data' => $data]);
                return;
            }

            $taskId = $data['task_id'];
            $receiverId = $data['receiver_id'];
            $receiverType = $data['receiver_type'];
            $taskTitle = $data['task_title'] ?? 'Task';
            $changes = $data['changes'] ?? [];
            $updaterName = $data['updater_name'] ?? 'System';

            // Prepare template data
            $templateData = [
                'task_id' => $taskId,
                'task_title' => $taskTitle,
                'changes' => $changes,
                'updater_name' => $updaterName,
                'receiver_name' => $data['receiver_name'] ?? 'User',
                'task_url' => $data['task_url'] ?? '#',
                'updated_at' => now()->format('Y-m-d H:i:s'),
                'change_summary' => $this->formatChanges($changes),
            ];

            // Determine priority based on changes
            $priority = $this->getPriorityFromChanges($changes);

            // Send notification
            $this->notificationService->sendNotification(
                'task_updated', // Template name
                [['user_id' => $receiverId, 'user_type' => $receiverType]],
                $templateData,
                ['priority' => $priority]
            );

            Log::info('Task updated notification sent', [
                'task_id' => $taskId,
                'receiver_id' => $receiverId,
                'receiver_type' => $receiverType,
                'changes' => $changes
            ]);

        } catch (\Exception $e) {
            Log::error('TaskUpdatedHandler: Failed to send notification', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Format changes for display
     */
    private function formatChanges(array $changes): string
    {
        $formatted = [];
        
        foreach ($changes as $field => $change) {
            $oldValue = $change['old'] ?? '';
            $newValue = $change['new'] ?? '';
            
            switch ($field) {
                case 'title':
                    $formatted[] = "Title: '{$oldValue}' → '{$newValue}'";
                    break;
                case 'description':
                    $formatted[] = "Description updated";
                    break;
                case 'deadline':
                    $formatted[] = "Deadline: {$oldValue} → {$newValue}";
                    break;
                case 'priority':
                    $formatted[] = "Priority: {$oldValue} → {$newValue}";
                    break;
                case 'status':
                    $formatted[] = "Status: {$oldValue} → {$newValue}";
                    break;
                default:
                    $formatted[] = ucfirst($field) . ": {$oldValue} → {$newValue}";
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * Get priority based on changes
     */
    private function getPriorityFromChanges(array $changes): string
    {
        $highPriorityFields = ['deadline', 'priority', 'status'];
        
        foreach ($changes as $field => $change) {
            if (in_array($field, $highPriorityFields)) {
                return 'high';
            }
        }

        return 'normal';
    }
}

<?php

declare(strict_types=1);

namespace Modules\Notifications\app\Handlers\ReminderHandle;

use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Task Deadline Reminder Handler
 * 
 * Xử lý nhắc nhở deadline task sắp tới
 */
class TaskDeadlineReminderHandler implements NotificationEventHandler
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(string $channel, array $data): void
    {
        try {
            Log::info('TaskDeadlineReminderHandler: Processing reminder', [
                'channel' => $channel,
                'data' => $data
            ]);

            // Validate required data
            if (!isset($data['user_id'], $data['task_id'], $data['reminder_time'])) {
                Log::warning('TaskDeadlineReminderHandler: Missing required data', [
                    'data' => $data
                ]);
                return;
            }

            $userId = (int) $data['user_id'];
            $userType = $data['user_type'] ?? 'student';
            $taskId = (int) $data['task_id'];
            $reminderTime = $data['reminder_time'];

            // Prepare template data
            $templateData = $this->prepareTemplateData($data);

            // Send notification
            $result = $this->notificationService->sendNotification(
                'task_deadline_reminder',
                [
                    [
                        'user_id' => $userId,
                        'user_type' => $userType,
                        'channels' => $this->getChannels($data)
                    ]
                ],
                $templateData,
                [
                    'priority' => $this->getPriority($data),
                    'sender_id' => $data['sender_id'] ?? null,
                    'sender_type' => $data['sender_type'] ?? 'system'
                ]
            );

            if ($result['success']) {
                Log::info('TaskDeadlineReminderHandler: Reminder sent successfully', [
                    'notification_id' => $result['notification_id'],
                    'user_id' => $userId,
                    'task_id' => $taskId
                ]);
            } else {
                Log::error('TaskDeadlineReminderHandler: Failed to send reminder', [
                    'error' => $result['error'],
                    'user_id' => $userId,
                    'task_id' => $taskId
                ]);
            }

        } catch (\Exception $e) {
            Log::error('TaskDeadlineReminderHandler: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
        }
    }

    /**
     * Prepare template data for notification
     */
    private function prepareTemplateData(array $data): array
    {
        $deadline = $this->ensureString($data['deadline'] ?? '');
        $timeUntilDeadline = $this->calculateTimeUntilDeadline($deadline);
        
        return [
            'user_name' => $this->ensureString($data['user_name'] ?? 'User'),
            'task_name' => $this->ensureString($data['task_name'] ?? 'Task'),
            'task_description' => $this->ensureString($data['task_description'] ?? ''),
            'deadline' => $deadline,
            'time_until_deadline' => $timeUntilDeadline,
            'reminder_time' => $this->ensureString($data['reminder_time'] ?? ''),
            'task_url' => $this->ensureString($data['task_url'] ?? ''),
            'app_name' => config('app.name', 'Hệ thống quản lý giáo dục'),
            'year' => date('Y'),
            'subject' => 'Nhắc nhở deadline: ' . ($data['task_name'] ?? 'Task')
        ];
    }

    /**
     * Get notification channels based on reminder type
     */
    private function getChannels(array $data): array
    {
        $reminderType = $data['reminder_type'] ?? 'email';
        
        return match($reminderType) {
            'email' => ['email', 'in_app'],
            'push' => ['push', 'in_app'],
            'sms' => ['sms', 'in_app'],
            'in_app' => ['in_app'],
            default => ['email', 'push', 'in_app']
        };
    }

    /**
     * Get notification priority based on time until deadline
     */
    private function getPriority(array $data): string
    {
        $deadline = $data['deadline'] ?? '';
        if (empty($deadline)) {
            return 'medium';
        }

        $deadlineTime = strtotime($deadline);
        $now = time();
        $hoursUntilDeadline = ($deadlineTime - $now) / 3600;

        return match(true) {
            $hoursUntilDeadline <= 1 => 'critical',
            $hoursUntilDeadline <= 24 => 'high',
            $hoursUntilDeadline <= 72 => 'medium',
            default => 'low'
        };
    }

    /**
     * Calculate time until deadline
     */
    private function calculateTimeUntilDeadline(string $deadline): string
    {
        if (empty($deadline)) {
            return 'Không xác định';
        }

        $deadlineTime = strtotime($deadline);
        $now = time();
        $diff = $deadlineTime - $now;

        if ($diff <= 0) {
            return 'Đã quá hạn';
        }

        $days = floor($diff / 86400);
        $hours = floor(($diff % 86400) / 3600);
        $minutes = floor(($diff % 3600) / 60);

        if ($days > 0) {
            return "{$days} ngày {$hours} giờ";
        } elseif ($hours > 0) {
            return "{$hours} giờ {$minutes} phút";
        } else {
            return "{$minutes} phút";
        }
    }

    /**
     * Ensure value is string
     */
    private function ensureString($value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }
}

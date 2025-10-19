<?php

declare(strict_types=1);

namespace Modules\Notifications\app\Handlers\ReminderHandle;

use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Task Overdue Handler
 * 
 * Xử lý thông báo task quá hạn
 */
class TaskOverdueHandler implements NotificationEventHandler
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(string $channel, array $data): void
    {
        try {
            Log::info('TaskOverdueHandler: Processing overdue task', [
                'channel' => $channel,
                'data' => $data
            ]);

            // Validate required data
            if (!isset($data['user_id'], $data['task_id'], $data['deadline'])) {
                Log::warning('TaskOverdueHandler: Missing required data', [
                    'data' => $data
                ]);
                return;
            }

            $userId = (int) $data['user_id'];
            $userType = $data['user_type'] ?? 'student';
            $taskId = (int) $data['task_id'];
            $deadline = $data['deadline'];

            // Prepare template data
            $templateData = $this->prepareTemplateData($data);

            // Send notification
            $result = $this->notificationService->sendNotification(
                'task_overdue',
                [
                    [
                        'user_id' => $userId,
                        'user_type' => $userType,
                        'channels' => ['email', 'push', 'in_app']
                    ]
                ],
                $templateData,
                [
                    'priority' => 'critical',
                    'sender_id' => $data['sender_id'] ?? null,
                    'sender_type' => $data['sender_type'] ?? 'system'
                ]
            );

            if ($result['success']) {
                Log::info('TaskOverdueHandler: Overdue notification sent successfully', [
                    'notification_id' => $result['notification_id'],
                    'user_id' => $userId,
                    'task_id' => $taskId
                ]);
            } else {
                Log::error('TaskOverdueHandler: Failed to send overdue notification', [
                    'error' => $result['error'],
                    'user_id' => $userId,
                    'task_id' => $taskId
                ]);
            }

        } catch (\Exception $e) {
            Log::error('TaskOverdueHandler: Exception occurred', [
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
        $overdueTime = $this->calculateOverdueTime($deadline);
        
        return [
            'user_name' => $this->ensureString($data['user_name'] ?? 'User'),
            'task_name' => $this->ensureString($data['task_name'] ?? 'Task'),
            'task_description' => $this->ensureString($data['task_description'] ?? ''),
            'deadline' => $deadline,
            'overdue_time' => $overdueTime,
            'task_url' => $this->ensureString($data['task_url'] ?? ''),
            'app_name' => config('app.name', 'Hệ thống quản lý giáo dục'),
            'year' => date('Y'),
            'subject' => 'CẢNH BÁO: Task quá hạn - ' . ($data['task_name'] ?? 'Task')
        ];
    }

    /**
     * Calculate how long the task has been overdue
     */
    private function calculateOverdueTime(string $deadline): string
    {
        if (empty($deadline)) {
            return 'Không xác định';
        }

        $deadlineTime = strtotime($deadline);
        $now = time();
        $diff = $now - $deadlineTime;

        if ($diff <= 0) {
            return 'Chưa quá hạn';
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

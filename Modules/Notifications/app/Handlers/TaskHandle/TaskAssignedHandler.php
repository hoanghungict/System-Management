<?php

namespace Modules\Notifications\app\Handlers\TaskHandle;

use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Illuminate\Support\Facades\Log;

class TaskAssignedHandler implements NotificationEventHandler
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(string $channel, array $data): void
    {
        // Expecting: user_id, task_name, ...
        if (!isset($data['user_id'])) {
            Log::warning('TaskAssignedHandler: Thiếu user_id trong dữ liệu', ['data' => $data]);
            return;
        }

        $userId = (int) $data['user_id'];
        $userType = $data['user_type'] ?? 'student';
        $taskName = $data['task_name'] ?? 'Công việc mới';

        Log::info('TaskAssignedHandler: Xử lý giao việc', [
            'user_id' => $userId,
            'user_type' => $userType,
            'task_name' => $taskName
        ]);
        
        try {
            // Chuẩn bị data cho template từ Kafka message
            $templateData = $this->prepareTemplateData($data);
            
            Log::info('TaskAssignedHandler: Dữ liệu template từ Kafka message', [
                'template_data' => $templateData,
                'user_id' => $userId
            ]);

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

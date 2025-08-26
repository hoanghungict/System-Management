<?php

namespace Modules\Notifications\app\Services\NotificationService;

use Modules\Notifications\app\Repositories\Interfaces\NotificationRepositoryInterface;
use Modules\Notifications\app\Services\EmailService\EmailService;
use Modules\Notifications\app\Services\PushService\PushService;
use Modules\Notifications\app\Services\SmsService\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    protected $notificationRepository;
    protected $emailService;
    protected $pushService;
    protected $smsService;

    public function __construct(
        NotificationRepositoryInterface $notificationRepository,
        EmailService $emailService,
        PushService $pushService,
        SmsService $smsService
    ) {
        $this->notificationRepository = $notificationRepository;
        $this->emailService = $emailService;
        $this->pushService = $pushService;
        $this->smsService = $smsService;
    }

    /**
     * Gửi thông báo đơn lẻ
     */
    public function sendNotification(
        string $templateName,
        array $recipients,
        array $data = [],
        array $options = []
    ): array {
        try {
            // 1. Lấy template
            $template = $this->notificationRepository->findTemplateByName($templateName);
            if (!$template) {
                throw new \Exception("Template '{$templateName}' not found");
            }

            // 2. Xử lý recipients
            $processedRecipients = $this->processRecipients($recipients);

            // 3. Tạo notification record
            $notification = $this->notificationRepository->createNotification([
                'title' => $this->renderTemplate($template->title, $data),
                'content' => $this->renderTemplate($template->in_app_template, $data),
                'type' => $template->category ?? 'system',
                'priority' => $options['priority'] ?? $template->priority,
                'data' => $data,
                'template_id' => $template->id,
                'sender_id' => $options['sender_id'] ?? null,
                'sender_type' => $options['sender_type'] ?? null,
                'scheduled_at' => $options['scheduled_at'] ?? null,
                'status' => isset($options['scheduled_at']) ? 'pending' : 'processing'
            ]);

            // 4. Tạo user notifications
            $userNotifications = [];
            foreach ($processedRecipients as $recipient) {
                $userNotifications[] = $this->notificationRepository->createUserNotification([
                    'user_id' => $recipient['user_id'],
                    'user_type' => $recipient['user_type'],
                    'notification_id' => $notification->id
                ]);
            }

            // 5. Gửi notifications qua các kênh
            $this->sendNotifications($userNotifications, $template, $data);

            return [
                'success' => true,
                'notification_id' => $notification->id,
                'recipients_count' => count($processedRecipients),
                'message' => 'Notification sent successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Notification failed', [
                'template' => $templateName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Gửi thông báo hàng loạt
     */
    public function sendBulkNotification(
        string $templateName,
        array $recipients,
        array $data = [],
        array $options = []
    ): array {
        // Chia nhỏ recipients để xử lý
        $chunks = array_chunk($recipients, 100);
        $results = [];

        foreach ($chunks as $chunk) {
            $result = $this->sendNotification($templateName, $chunk, $data, $options);
            $results[] = $result;
        }

        return [
            'success' => true,
            'total_chunks' => count($chunks),
            'results' => $results
        ];
    }

    /**
     * Lên lịch gửi thông báo
     */
    public function scheduleNotification(
        string $templateName,
        array $recipients,
        array $data = [],
        \DateTime $scheduledAt,
        array $options = []
    ): array {
        $options['scheduled_at'] = $scheduledAt;
        return $this->sendNotification($templateName, $recipients, $data, $options);
    }

    /**
     * Lấy templates theo category
     */
    public function getTemplatesByCategory(string $category = null): array
    {
        if ($category) {
            $templates = $this->notificationRepository->getTemplatesByCategory($category);
        } else {
            $templates = $this->notificationRepository->getTemplatesByCategory('all');
        }

        return $templates->toArray();
    }

    /**
     * Lấy notifications của user
     */
    public function getUserNotifications(int $userId, string $userType, int $limit = 20, int $offset = 0): array
    {
        $notifications = $this->notificationRepository->getUserNotifications($userId, $userType, $limit, $offset);
        
        return [
            'data' => $notifications->toArray(),
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $this->notificationRepository->countUnreadNotifications($userId, $userType)
            ]
        ];
    }

    /**
     * Đánh dấu notification đã đọc
     */
    public function markNotificationAsRead(int $userNotificationId): array
    {
        $success = $this->notificationRepository->markNotificationAsRead($userNotificationId);
        
        return [
            'success' => $success,
            'message' => $success ? 'Notification marked as read' : 'Notification not found'
        ];
    }

    /**
     * Lấy trạng thái notification
     */
    public function getNotificationStatus(int $notificationId): array
    {
        $notification = $this->notificationRepository->findNotificationById($notificationId);
        
        if (!$notification) {
            return [
                'success' => false,
                'message' => 'Notification not found'
            ];
        }

        return [
            'success' => true,
            'data' => [
                'id' => $notification->id,
                'status' => $notification->status,
                'sent_at' => $notification->sent_at,
                'recipients_count' => $notification->userNotifications->count(),
                'email_sent_count' => $notification->userNotifications->where('email_sent', true)->count(),
                'push_sent_count' => $notification->userNotifications->where('push_sent', true)->count(),
                'sms_sent_count' => $notification->userNotifications->where('sms_sent', true)->count()
            ]
        ];
    }

    /**
     * Xử lý recipients
     */
    private function processRecipients(array $recipients): array
    {
        $processed = [];

        foreach ($recipients as $recipient) {
            if (is_array($recipient)) {
                $processed[] = $recipient;
            } else {
                // Nếu chỉ là user_id, tạo default structure
                $processed[] = [
                    'user_id' => $recipient,
                    'user_type' => 'student', // Default
                    'channels' => ['email', 'push', 'in_app']
                ];
            }
        }

        return $processed;
    }

    /**
     * Gửi notifications qua các kênh
     */
    private function sendNotifications($userNotifications, $template, array $data): void
    {
        foreach ($userNotifications as $userNotification) {
            $this->sendToChannels($userNotification, $template, $data);
        }
    }

    /**
     * Gửi qua các kênh cụ thể
     */
    private function sendToChannels($userNotification, $template, array $data): void
    {
        $channels = $template->channels ?? ['email', 'push', 'in_app'];

        foreach ($channels as $channel) {
            try {
                switch ($channel) {
                    case 'email':
                        if ($template->email_template) {
                            $this->emailService->send(
                                $userNotification->user_id,
                                $userNotification->user_type,
                                $this->renderTemplate($template->email_template, $data),
                                $template->subject ?? 'Notification'
                            );
                            $userNotification->markEmailAsSent();
                        }
                        break;

                    case 'push':
                        if ($template->push_template) {
                            $this->pushService->send(
                                $userNotification->user_id,
                                $userNotification->user_type,
                                $this->renderTemplate($template->push_template, $data)
                            );
                            $userNotification->markPushAsSent();
                        }
                        break;

                    case 'sms':
                        if ($template->sms_template) {
                            $this->smsService->send(
                                $userNotification->user_id,
                                $userNotification->user_type,
                                $this->renderTemplate($template->sms_template, $data)
                            );
                            $userNotification->markSmsAsSent();
                        }
                        break;
                }
            } catch (\Exception $e) {
                Log::error("Failed to send {$channel} notification", [
                    'user_notification_id' => $userNotification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Render template với data
     */
    private function renderTemplate(?string $template, array $data): string
    {
        if (!$template) {
            return '';
        }

        foreach ($data as $key => $value) {
            $template = str_replace("{{{$key}}}", $value, $template);
        }

        return $template;
    }
}

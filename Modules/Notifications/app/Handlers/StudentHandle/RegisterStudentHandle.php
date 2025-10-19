<?php

namespace Modules\Notifications\app\Handlers\StudentHandle;

use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Illuminate\Support\Facades\Log;

class RegisterStudentHandle implements NotificationEventHandler
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(string $channel, array $data): void
    {
        // Expecting: user_id, user_name, ...
        if (!isset($data['user_id'])) {
            Log::warning('RegisterStudentHandle: Thiếu user_id trong dữ liệu', ['data' => $data]);
            return;
        }

        $userId = (int) $data['user_id'];
        $userName = $data['user_name'] ?? 'Sinh viên mới';

        Log::info('RegisterStudentHandle: Xử lý đăng ký sinh viên', [
            'user_id' => $userId,
            'user_name' => $userName
        ]);

        try {
            // Chuẩn bị data cho template từ Kafka message
            $templateData = $this->prepareTemplateData($data);

            // Sử dụng NotificationService để gửi thông báo
            $result = $this->notificationService->sendNotification(
                'student_registered', // Tên template
                [
                    [
                        'user_id' => $userId,
                        'user_type' => 'student',
                        'channels' => ['email', 'push', 'in_app']
                    ]
                ],
                $templateData,
                [
                    'priority' => 'high',
                    'sender_id' => null,
                    'sender_type' => 'system'
                ]
            );

            if ($result['success']) {
                Log::info('RegisterStudentHandle: Gửi thông báo thành công', [
                    'notification_id' => $result['notification_id'],
                    'user_id' => $userId
                ]);
            } else {
                Log::error('RegisterStudentHandle: Gửi thông báo thất bại', [
                    'error' => $result['error'],
                    'user_id' => $userId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('RegisterStudentHandle: Lỗi xảy ra', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function prepareTemplateData(array $kafkaData): array
    {
        $userName = $this->ensureString($kafkaData['user_name'] ?? 'Sinh viên mới');
        $email = $this->ensureString($kafkaData['email'] ?? '');
        $class = $this->ensureString($kafkaData['class'] ?? '');

        return [
            'user_name' => $userName,
            'email' => $email,
            'class' => $class,
            'app_name' => config('notification_config.name', 'Hệ thống quản lý giáo dục'),
            'year' => date('Y'),
            'subject' => 'Chào mừng bạn đến với hệ thống!'
        ];
    }

    private function ensureString($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return '';
        }

        return (string)$value;
    }
}

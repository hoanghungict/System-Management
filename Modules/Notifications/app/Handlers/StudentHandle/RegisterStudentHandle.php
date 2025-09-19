<?php

namespace Modules\Notifications\app\Handlers\StudentHandle;

use Exception;
use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Handlers\HandleUtil\ensureString;

class RegisterStudentHandle implements NotificationEventHandler {
    protected $notificationService;
    protected $ensureString;
    public function __construct(NotificationService $notificationService, ensureString $ensureString)
    {
        $this->notificationService = $notificationService;
        $this->ensureString = $ensureString;
    }

    public function handle(string $channel, array $data): void
    {
        Log::info('RegisterStudentHandle: Bắt đầu xử lý', [
            'channel' => $channel,
            'data' => $data
        ]);

        if(!isset($data['user_id'])) {
            Log::warning('RegisterStudentHandle: Thiếu user_id trong dữ liệu', ['data' => $data]);
            return;
        }

        $userId =  (int) $data['user_id'];
        $user_name = $data['user_name'] ?? 'Sinh viên mới';
        

        Log::info('RegisterStudentHandle: Xử lý đăng ký sinh viên', [
            'user_id' => $userId,
            'user_name' => $user_name
        ]);

        try {
            $templateData = $this->prepareTemplateData($data);

            Log::info('RegisterStudentHandle: Dữ liệu template từ Kafka message', [
                'template_data' => $templateData,
                'user_id' => $userId
            ]);
            $result = $this->notificationService->sendNotification(
                'student_account_created',
                [
                    [
                        'user_id' => $userId,
                        'user_type' => 'student'
                    ]
                ],
                $templateData,
                [
                    'priority' => 'medium',
                    'sender_id' => $data['sender_id'] ?? null,
                    'sender_type' => $data['sender_type'] ?? 'student'
                ]
            );

            if ($result['success']) {
                Log::info('RegisterStudentHandle: Gửi thông báo thành công', [
                    'notification_id' => $result['notification_id'],
                    'user_id' => $userId
                ]);
            }
            else {
                Log::error('RegisterStudentHandle: Gửi thông báo thất bại', [
                    'error' => $result['error'],
                    'user_id' => $userId
                ]);
            }
        }catch(Exception $e)
        {
            Log::error('RegisterStudentHandle: Lỗi xảy ra', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
    }

    public function prepareTemplateData(array $data): array
    {
        $usernName = $this->ensureString->ensureString($data['user_name'] ?? 'sv_' . $data['student_code']);
        $name = $this->ensureString->ensureString($data['name'] ?? 'Sinh viên mới');
        $password = $this->ensureString->ensureString($data['password'] ?? '123456');

        return [
            // Dữ liệu từ kafka
            'user_name' => $usernName,
            'name' => $name,
            'password' => $password,
            // Dữ Liệu Hệ Thống
            'app_name' => config('notification_config.name', 'Hệ Điện Tử Khoa CNTT'),
            'year' => date('Y'),
            'subject' => 'Đăng ký tài khoản sinh viên',
            'logo_url' => config('notification_config.logo_url', 'https://via.placeholder.com/120x40/3b82f6/ffffff?text=LOGO'),
            'banner_url' => config('notification_config.banner_url', 'https://via.placeholder.com/600x200/3b82f6/ffffff?text=Banner'),
            'original_data' => $this->ensureString->ensureString(json_encode($data, JSON_UNESCAPED_UNICODE))
        ];

    }
}
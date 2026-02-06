<?php

namespace Modules\Notifications\app\Handlers\LecturerHandle;

use Exception;
use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Handlers\HandleUtil\ensureString;

class RegisterLecturerHandle implements NotificationEventHandler {
    protected $notificationService;
    protected $ensureString;
    public function __construct(NotificationService $notificationService, ensureString $ensureString)
    {
        $this->notificationService = $notificationService;
        $this->ensureString = $ensureString;
    }

    public function handle(string $channel, array $data): void
    {
        /* Log::info('RegisterLecturerHandle: Bắt đầu xử lý', [
            'channel' => $channel,
            'data' => $data
        ]); */

        if(!isset($data['user_id'])) {
            Log::warning('RegisterLecturerHandle: Thiếu user_id trong dữ liệu', ['data' => $data]);
            return;
        }

        $userId =  (int) $data['user_id'];
        $user_name = $data['user_name'] ?? 'Sinh viên mới';
        

        /* Log::info('RegisterLecturerHandle: Xử lý đăng ký sinh viên', [
            'user_id' => $userId,
            'user_name' => $user_name
        ]); */

        try {
            $templateData = $this->prepareTemplateData($data);

            /* Log::info('RegisterLecturerHandle: Dữ liệu template từ Kafka message', [
                'template_data' => $templateData,
                'user_id' => $userId
            ]); */
            $result = $this->notificationService->sendNotification(
                'lecturer_account_created',
                [
                    [
                        'user_id' => $userId,
                        'user_type' => 'lecturer'
                    ]
                ],
                $templateData,
                [
                    'priority' => 'medium',
                    'sender_id' => $data['sender_id'] ?? null,
                    'sender_type' => $data['sender_type'] ?? 'lecturer'
                ]
            );

            if ($result['success']) {
                /* Log::info('RegisterLecturerHandle: Gửi thông báo thành công', [
                    'notification_id' => $result['notification_id'],
                    'user_id' => $userId
                ]); */
            }
            else {
                Log::error('RegisterLecturerHandle: Gửi thông báo thất bại', [
                    'error' => $result['error'],
                    'user_id' => $userId
                ]);
            }
        }catch(Exception $e)
        {
            Log::error('RegisterLecturerHandle: Lỗi xảy ra', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
    }

    public function prepareTemplateData(array $data): array
    {
        $usernName = $this->ensureString->ensureString($data['user_name'] ?? 'gv_' . $data['student_code']);
        $name = $this->ensureString->ensureString($data['name'] ?? 'Giảng viên mới');
        $password = $this->ensureString->ensureString($data['password'] ?? '123456');

        return [
            // Dữ liệu từ kafka
            'user_name' => $usernName,
            'name' => $name,
            'password' => $password,
            // Dữ Liệu Hệ Thống
            'app_name' => config('notification_config.name', 'Hệ Điện Tử Khoa CNTT'),
            'year' => date('Y'),
            'subject' => 'Đăng ký tài khoản giảng viên',
            'logo_url' => config('notification_config.logo_url', 'https://via.placeholder.com/120x40/3b82f6/ffffff?text=LOGO'),
            'banner_url' => config('notification_config.banner_url', 'https://via.placeholder.com/600x200/3b82f6/ffffff?text=Banner'),
            'original_data' => $this->ensureString->ensureString(json_encode($data, JSON_UNESCAPED_UNICODE))
        ];

    }
}
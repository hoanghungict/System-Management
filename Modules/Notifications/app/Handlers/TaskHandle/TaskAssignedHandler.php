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

        /* Log::info('TaskAssignedHandler: Xử lý giao việc', [
            'user_id' => $userId,
            'user_type' => $userType,
            'task_name' => $taskName
        ]); */
        
        try {
            // Chuẩn bị data cho template từ Kafka message
            $templateData = $this->prepareTemplateData($data);
            
            /* Log::info('TaskAssignedHandler: Dữ liệu template từ Kafka message', [
                'template_data' => $templateData,
                'user_id' => $userId
            ]); */

            // Sử dụng NotificationService để gửi thông báo qua tất cả kênh
            $result = $this->notificationService->sendNotification(
                'task_assigned', // Tên template
                [
                    [
                        'user_id' => $userId,
                        'user_type' => $userType,
                        'channels' => ['email', 'push', 'in_app']
                    ]
                ],
                $templateData, // Dữ liệu chuẩn bị cho template
                [
                    'priority' => 'medium',
                    'sender_id' => $data['assigner_id'] ?? null,
                    'sender_type' => $data['assigner_type'] ?? 'lecturer'
                ]
            );

            if ($result['success']) {
                /* Log::info('TaskAssignedHandler: Gửi thông báo thành công', [
                    'notification_id' => $result['notification_id'],
                    'user_id' => $userId,
                    'task_name' => $taskName
                ]); */
            } else {
                Log::error('TaskAssignedHandler: Gửi thông báo thất bại', [
                    'error' => $result['error'],
                    'user_id' => $userId
                ]);
            }

        } catch (\Exception $e) {
            Log::error('TaskAssignedHandler: Lỗi xảy ra', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Chuẩn bị dữ liệu cho template từ Kafka message
     */
    private function prepareTemplateData(array $kafkaData): array
    {
        // Lấy dữ liệu từ Kafka message và đảm bảo là chuỗi
        $taskName = $this->ensureString($kafkaData['task_name'] ?? 'Công việc mới');
        $userName = $this->ensureString($kafkaData['user_name'] ?? 'User');
        $assignerName = $this->ensureString($kafkaData['assigner_name'] ?? 'Hệ thống');
        $deadline = $this->ensureString($kafkaData['deadline'] ?? '');
        $taskDescription = $this->ensureString($kafkaData['task_description'] ?? '');
        $taskUrl = $this->ensureString($kafkaData['task_url'] ?? '');

        // Chuẩn bị dữ liệu cho template
        return [
            // Dữ liệu từ Kafka message
            'user_name' => $userName,
            'task_name' => $taskName,
            'task_description' => $taskDescription,
            'assigner_name' => $assignerName,
            'deadline' => $deadline,
            'task_url' => $taskUrl,
            
            // Dữ liệu hệ thống
            'app_name' => config('notification_config.name', 'Hệ thống quản lý giáo dục'),
            'year' => date('Y'),
            'subject' => 'Công việc mới: ' . $taskName,
            
            // URLs có thể từ config hoặc hardcode
            'logo_url' => config('notification_config.logo_url', 'https://via.placeholder.com/120x40/3b82f6/ffffff?text=LOGO'),
            'banner_url' => config('notification_config.banner_url', 'https://via.placeholder.com/600x200/3b82f6/ffffff?text=Banner'),
            
            // Giữ nguyên data gốc từ Kafka để có thể sử dụng sau này (nhưng convert thành string)
            'original_data' => $this->ensureString(json_encode($kafkaData, JSON_UNESCAPED_UNICODE))
        ];
    }

    /**
     * Đảm bảo giá trị là chuỗi
     */
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
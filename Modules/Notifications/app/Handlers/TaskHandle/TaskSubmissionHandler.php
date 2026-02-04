<?php

namespace Modules\Notifications\app\Handlers\TaskHandle;

use Exception;
use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Handlers\HandleUtil\ensureString;

class TaskSubmissionHandler implements NotificationEventHandler {
    protected $notificationService;
    protected $ensureString;
    public function __construct(NotificationService $notificationService, ensureString $ensureString)
    {
        $this->notificationService = $notificationService;
        $this->ensureString = $ensureString;
    }

    public function handle(string $channel, array $data): void
    {
        /* Log::info('TaskSubmittedHandle: Bắt đầu xử lý', [
            'channel' => $channel,
            'data' => $data
        ]); */

        if(!isset($data['receiver_id'])) {
            Log::warning('TaskSubmittedHandle: Thiếu user_id trong dữ liệu', ['data' => $data]);
            return;
        }

        $receiverId =  (int) $data['receiver_id'];
        $taskTitle = $data['taskTitle'] ?? 'Công việc mới';
        $receiverType = $data['receiver_type'] ?? 'lecturer';

        /* Log::info('TaskSubmittedHandle: Xử lý gửi thông báo', [
            'user_id' => $receiverId,
            'taskTitle' => $taskTitle
        ]); */

        try {
            $templateData = $this->prepareTemplateData($data);

            /* Log::info('TaskSubmittedHandle: Dữ liệu template từ Kafka message', [
                'template_data' => $templateData,
                'user_id' => $receiverId
            ]); */
            $result = $this->notificationService->sendNotification(
                'task_submission',
                [
                    [
                        'user_id' => $receiverId,
                        'user_type' => $receiverType
                    ]
                ],
                $templateData,
                [
                    'priority' => 'medium',
                    'sender_id' => $data['sender_id'] ?? null,
                    'sender_type' => $data['sender_type'] ?? 'student',
                    'key' => $data['key'] ?? null
                ]
            );

            if ($result['success']) {
                /* Log::info('TaskSubmittedHandle: Gửi thông báo thành công', [
                    'notification_id' => $result['notification_id'],
                    'user_id' => $receiverId
                ]); */
            }
            else {
                Log::error('RegisterStudentHandle: Gửi thông báo thất bại', [
                    'error' => $result['error'],
                    'user_id' => $receiverId
                ]);
            }
        }catch(Exception $e)
        {
            Log::error('RegisterStudentHandle: Lỗi xảy ra', [
                'user_id' => $receiverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
    }

    public function prepareTemplateData(array $data): array
    {
        $subject = $this->ensureString->ensureString($data['subject'] ?? 'Công việc mới');
        $lecturerName = $this->ensureString->ensureString($data['lecturerName'] ?? 'Giảng viên');
        $studentName = $this->ensureString->ensureString($data['studentName'] ?? 'Sinh viên');
        $taskTitle = $this->ensureString->ensureString($data['taskTitle'] ?? 'Công việc mới');
        $deadline = $this->ensureString->ensureString($data['deadline'] ?? '2025-01-01');
        $submittedAt = $this->ensureString->ensureString($data['submittedAt'] ?? '2025-01-01');
        $status = $this->ensureString->ensureString($data['status'] ?? 'Chưa nộp');
        $fileCount = $this->ensureString->ensureString($data['fileCount'] ?? 0);
        $reviewUrl = $this->ensureString->ensureString($data['reviewUrl'] ?? '#');
        $app_name = $this->ensureString->ensureString(config('notification_config.name', 'Hệ thống quản lý giáo dục'));
        $year = $this->ensureString->ensureString(date('Y'));

        $logo_url = $this->ensureString->ensureString(config('notification_config.logo_url', 'https://via.placeholder.com/120x40/3b82f6/ffffff?text=LOGO'));
        $banner_url = $this->ensureString->ensureString(config('notification_config.banner_url', 'https://via.placeholder.com/600x200/3b82f6/ffffff?text=Banner'));
        return [
            // Dữ liệu từ kafka
            'subject' => $subject,
            'lecturerName' => $lecturerName,
            'studentName' => $studentName,
            'taskTitle' => $taskTitle,
            'deadline' => $deadline,
            'submittedAt' => $submittedAt,
            'status' => $status,
            'fileCount' => $fileCount,
            'reviewUrl' => $reviewUrl,
            'app_name' => $app_name,
            'year' => $year,
            'logo_url' => $logo_url,
            'banner_url' => $banner_url,
            'original_data' => $this->ensureString->ensureString(json_encode($data, JSON_UNESCAPED_UNICODE))
        ];

    }
}
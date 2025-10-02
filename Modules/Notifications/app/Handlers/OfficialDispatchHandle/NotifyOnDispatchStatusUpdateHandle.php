<?php

namespace Modules\Notifications\app\Handlers\OfficialDispatchHandle;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Handlers\HandleUtil\ensureString;
use Modules\Notifications\app\Services\NotificationService\NotificationService;

class NotifyOnDispatchStatusUpdateHandle implements NotificationEventHandler
{
    protected $notificationService;
    protected $ensureString;

    public function __construct(NotificationService $notificationService, EnsureString $ensureString)
    {
        $this->notificationService = $notificationService;
        $this->ensureString = $ensureString;
    }

    public function handle(string $channel, array $data): void
    {
        if (!isset($data['user_id'])) {
            Log::warning('NotifyOnDispatchStatusUpdateHandle: Thiếu user_id trong dữ liệu', ['data' => $data]);
            return;
        }

        $userId = (int) $data['user_id'];
        $userType = $data['user_type'] ?? 'student';

        Log::info('NotifyOnDispatchStatusUpdateHandle: Xử lý cập nhật trạng thái công văn', [
            'user_id'   => $userId,
            'user_type' => $userType,
            'status'    => $data['status'] ?? null,
        ]);

        try {
            $templateData = $this->prepareTemplateData($data);

            Log::info('NotifyOnDispatchStatusUpdateHandle: Dữ liệu template từ Kafka message', [
                'template_data' => $templateData,
                'user_id'       => $userId
            ]);

            $result = $this->notificationService->sendNotification(
                'official_dispatch_status',
                [
                    [
                        'user_id'   => $userId,
                        'user_type' => $userType
                    ]
                ],
                $templateData,
                [
                    'priority'     => 'medium',
                    'sender_id'    => $data['sender_id'] ?? null,
                    'sender_type'  => $data['sender_type'] ?? 'system'
                ]
            );

            Log::info('NotifyOnDispatchStatusUpdateHandle: Gửi thông báo thành công', ['result' => $result]);
        } catch (\Exception $e) {
            Log::error('NotifyOnDispatchStatusUpdateHandle: Lỗi xảy ra', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
        }
    }

    public function prepareTemplateData(array $data): array
    {
        $authorName            = $this->ensureString->ensureString($data['authorName'] ?? 'Người tạo công văn');
        $documentTitle         = $this->ensureString->ensureString($data['documentTitle'] ?? 'Không có tiêu đề');
        $documentSerialNumber  = $this->ensureString->ensureString($data['documentSerialNumber'] ?? 'N/A');
        $reviewerName          = $this->ensureString->ensureString($data['reviewerName'] ?? 'Người xử lý');
        $status                = $this->ensureString->ensureString($data['status'] ?? 'Chưa xác định');
        $reviewComment         = $this->ensureString->ensureString($data['reviewComment'] ?? 'Không có ghi chú');
        $documentUrl           = $this->ensureString->ensureString($data['documentUrl'] ?? 'https://example.com/document');

        return [
            // Dữ liệu từ Kafka message
            'authorName'           => $authorName,
            'documentTitle'        => $documentTitle,
            'documentSerialNumber' => $documentSerialNumber,
            'reviewerName'         => $reviewerName,
            'status'               => $status,
            'reviewComment'        => $reviewComment,
            'documentUrl'          => $documentUrl,

            // Dữ liệu hệ thống
            'year'         => date('Y'),
            'app_name'     => config('app.name', 'HPC Corp'),
            'original_data'=> $this->ensureString->ensureString(json_encode($data, JSON_UNESCAPED_UNICODE))
        ];
    }
}

<?php

namespace Modules\Notifications\app\Handlers\OfficialDispatchHandle;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Handlers\HandleUtil\ensureString;
use Modules\Notifications\app\Services\NotificationService\NotificationService;

class SendOfficialHandle implements NotificationEventHandler
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
            Log::warning('SendOfficialHandle: Thiếu user_id trong dữ liệu', ['data' => $data]);
            return;
        }

        $userId = (int) $data['user_id'];
        $userType = $data['user_type'] ?? 'student';
        $documentTitle = $data['documentTitle'] ?? 'Thông báo chính thức';

        Log::info('SendOfficialHandle: Xử lý thông báo chính thức', [
            'user_id' => $userId,
            'user_type' => $userType,
            'documentTitle' => $documentTitle
        ]);
        
        try {
            $templateData = $this->prepareTemplateData($data);

            Log::info('SendOfficialHandle: Dữ liệu template từ Kafka message', [
                'template_data' => $templateData,
                'user_id' => $userId
            ]);

            $result = $this->notificationService->sendNotification(
                'official_dispatch',
                [
                    [
                        'user_id' => $userId,
                        'user_type' => $userType
                    ]
                ],
                $templateData,
                [
                    'priority' => 'medium',
                    'sender_id' => $data['sender_id'] ?? null,
                    'sender_type' => $data['sender_type'] ?? 'student'
                ]
            );
        } catch (\Exception $e) {
            Log::error('SendOfficialHandle: Lỗi xảy ra', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

    }
    
    public function prepareTemplateData(array $data): array
    {
        $documentTitle = $this->ensureString->ensureString($data['documentTitle'] ?? 'Thông báo chính thức');
        $documentUrl = $this->ensureString->ensureString($data['documentUrl'] ?? 'https://example.com/document');
        $documentSerialNumber = $this->ensureString->ensureString($data['documentSerialNumber'] ?? 'unknown');
        $assignerName  = $this->ensureString->ensureString($data['assignerName'] ?? 'unknown');
        $assigneeName = $this->ensureString->ensureString($data['assigneeName'] ?? 'unknown');
        $actionRequired = $this->ensureString->ensureString($data['actionRequired'] ?? 'unknown');
        $dateOfDispatch = $this->ensureString->ensureString($data['date'] ?? 'unknown');
        return [
           // Dữ liệu từ Kafka message $data[] - matching template variables
           'assignerName' => $assignerName,
           'assigneeName' => $assigneeName,
           'actionRequired' => $actionRequired,
           'assignedDate' => $dateOfDispatch,
           'documentTitle' => $documentTitle,
           'documentUrl' => $documentUrl,
           'documentSerialNumber' => $documentSerialNumber,

           // Dữ liệu hệ thống
           'year' => date('Y'),
           'original_data' => $this->ensureString->ensureString(json_encode($data, JSON_UNESCAPED_UNICODE))
        ];
    }
}

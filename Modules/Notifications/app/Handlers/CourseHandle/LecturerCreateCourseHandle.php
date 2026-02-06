<?php

namespace Modules\Notifications\app\Handlers\CourseHandle;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Handlers\HandleUtil\ensureString;
use Modules\Notifications\app\Services\NotificationService\NotificationService;

class LecturerCreateCourseHandle implements NotificationEventHandler
{
    protected NotificationService $notificationService;
    protected EnsureString $ensureString;

    public function __construct(NotificationService $notificationService, EnsureString $ensureString)
    {
        $this->notificationService = $notificationService;
        $this->ensureString = $ensureString;
    }

    /**
     * Handle Kafka message
     */
    public function handle(string $channel, array $data): void
    {
        if (!isset($data['admin_id'])) {
            Log::warning('LecturerCreateCourseHandle: Thiếu admin_id trong dữ liệu', ['data' => $data]);
            return;
        }

        $adminId = (int) $data['admin_id'];
        $adminType = $data['admin_type'] ?? 'lecturer';
        $lecturerName = $data['lecturer_name'] ?? 'Giảng viên';

        /* Log::info('LecturerCreateCourseHandle: Xử lý thông báo tạo khóa học', [
            'admin_id' => $adminId,
            'lecturer_name' => $lecturerName
        ]); */

        try {
            $templateData = $this->prepareTemplateData($data);

            /* Log::info('LecturerCreateCourseHandle: Dữ liệu template từ Kafka message', [
                'template_data' => $templateData,
                'admin_id' => $adminId
            ]); */

            $this->notificationService->sendNotification(
                'course_create',
                [
                    [
                        'user_id' => $adminId,
                        'user_type' => $adminType
                    ]
                ],
                $templateData,
                [
                    'priority' => 'high',
                    'sender_id' => $data['lecturer_id'] ?? null,
                    'sender_type' => 'lecturer'
                ]
            );
        } catch (\Exception $e) {
            Log::error('LecturerCreateCourseHandle: Lỗi xảy ra', [
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Chuẩn hóa dữ liệu cho template
     */
    public function prepareTemplateData(array $data): array
    {
        $lecturerName = $this->ensureString->ensureString($data['lecturer_name'] ?? 'Giảng viên');
        $title = $this->ensureString->ensureString($data['title'] ?? 'Khóa học mới');
        $courseReviewUrl = $this->ensureString->ensureString($data['course_review_url'] ?? 'https://example.com/courses/review');
        $date = $this->ensureString->ensureString($data['date'] ?? date('d/m/Y'));

        return [
            'lecturer_name' => $lecturerName,
            'title' => $title,
            'course_review_url' => $courseReviewUrl,
            'date' => $date,

            // Dữ liệu hệ thống
            'year' => date('Y'),
            'original_data' => $this->ensureString->ensureString(json_encode($data, JSON_UNESCAPED_UNICODE))
        ];
    }
}

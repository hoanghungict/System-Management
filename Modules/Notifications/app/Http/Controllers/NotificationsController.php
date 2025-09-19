<?php

namespace Modules\Notifications\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;
use Modules\Notifications\app\Http\Requests\SendNotificationRequest;
use Modules\Notifications\app\Http\Requests\SendBulkNotificationRequest;
use Modules\Notifications\app\Http\Requests\ScheduleNotificationRequest;
use Modules\Notifications\app\Http\Requests\GetUserNotificationsRequest;
use Modules\Notifications\app\Http\Requests\MarkAsReadRequest;

class NotificationsController extends Controller
{
    protected $notificationService;
    protected $kafkaProducer;

    public function __construct(
        NotificationService $notificationService,
        KafkaProducerService $kafkaProducer
    ) {
        $this->notificationService = $notificationService;
        $this->kafkaProducer = $kafkaProducer;
    }

    /**
     * Gửi thông báo đơn lẻ
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $result = $this->notificationService->sendNotification(
            $request->validated('template'),
            $request->validated('recipients'),
            $request->validated('data', []),
            $request->validated('options', [])
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    /**
     * Gửi thông báo hàng loạt
     */
    public function sendBulk(SendBulkNotificationRequest $request): JsonResponse
    {
        $result = $this->notificationService->sendBulkNotification(
            $request->validated('template'),
            $request->validated('recipients'),
            $request->validated('data', []),
            $request->validated('options', [])
        );

        return response()->json($result, 200);
    }

    /**
     * Lên lịch gửi thông báo
     */
    public function schedule(ScheduleNotificationRequest $request): JsonResponse
    {
        $result = $this->notificationService->scheduleNotification(
            $request->validated('template'),
            $request->validated('recipients'),
            $request->validated('data', []),
            new \DateTime($request->validated('scheduled_at')),
            $request->validated('options', [])
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    /**
     * Lấy danh sách templates
     */
    public function templates(): JsonResponse
    {
        $category = request()->query('category');
        $templates = $this->notificationService->getTemplatesByCategory($category);

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Lấy thông báo của user
     */
    public function userNotifications(GetUserNotificationsRequest $request): JsonResponse
    {
        $limit = $request->validated('limit', 20);
        $offset = $request->validated('offset', 0);

        $result = $this->notificationService->getUserNotifications(
            $request->validated('user_id'),
            $request->validated('user_type'),
            $limit,
            $offset
        );

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination']
        ]);
    }

    /**
     * Đánh dấu notification đã đọc
     */
    public function markAsRead(MarkAsReadRequest $request): JsonResponse
    {
        $result = $this->notificationService->markNotificationAsRead(
            $request->validated('user_notification_id')
        );

        return response()->json($result);
    }

    /**
     * Lấy trạng thái gửi thông báo
     */
    public function status($id): JsonResponse
    {
        $result = $this->notificationService->getNotificationStatus($id);
        
        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    /**
     * Publish Event API - Single API for all business events
     * 
     * Các service bên ngoài chỉ cần gọi 1 API này để publish event
     * Business logic sẽ được xử lý trong Handler tương ứng
     */
    public function publishEvent(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string',
            'payload' => 'required|array',
            'priority' => 'nullable|string|in:low,medium,high,critical',
            'key' => 'nullable|string'
        ]);

        try {
            // Chuẩn bị event data
            $eventData = array_merge($request->input('payload'), [
                'topic' => $request->input('topic'), 
                'priority' => $request->input('priority', 'medium'),
                'timestamp' => now()->toISOString()
            ]);

            // Publish lên Kafka
            $this->kafkaProducer->send(
                $request->input('topic'),
                $eventData,
                $request->input('key', $request->input('topic') . '_' . time())
            );

            return response()->json([
                'success' => true,
                'message' => 'Event published successfully',
                'data' => [
                    'event_type' => $request->input('topic'),
                    'event_id' => $request->input('key', $request->input('topic') . '_' . time()),
                    'timestamp' => $eventData['timestamp']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

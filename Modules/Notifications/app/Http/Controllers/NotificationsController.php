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
    public function userNotifications(Request $request): JsonResponse
    {
        // Lấy user info từ JWT middleware thay vì request parameters
        $userId = $request->attributes->get('jwt_user_id');
        $userType = $request->attributes->get('jwt_user_type');
        
        if (!$userId || !$userType) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin user không hợp lệ'
            ], 401);
        }

        // Lấy pagination parameters từ query string
        $limit = (int) $request->query('limit', 20);
        $offset = (int) $request->query('offset', 0);
        
        // Validate pagination params
        $limit = max(1, min(100, $limit)); // Between 1-100
        $offset = max(0, $offset); // >= 0

        $result = $this->notificationService->getUserNotifications(
            $userId,
            $userType,
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
    public function markAsRead(Request $request): JsonResponse
    {
        // Lấy user info từ JWT middleware
        $userId = $request->attributes->get('jwt_user_id');
        $userType = $request->attributes->get('jwt_user_type');
        
        if (!$userId || !$userType) {
            return response()->json([
                'success' => false,
                'message' => 'Thông tin user không hợp lệ'
            ], 401);
        }

        // Lấy notification IDs từ request body
        $notificationIds = $request->input('notification_ids', []);
        
        if (empty($notificationIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Không có thông báo nào được chọn'
            ], 400);
        }

        $results = [];
        $successCount = 0;
        
        foreach ($notificationIds as $notificationId) {
            // Tìm user_notification dựa trên notification_id và user info
            $result = $this->notificationService->markUserNotificationAsRead(
                $userId,
                $userType,
                (int) $notificationId
            );
            
            $results[] = $result;
            if ($result['success']) {
                $successCount++;
            }
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => $successCount === count($notificationIds) 
                ? 'Tất cả thông báo đã được đánh dấu đã đọc'
                : "Đã đánh dấu {$successCount}/{" . count($notificationIds) . "} thông báo",
            'results' => $results,
            'processed' => count($notificationIds),
            'success_count' => $successCount
        ]);
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

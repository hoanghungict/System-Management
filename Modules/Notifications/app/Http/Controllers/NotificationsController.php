<?php

namespace Modules\Notifications\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Modules\Notifications\app\Http\Requests\SendNotificationRequest;
use Modules\Notifications\app\Http\Requests\SendBulkNotificationRequest;
use Modules\Notifications\app\Http\Requests\ScheduleNotificationRequest;
use Modules\Notifications\app\Http\Requests\GetUserNotificationsRequest;
use Modules\Notifications\app\Http\Requests\MarkAsReadRequest;

class NotificationsController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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
}

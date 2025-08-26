<?php

namespace Modules\Notifications\app\Repositories\NotificationRepository;

use Modules\Notifications\app\Repositories\Interfaces\NotificationRepositoryInterface;
use Modules\Notifications\app\Models\Notification;
use Modules\Notifications\app\Models\NotificationTemplate;
use Modules\Notifications\app\Models\UserNotification;
use Illuminate\Support\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Tìm template theo tên
     */
    public function findTemplateByName(string $name): ?NotificationTemplate
    {
        return NotificationTemplate::active()->byName($name)->first();
    }

    /**
     * Lấy danh sách templates theo category
     */
    public function getTemplatesByCategory(string $category): Collection
    {
        return NotificationTemplate::active()->byCategory($category)->get();
    }

    /**
     * Tạo notification mới
     */
    public function createNotification(array $data): Notification
    {
        return Notification::create($data);
    }

    /**
     * Tạo user notification
     */
    public function createUserNotification(array $data): UserNotification
    {
        return UserNotification::create($data);
    }

    /**
     * Lấy notifications của user
     */
    public function getUserNotifications(int $userId, string $userType, int $limit = 20, int $offset = 0): Collection
    {
        return UserNotification::with('notification')
            ->byUser($userId, $userType)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Đánh dấu notification đã đọc
     */
    public function markNotificationAsRead(int $userNotificationId): bool
    {
        $userNotification = UserNotification::find($userNotificationId);
        
        if (!$userNotification) {
            return false;
        }

        $userNotification->markAsRead();
        return true;
    }

    /**
     * Lấy notifications pending
     */
    public function getPendingNotifications(): Collection
    {
        return Notification::pending()->get();
    }

    /**
     * Cập nhật trạng thái notification
     */
    public function updateNotificationStatus(int $notificationId, string $status): bool
    {
        $notification = Notification::find($notificationId);
        
        if (!$notification) {
            return false;
        }

        return $notification->update(['status' => $status]);
    }

    /**
     * Lấy notification theo ID
     */
    public function findNotificationById(int $id): ?Notification
    {
        return Notification::with('userNotifications')->find($id);
    }

    /**
     * Lấy user notification theo ID
     */
    public function findUserNotificationById(int $id): ?UserNotification
    {
        return UserNotification::find($id);
    }

    /**
     * Đếm notifications chưa đọc của user
     */
    public function countUnreadNotifications(int $userId, string $userType): int
    {
        return UserNotification::byUser($userId, $userType)
            ->unread()
            ->count();
    }
}

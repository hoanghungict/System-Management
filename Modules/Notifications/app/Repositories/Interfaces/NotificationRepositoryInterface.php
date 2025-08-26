<?php

namespace Modules\Notifications\app\Repositories\Interfaces;

use Modules\Notifications\app\Models\Notification;
use Modules\Notifications\app\Models\NotificationTemplate;
use Modules\Notifications\app\Models\UserNotification;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    /**
     * Tìm template theo tên
     */
    public function findTemplateByName(string $name): ?NotificationTemplate;

    /**
     * Lấy danh sách templates theo category
     */
    public function getTemplatesByCategory(string $category): Collection;

    /**
     * Tạo notification mới
     */
    public function createNotification(array $data): Notification;

    /**
     * Tạo user notification
     */
    public function createUserNotification(array $data): UserNotification;

    /**
     * Lấy notifications của user
     */
    public function getUserNotifications(int $userId, string $userType, int $limit = 20, int $offset = 0): Collection;

    /**
     * Đánh dấu notification đã đọc
     */
    public function markNotificationAsRead(int $userNotificationId): bool;

    /**
     * Lấy notifications pending
     */
    public function getPendingNotifications(): Collection;

    /**
     * Cập nhật trạng thái notification
     */
    public function updateNotificationStatus(int $notificationId, string $status): bool;

    /**
     * Lấy notification theo ID
     */
    public function findNotificationById(int $id): ?Notification;

    /**
     * Lấy user notification theo ID
     */
    public function findUserNotificationById(int $id): ?UserNotification;

    /**
     * Đếm notifications chưa đọc của user
     */
    public function countUnreadNotifications(int $userId, string $userType): int;
}

<?php

namespace Modules\Notifications\app\Services\EmailService;

interface EmailServiceInterface
{
    /**
     * Gửi email notification đơn giản
     */
    public function send(
        int $userId,
        string $userType,
        string $content,
        string $subject = 'Notification'
    ): bool;

    /**
     * Gửi email trực tiếp (không qua queue)
     */
    public function sendImmediate(
        int $userId,
        string $userType,
        string $content,
        string $subject = 'Notification'
    ): bool;

    /**
     * Gửi email với template
     */
    public function sendTemplateEmail(
        string $to,
        string $template,
        array $data,
        array $attachments = []
    ): bool;

    /**
     * Gửi email hàng loạt
     */
    public function sendBulkEmail(
        array $recipients,
        string $subject,
        string $content,
        array $attachments = []
    ): bool;

    /**
     * Gửi email thông báo với attachments
     */
    public function sendNotificationEmail(
        string $to,
        string $subject,
        string $content,
        array $attachments = []
    ): bool;

    /**
     * Kiểm tra kết nối email
     */
    public function testConnection(): bool;

    /**
     * Lấy email của user
     */
    public function getUserEmail(int $userId, string $userType): ?string;

    /**
     * Gửi email báo cáo (cho Task module)
     */
    public function sendReportEmail(array $emailData): bool;
}

<?php

namespace Modules\Task\app\Services\Interfaces;

use Modules\Task\app\DTOs\EmailReportDTO;

interface EmailServiceInterface
{
    /**
     * Gửi email báo cáo
     *
     * @param EmailReportDTO $emailDTO
     * @return bool
     */
    public function sendReportEmail(EmailReportDTO $emailDTO): bool;

    /**
     * Gửi email thông báo
     *
     * @param string $to
     * @param string $subject
     * @param string $content
     * @param array $attachments
     * @return bool
     */
    public function sendNotificationEmail(string $to, string $subject, string $content, array $attachments = []): bool;

    /**
     * Gửi email với template
     *
     * @param string $to
     * @param string $template
     * @param array $data
     * @param array $attachments
     * @return bool
     */
    public function sendTemplateEmail(string $to, string $template, array $data, array $attachments = []): bool;

    /**
     * Gửi email hàng loạt
     *
     * @param array $recipients
     * @param string $subject
     * @param string $content
     * @param array $attachments
     * @return bool
     */
    public function sendBulkEmail(array $recipients, string $subject, string $content, array $attachments = []): bool;

    /**
     * Kiểm tra kết nối email
     *
     * @return bool
     */
    public function testConnection(): bool;
}

<?php

namespace Modules\Task\app\Services\Interfaces;

use Modules\Task\app\DTOs\EmailReportDTO;

interface EmailServiceInterface
{
    /**
     * Gửi email báo cáo Task
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
}

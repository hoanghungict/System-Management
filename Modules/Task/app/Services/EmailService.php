<?php

namespace Modules\Task\app\Services;

use Modules\Notifications\app\Services\EmailService\EmailServiceInterface;
use Modules\Task\app\DTOs\EmailReportDTO;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Events\EmailSentEvent;
use Modules\Task\app\Events\EmailFailedEvent;

/**
 * Task EmailService - Facade sử dụng Notifications EmailService
 * Tuân theo Clean Architecture và Dependency Inversion Principle
 */
class EmailService
{
    private EmailServiceInterface $emailService;

    public function __construct(EmailServiceInterface $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Gửi email báo cáo Task
     *
     * @param EmailReportDTO $emailDTO
     * @return bool
     */
    public function sendReportEmail(EmailReportDTO $emailDTO): bool
    {
        try {
            Log::info('TaskEmailService: Sending report email', [
                'recipients_count' => count($emailDTO->recipients),
                'subject' => $emailDTO->subject,
                'template' => $emailDTO->template
            ]);

            // Validate DTO
            if (!$emailDTO->isValid()) {
                Log::error('TaskEmailService: Invalid email DTO');
                return false;
            }

            // Lấy danh sách email recipients
            $recipients = $emailDTO->getEmailRecipients();
            if (empty($recipients)) {
                Log::error('TaskEmailService: No valid email recipients');
                return false;
            }

            // Chuyển đổi DTO thành array để sử dụng với Notifications EmailService
            $emailData = [
                'recipients' => $recipients,
                'subject' => $emailDTO->subject,
                'content' => $emailDTO->content,
                'template' => $emailDTO->template,
                'data' => $emailDTO->reportData,
                'attachments' => $emailDTO->attachments
            ];

            // Sử dụng Notifications EmailService
            $sent = $this->emailService->sendReportEmail($emailData);

            if ($sent) {
                // Dispatch event thành công
                EmailSentEvent::dispatch($emailDTO);
                
                Log::info('TaskEmailService: Report email sent successfully', [
                    'recipients_count' => count($recipients)
                ]);
            }

            return $sent;

        } catch (\Exception $e) {
            Log::error('TaskEmailService: Failed to send report email', [
                'error' => $e->getMessage(),
                'recipients' => $emailDTO->recipients ?? []
            ]);

            // Dispatch event thất bại
            EmailFailedEvent::dispatch($emailDTO, $e->getMessage());

            return false;
        }
    }

    /**
     * Delegate method cần thiết đến Notifications EmailService
     * Chỉ giữ lại những method thực sự được sử dụng trong Task module
     */
    
    public function sendNotificationEmail(string $to, string $subject, string $content, array $attachments = []): bool
    {
        return $this->emailService->sendNotificationEmail($to, $subject, $content, $attachments);
    }
}

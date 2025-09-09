<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Services\Interfaces\EmailServiceInterface;
use Modules\Task\app\DTOs\EmailReportDTO;
use Modules\Task\app\Repositories\Interfaces\EmailRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Modules\Task\app\Jobs\SendEmailJob;
use Modules\Task\app\Events\EmailSentEvent;
use Modules\Task\app\Events\EmailFailedEvent;

class EmailService implements EmailServiceInterface
{
    private EmailRepositoryInterface $emailRepository;

    public function __construct(EmailRepositoryInterface $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    /**
     * Gửi email báo cáo
     *
     * @param EmailReportDTO $emailDTO
     * @return bool
     */
    public function sendReportEmail(EmailReportDTO $emailDTO): bool
    {
        try {
            Log::info('EmailService: Sending report email', [
                'recipients_count' => count($emailDTO->recipients),
                'subject' => $emailDTO->subject,
                'template' => $emailDTO->template
            ]);

            // Validate DTO
            if (!$emailDTO->isValid()) {
                Log::error('EmailService: Invalid email DTO');
                return false;
            }

            // Lấy danh sách email recipients
            $recipients = $emailDTO->getEmailRecipients();
            if (empty($recipients)) {
                Log::error('EmailService: No valid email recipients');
                return false;
            }

            // Gửi email qua queue để xử lý background
            $sent = $this->sendEmailViaQueue($emailDTO);

            if ($sent) {
                // Dispatch event thành công
                EmailSentEvent::dispatch($emailDTO);
                
                Log::info('EmailService: Report email queued successfully', [
                    'recipients_count' => count($recipients)
                ]);
            }

            return $sent;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send report email', [
                'error' => $e->getMessage(),
                'recipients' => $emailDTO->recipients ?? []
            ]);

            // Dispatch event thất bại
            EmailFailedEvent::dispatch($emailDTO, $e->getMessage());

            return false;
        }
    }

    /**
     * Gửi email thông báo
     *
     * @param string $to
     * @param string $subject
     * @param string $content
     * @param array $attachments
     * @return bool
     */
    public function sendNotificationEmail(string $to, string $subject, string $content, array $attachments = []): bool
    {
        try {
            Log::info('EmailService: Sending notification email', [
                'to' => $to,
                'subject' => $subject
            ]);

            // Validate email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                Log::error('EmailService: Invalid email address', ['email' => $to]);
                return false;
            }

            // Gửi email trực tiếp
            Mail::raw($content, function ($message) use ($to, $subject, $attachments) {
                $message->to($to)
                        ->subject($subject);

                // Thêm attachments nếu có
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path'])
                        ]);
                    }
                }
            });

            Log::info('EmailService: Notification email sent successfully', ['to' => $to]);
            return true;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send notification email', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gửi email với template
     *
     * @param string $to
     * @param string $template
     * @param array $data
     * @param array $attachments
     * @return bool
     */
    public function sendTemplateEmail(string $to, string $template, array $data, array $attachments = []): bool
    {
        try {
            Log::info('EmailService: Sending template email', [
                'to' => $to,
                'template' => $template
            ]);

            // Validate email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                Log::error('EmailService: Invalid email address', ['email' => $to]);
                return false;
            }

            // Gửi email với template
            Mail::send($template, $data, function ($message) use ($to, $data, $attachments) {
                $message->to($to)
                        ->subject($data['subject'] ?? 'Notification');

                // Thêm attachments nếu có
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path'])
                        ]);
                    }
                }
            });

            Log::info('EmailService: Template email sent successfully', ['to' => $to]);
            return true;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send template email', [
                'to' => $to,
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gửi email hàng loạt
     *
     * @param array $recipients
     * @param string $subject
     * @param string $content
     * @param array $attachments
     * @return bool
     */
    public function sendBulkEmail(array $recipients, string $subject, string $content, array $attachments = []): bool
    {
        try {
            Log::info('EmailService: Sending bulk email', [
                'recipients_count' => count($recipients)
            ]);

            $successCount = 0;
            $failedCount = 0;

            foreach ($recipients as $recipient) {
                $email = is_string($recipient) ? $recipient : ($recipient['email'] ?? null);
                
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $sent = $this->sendNotificationEmail($email, $subject, $content, $attachments);
                    if ($sent) {
                        $successCount++;
                    } else {
                        $failedCount++;
                    }
                } else {
                    $failedCount++;
                    Log::warning('EmailService: Invalid recipient email', ['recipient' => $recipient]);
                }
            }

            Log::info('EmailService: Bulk email completed', [
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'total_count' => count($recipients)
            ]);

            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send bulk email', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Kiểm tra kết nối email
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            // Kiểm tra cấu hình email
            $config = Config::get('mail');
            if (empty($config)) {
                Log::error('EmailService: Mail configuration not found');
                return false;
            }

            // Test connection bằng cách gửi email test
            $testEmail = Config::get('mail.test_email', 'test@example.com');
            
            Mail::raw('Test email connection', function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Test Connection');
            });

            Log::info('EmailService: Connection test successful');
            return true;

        } catch (\Exception $e) {
            Log::error('EmailService: Connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gửi email qua queue
     *
     * @param EmailReportDTO $emailDTO
     * @return bool
     */
    private function sendEmailViaQueue(EmailReportDTO $emailDTO): bool
    {
        try {
            // Dispatch job để gửi email
            SendEmailJob::dispatch($emailDTO)
                ->onQueue('emails')
                ->delay(now()->addSeconds(5));

            return true;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to queue email', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lưu log email
     *
     * @param EmailReportDTO $emailDTO
     * @param bool $success
     * @param string|null $error
     * @return void
     */
    private function logEmailActivity(EmailReportDTO $emailDTO, bool $success, ?string $error = null): void
    {
        try {
            $this->emailRepository->logEmailActivity([
                'recipients' => $emailDTO->recipients,
                'subject' => $emailDTO->subject,
                'template' => $emailDTO->template,
                'success' => $success,
                'error' => $error,
                'sent_at' => now(),
                'metadata' => $emailDTO->metadata
            ]);
        } catch (\Exception $e) {
            Log::error('EmailService: Failed to log email activity', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

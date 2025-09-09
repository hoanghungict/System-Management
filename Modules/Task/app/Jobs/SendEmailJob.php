<?php

namespace Modules\Task\app\Jobs;

use Modules\Task\app\DTOs\EmailReportDTO;
use Modules\Task\app\Services\Interfaces\EmailServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 3;

    private EmailReportDTO $emailDTO;

    public function __construct(EmailReportDTO $emailDTO)
    {
        $this->emailDTO = $emailDTO;
        $this->onQueue('emails');
    }

    /**
     * Thực thi job
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            Log::info('SendEmailJob: Starting email job', [
                'recipients_count' => count($this->emailDTO->recipients),
                'subject' => $this->emailDTO->subject,
                'template' => $this->emailDTO->template
            ]);

            // Validate DTO
            if (!$this->emailDTO->isValid()) {
                throw new \Exception('Invalid email DTO');
            }

            // Lấy danh sách email recipients
            $recipients = $this->emailDTO->getEmailRecipients();
            if (empty($recipients)) {
                throw new \Exception('No valid email recipients');
            }

            // Gửi email cho từng recipient
            $successCount = 0;
            $failedCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    $sent = $this->sendEmailToRecipient($recipient);
                    if ($sent) {
                        $successCount++;
                        Log::info('SendEmailJob: Email sent successfully', [
                            'recipient' => $recipient
                        ]);
                    } else {
                        $failedCount++;
                        Log::warning('SendEmailJob: Failed to send email', [
                            'recipient' => $recipient
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('SendEmailJob: Error sending email to recipient', [
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('SendEmailJob: Email job completed', [
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'total_count' => count($recipients)
            ]);

        } catch (\Exception $e) {
            Log::error('SendEmailJob: Job failed', [
                'error' => $e->getMessage(),
                'recipients' => $this->emailDTO->recipients ?? []
            ]);

            throw $e;
        }
    }

    /**
     * Gửi email đến một recipient cụ thể
     *
     * @param string $recipient
     * @return bool
     */
    private function sendEmailToRecipient(string $recipient): bool
    {
        try {
            // Validate email
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                Log::warning('SendEmailJob: Invalid email address', ['email' => $recipient]);
                return false;
            }

            // Gửi email với template nếu có
            if ($this->emailDTO->template && $this->emailDTO->template !== 'emails.reports.default') {
                return $this->sendTemplateEmail($recipient);
            } else {
                return $this->sendRawEmail($recipient);
            }

        } catch (\Exception $e) {
            Log::error('SendEmailJob: Failed to send email to recipient', [
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gửi email với template
     *
     * @param string $recipient
     * @return bool
     */
    private function sendTemplateEmail(string $recipient): bool
    {
        try {
            Mail::send($this->emailDTO->template, $this->emailDTO->reportData, function ($message) use ($recipient) {
                $message->to($recipient)
                        ->subject($this->emailDTO->subject);

                // Thêm attachments nếu có
                foreach ($this->emailDTO->attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path'])
                        ]);
                    }
                }
            });

            return true;

        } catch (\Exception $e) {
            Log::error('SendEmailJob: Failed to send template email', [
                'recipient' => $recipient,
                'template' => $this->emailDTO->template,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gửi email raw content
     *
     * @param string $recipient
     * @return bool
     */
    private function sendRawEmail(string $recipient): bool
    {
        try {
            Mail::raw($this->emailDTO->content, function ($message) use ($recipient) {
                $message->to($recipient)
                        ->subject($this->emailDTO->subject);

                // Thêm attachments nếu có
                foreach ($this->emailDTO->attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path'])
                        ]);
                    }
                }
            });

            return true;

        } catch (\Exception $e) {
            Log::error('SendEmailJob: Failed to send raw email', [
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Xử lý khi job fail
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendEmailJob: Job permanently failed', [
            'recipients_count' => count($this->emailDTO->recipients),
            'subject' => $this->emailDTO->subject,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Có thể gửi notification cho admin về job fail
        // Hoặc retry với delay khác
    }

    /**
     * Retry job với delay tăng dần
     *
     * @param \Throwable $exception
     * @return void
     */
    public function retryAfter(\Throwable $exception): void
    {
        $this->release(now()->addMinutes(pow(2, $this->attempts())));
    }
}

<?php

namespace Modules\Task\app\Jobs;

use Modules\Task\app\DTOs\EmailReportDTO;
use Modules\Notifications\app\Services\EmailService\EmailServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
     * Thực thi job sử dụng Notifications EmailService
     *
     * @param EmailServiceInterface $emailService
     * @return void
     */
    public function handle(EmailServiceInterface $emailService): void
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

            // Chuyển đổi DTO thành array để sử dụng với Notifications EmailService
            $emailData = [
                'recipients' => $recipients,
                'subject' => $this->emailDTO->subject,
                'content' => $this->emailDTO->content,
                'template' => $this->emailDTO->template,
                'data' => $this->emailDTO->reportData,
                'attachments' => $this->emailDTO->attachments
            ];

            // Sử dụng Notifications EmailService để gửi email
            $sent = $emailService->sendReportEmail($emailData);

            if ($sent) {
                Log::info('SendEmailJob: Email sent successfully', [
                    'recipients_count' => count($recipients)
                ]);
            } else {
                Log::warning('SendEmailJob: Failed to send email');
            }

        } catch (\Exception $e) {
            Log::error('SendEmailJob: Job failed', [
                'error' => $e->getMessage(),
                'recipients' => $this->emailDTO->recipients ?? []
            ]);

            throw $e;
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

<?php

namespace Modules\Task\app\Listeners;

use Modules\Task\app\Events\EmailSentEvent;
use Modules\Task\app\Events\EmailFailedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmailEventListener
{
    /**
     * Xử lý email sent event
     *
     * @param EmailSentEvent $event
     * @return void
     */
    public function handleEmailSent(EmailSentEvent $event): void
    {
        try {
            /* Log::info('EmailEventListener: Email sent event handled', [
                'recipients_count' => count($event->emailDTO->recipients),
                'subject' => $event->emailDTO->subject,
                'template' => $event->emailDTO->template
            ]); */

            // Track email metrics
            $this->trackEmailMetrics('sent', $event->emailDTO);

            // Có thể gửi notification cho admin hoặc log vào hệ thống monitoring
            $this->notifyEmailSuccess($event->emailDTO);

        } catch (\Exception $e) {
            Log::error('EmailEventListener: Failed to handle email sent event', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Xử lý email failed event
     *
     * @param EmailFailedEvent $event
     * @return void
     */
    public function handleEmailFailed(EmailFailedEvent $event): void
    {
        try {
            Log::error('EmailEventListener: Email failed event handled', [
                'recipients_count' => count($event->emailDTO->recipients),
                'subject' => $event->emailDTO->subject,
                'error_message' => $event->errorMessage
            ]);

            // Track email metrics
            $this->trackEmailMetrics('failed', $event->emailDTO);

            // Có thể gửi alert cho admin hoặc retry logic
            $this->notifyEmailFailure($event->emailDTO, $event->errorMessage);

        } catch (\Exception $e) {
            Log::error('EmailEventListener: Failed to handle email failed event', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Track email metrics
     *
     * @param string $status
     * @param mixed $emailDTO
     * @return void
     */
    private function trackEmailMetrics(string $status, mixed $emailDTO): void
    {
        try {
            $date = now()->format('Y-m-d');
            $hour = now()->format('Y-m-d-H');

            // Track daily metrics
            $dailyKey = "email_metrics:daily:{$date}";
            $this->incrementMetric($dailyKey, "{$status}_count");
            $this->incrementMetric($dailyKey, 'total_count');

            // Track hourly metrics
            $hourlyKey = "email_metrics:hourly:{$hour}";
            $this->incrementMetric($hourlyKey, "{$status}_count");
            $this->incrementMetric($hourlyKey, 'total_count');

            // Track template metrics
            $templateKey = "email_metrics:template:{$emailDTO->template}";
            $this->incrementMetric($templateKey, "{$status}_count");
            $this->incrementMetric($templateKey, 'total_count');

            // Track success rate
            $this->updateSuccessRate($dailyKey);
            $this->updateSuccessRate($hourlyKey);
            $this->updateSuccessRate($templateKey);

        } catch (\Exception $e) {
            Log::error('EmailEventListener: Failed to track email metrics', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Increment metric counter
     *
     * @param string $key
     * @param string $field
     * @return void
     */
    private function incrementMetric(string $key, string $field): void
    {
        $metrics = Cache::get($key, []);
        $metrics[$field] = ($metrics[$field] ?? 0) + 1;
        Cache::put($key, $metrics, now()->addDays(30));
    }

    /**
     * Update success rate
     *
     * @param string $key
     * @return void
     */
    private function updateSuccessRate(string $key): void
    {
        $metrics = Cache::get($key, []);
        
        if (isset($metrics['total_count']) && $metrics['total_count'] > 0) {
            $successCount = $metrics['sent_count'] ?? 0;
            $metrics['success_rate'] = round(($successCount / $metrics['total_count']) * 100, 2);
            Cache::put($key, $metrics, now()->addDays(30));
        }
    }

    /**
     * Notify email success
     *
     * @param mixed $emailDTO
     * @return void
     */
    private function notifyEmailSuccess(mixed $emailDTO): void
    {
        // Có thể gửi notification cho admin hoặc log vào hệ thống monitoring
        // Ví dụ: Slack notification, email to admin, etc.
        
        /* Log::info('EmailEventListener: Email success notification', [
            'recipients_count' => count($emailDTO->recipients),
            'subject' => $emailDTO->subject
        ]); */
    }

    /**
     * Notify email failure
     *
     * @param mixed $emailDTO
     * @param string $errorMessage
     * @return void
     */
    private function notifyEmailFailure(mixed $emailDTO, string $errorMessage): void
    {
        // Có thể gửi alert cho admin hoặc retry logic
        // Ví dụ: Slack alert, email to admin, retry queue, etc.
        
        Log::error('EmailEventListener: Email failure notification', [
            'recipients_count' => count($emailDTO->recipients),
            'subject' => $emailDTO->subject,
            'error_message' => $errorMessage
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Send Reminder Notification Job
 * 
 * Background job để gửi reminder notifications
 */
class SendReminderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $reminderId,
        private readonly string $templateName,
        private readonly array $recipients,
        private readonly array $templateData,
        private readonly array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            Log::info('SendReminderNotificationJob: Starting job', [
                'reminder_id' => $this->reminderId,
                'template' => $this->templateName,
                'recipients_count' => count($this->recipients)
            ]);

            // Send notification
            $result = $notificationService->sendNotification(
                $this->templateName,
                $this->recipients,
                $this->templateData,
                $this->options
            );

            if ($result['success']) {
                Log::info('SendReminderNotificationJob: Notification sent successfully', [
                    'reminder_id' => $this->reminderId,
                    'notification_id' => $result['notification_id']
                ]);
            } else {
                Log::error('SendReminderNotificationJob: Failed to send notification', [
                    'reminder_id' => $this->reminderId,
                    'error' => $result['error']
                ]);
                
                // Mark job as failed
                $this->fail(new \Exception($result['error']));
            }

        } catch (\Exception $e) {
            Log::error('SendReminderNotificationJob: Job failed', [
                'reminder_id' => $this->reminderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendReminderNotificationJob: Job permanently failed', [
            'reminder_id' => $this->reminderId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // TODO: Mark reminder as failed in database
        // This could be done via a separate service or direct database update
    }
}

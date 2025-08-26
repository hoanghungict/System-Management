<?php

namespace Modules\Notifications\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Services\EmailService\EmailService;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        private int $userId,
        private string $userType,
        private string $content,
        private string $subject
    ) {
        $this->onQueue('emails');
    }

    public function handle(EmailService $emailService): void
    {
        try {
            $emailService->sendImmediate(
                $this->userId,
                $this->userType,
                $this->content,
                $this->subject
            );
        } catch (\Exception $e) {
            Log::error('Email notification job failed', [
                'user_id' => $this->userId,
                'user_type' => $this->userType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Email notification job permanently failed', [
            'user_id' => $this->userId,
            'user_type' => $this->userType,
            'error' => $exception->getMessage()
        ]);
    }
}

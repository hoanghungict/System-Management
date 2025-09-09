<?php

namespace Modules\Task\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Task\app\Services\EmailService;
use Modules\Notifications\app\Services\EmailService\EmailServiceInterface as NotificationsEmailServiceInterface;
use Modules\Notifications\app\Services\EmailService\EmailService as NotificationsEmailService;
use Modules\Task\app\Listeners\EmailEventListener;
use Modules\Task\app\Events\EmailSentEvent;
use Modules\Task\app\Events\EmailFailedEvent;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Notifications EmailService
        $this->app->bind(NotificationsEmailServiceInterface::class, NotificationsEmailService::class);
        
        // Bind Task EmailService sử dụng Notifications EmailService
        $this->app->bind(EmailService::class, function ($app) {
            return new EmailService($app->make(NotificationsEmailServiceInterface::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerEventListeners();
        $this->loadEmailConfiguration();
    }

    /**
     * Đăng ký event listeners
     */
    private function registerEventListeners(): void
    {
        $events = $this->app['events'];

        // Email sent event
        $events->listen(EmailSentEvent::class, [EmailEventListener::class, 'handleEmailSent']);

        // Email failed event
        $events->listen(EmailFailedEvent::class, [EmailEventListener::class, 'handleEmailFailed']);
    }

    /**
     * Load email configuration
     */
    private function loadEmailConfiguration(): void
    {
        $configPath = __DIR__ . '/../../config/email.php';
        
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'task.email');
        }
    }
}

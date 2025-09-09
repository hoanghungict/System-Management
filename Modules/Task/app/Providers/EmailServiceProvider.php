<?php

namespace Modules\Task\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Task\app\Services\Interfaces\EmailServiceInterface;
use Modules\Task\app\Services\EmailService;
use Modules\Task\app\Repositories\Interfaces\EmailRepositoryInterface;
use Modules\Task\app\Repositories\EmailRepository;
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
        // Bind interfaces to implementations
        $this->app->bind(EmailRepositoryInterface::class, EmailRepository::class);
        $this->app->bind(EmailServiceInterface::class, EmailService::class);

        // Singleton for EmailService
        $this->app->singleton(EmailServiceInterface::class, function ($app) {
            return new EmailService(
                $app->make(EmailRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerEventListeners();
        $this->loadEmailConfiguration();
        $this->registerCommands();
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

    /**
     * Register commands
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Có thể thêm commands ở đây nếu cần
            ]);
        }
    }
}

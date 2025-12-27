<?php

namespace Modules\Task\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Modules\Task\app\Repositories\TaskRepository;
use Modules\Task\app\Calendar\Contracts\CalendarRepositoryInterface;
use Modules\Task\app\Calendar\Repositories\CalendarRepository;
use Modules\Task\app\Calendar\Services\CalendarService;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\TaskService;
use Modules\Task\app\Services\Interfaces\EmailServiceInterface;
use Modules\Task\app\Services\EmailService;
use Modules\Task\app\Services\Interfaces\CacheServiceInterface;
use Modules\Task\app\Services\CacheService;
use Modules\Task\app\Cache\Contracts\CacheInvalidationInterface;
use Modules\Task\app\Services\CacheInvalidationService;
use Modules\Task\app\File\Contracts\FileRepositoryInterface;
use Modules\Task\app\File\Repositories\FileRepository;
use Modules\Task\app\Services\FileService;
use Modules\Task\app\Services\UserContextService;
use Modules\Task\app\Admin\Services\AdminTaskService;
use Modules\Task\app\UseCases\CreateTaskUseCase;
use Modules\Task\app\UseCases\CreateTaskWithPermissionsUseCase;
use Modules\Task\app\Admin\Providers\AdminServiceProvider;

use Modules\Task\app\Repositories\Interfaces\ReminderRepositoryInterface;
use Modules\Task\app\Repositories\ReminderRepository;
use Modules\Task\app\Services\ReminderService;
use Modules\Task\app\Services\ReportService;
use Modules\Task\app\Console\Commands\ProcessRemindersCommand;

/**
 * Service Provider cho Task Module
 * 
 * Tuân thủ Clean Architecture: Dependency Injection Container
 * Bind interfaces với concrete implementations
 */
class TaskServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind TaskRepository interface với implementation
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);

        // Bind Calendar Services
        $this->app->bind(CalendarRepositoryInterface::class, CalendarRepository::class);
        $this->app->bind(CalendarService::class, CalendarService::class);

        // Bind TaskService interface với implementation
        $this->app->bind(TaskServiceInterface::class, TaskService::class);

        // Bind Calendar Services
        $this->app->bind(CalendarRepositoryInterface::class, CalendarRepository::class);
        $this->app->bind(CalendarService::class, CalendarService::class);

        // Bind TaskService interface với implementation
        $this->app->bind(TaskServiceInterface::class, TaskService::class);

        // Bind EmailService interface với implementation
        $this->app->bind(EmailServiceInterface::class, EmailService::class);

        // Bind CacheService
        $this->app->singleton(CacheServiceInterface::class, CacheService::class);

        // Bind Cache Services
        $this->app->bind(CacheInvalidationInterface::class, CacheInvalidationService::class);

        // Bind File Services
        $this->app->bind(FileRepositoryInterface::class, FileRepository::class);
        $this->app->bind(FileService::class, FileService::class);

        // Bind Admin Services
        $this->app->bind(AdminTaskService::class, AdminTaskService::class);
        $this->app->bind(UserContextService::class, UserContextService::class);

        // Bind Use Cases
        $this->app->bind(CreateTaskUseCase::class, CreateTaskUseCase::class);
        $this->app->bind(CreateTaskWithPermissionsUseCase::class, CreateTaskWithPermissionsUseCase::class);


        // Bind Admin Use Cases
        $this->app->bind(\Modules\Task\app\Admin\UseCases\ForceDeleteTaskUseCase::class, \Modules\Task\app\Admin\UseCases\ForceDeleteTaskUseCase::class);
        $this->app->bind(\Modules\Task\app\Admin\UseCases\RestoreTaskUseCase::class, \Modules\Task\app\Admin\UseCases\RestoreTaskUseCase::class);
        $this->app->bind(\Modules\Task\app\Admin\UseCases\AssignTaskToLecturersUseCase::class, \Modules\Task\app\Admin\UseCases\AssignTaskToLecturersUseCase::class);
        $this->app->bind(\Modules\Task\app\Admin\UseCases\GetAssignedTasksUseCase::class, \Modules\Task\app\Admin\UseCases\GetAssignedTasksUseCase::class);
        $this->app->bind(\Modules\Task\app\Admin\UseCases\GetTaskDetailUseCase::class, \Modules\Task\app\Admin\UseCases\GetTaskDetailUseCase::class);
        $this->app->bind(\Modules\Task\app\Admin\UseCases\ShowTaskUseCase::class, \Modules\Task\app\Admin\UseCases\ShowTaskUseCase::class);
        $this->app->bind(\Modules\Task\app\Admin\UseCases\CheckAdminRoleUseCase::class, \Modules\Task\app\Admin\UseCases\CheckAdminRoleUseCase::class);

        // Bind Lecturer-specific Use Cases (only existing ones)
        $this->app->bind(\Modules\Task\app\Lecturer\UseCases\LecturerTaskUseCase::class, \Modules\Task\app\Lecturer\UseCases\LecturerTaskUseCase::class);
        $this->app->bind(\Modules\Task\app\Lecturer\UseCases\UpdateTaskUseCase::class, \Modules\Task\app\Lecturer\UseCases\UpdateTaskUseCase::class);
        $this->app->bind(\Modules\Task\app\Lecturer\UseCases\AssignTaskUseCase::class, \Modules\Task\app\Lecturer\UseCases\AssignTaskUseCase::class);
        $this->app->bind(\Modules\Task\app\Lecturer\UseCases\RevokeTaskUseCase::class, \Modules\Task\app\Lecturer\UseCases\RevokeTaskUseCase::class);

        // Bind Lecturer Repository
        $this->app->bind(\Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository::class, \Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository::class);



        // Bind Reminder Services
        $this->app->bind(ReminderRepositoryInterface::class, ReminderRepository::class);
        $this->app->bind(ReminderService::class, ReminderService::class);

        // Bind Report Services
        $this->app->bind(ReportService::class, ReportService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes (check if file exists)
        $routesPath = __DIR__ . '/../../routes/api.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Load views (check if directory exists)
        $viewsPath = __DIR__ . '/../resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'task');
        }

        // Load migrations (check if directory exists)
        $migrationsPath = __DIR__ . '/../database/migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // Load translations (check if directory exists)
        $langPath = __DIR__ . '/../resources/lang';
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'task');
        }

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessRemindersCommand::class,
            ]);
        }

        // Publish config (check if file exists)
        $configPath = __DIR__ . '/../config/task.php';
        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path('task.php'),
            ], 'task-config');
        }

        // Register Admin Service Provider (check if class exists)
        if (class_exists(AdminServiceProvider::class)) {
            $this->app->register(AdminServiceProvider::class);
        }

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Task\app\Console\Commands\TestEmailSystemCommand::class,
            ]);
        }
    }
}
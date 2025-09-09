<?php

namespace Modules\Task\app\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Task\app\Admin\UseCases\ForceDeleteTaskUseCase;
use Modules\Task\app\Admin\UseCases\RestoreTaskUseCase;
use Modules\Task\app\Admin\UseCases\AssignTaskToLecturersUseCase;
use Modules\Task\app\Admin\UseCases\GetAssignedTasksUseCase;
use Modules\Task\app\Admin\UseCases\GetTaskDetailUseCase;
use Modules\Task\app\Admin\UseCases\CheckAdminRoleUseCase;
use Modules\Task\app\Admin\Services\AdminTaskService;
use Modules\Task\app\Services\PermissionService;

/**
 * Admin Service Provider
 * 
 * Registers admin-specific services and use cases
 * Following Clean Architecture principles
 */
class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register Admin Services
        $this->app->singleton(AdminTaskService::class, function ($app) {
            return new AdminTaskService(
                $app->make(PermissionService::class)
            );
        });

        // Register Admin Use Cases
        $this->app->singleton(ForceDeleteTaskUseCase::class, function ($app) {
            return new ForceDeleteTaskUseCase(
                $app->make(PermissionService::class)
            );
        });

        $this->app->singleton(RestoreTaskUseCase::class, function ($app) {
            return new RestoreTaskUseCase(
                $app->make(PermissionService::class)
            );
        });

        $this->app->singleton(AssignTaskToLecturersUseCase::class, function ($app) {
            return new AssignTaskToLecturersUseCase(
                $app->make(PermissionService::class)
            );
        });

        $this->app->singleton(GetAssignedTasksUseCase::class, function ($app) {
            return new GetAssignedTasksUseCase(
                $app->make(PermissionService::class)
            );
        });

        $this->app->singleton(GetTaskDetailUseCase::class, function ($app) {
            return new GetTaskDetailUseCase(
                $app->make(PermissionService::class)
            );
        });

        $this->app->singleton(CheckAdminRoleUseCase::class, function ($app) {
            return new CheckAdminRoleUseCase(
                $app->make(PermissionService::class)
            );
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Boot admin-specific configurations if needed
    }
}

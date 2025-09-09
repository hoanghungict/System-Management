<?php

namespace Modules\Task\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Route Service Provider của module Task
 * 
 * Provider này đăng ký các routes cho module Task
 * Tuân thủ Clean Architecture: chỉ đăng ký routes, không chứa business logic
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * Tên module
     */
    protected string $name = 'Task';

    /**
     * Được gọi trước khi routes được đăng ký
     *
     * Đăng ký các model bindings hoặc pattern based filters
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Định nghĩa routes cho application
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Định nghĩa "web" routes cho application
     *
     * Các routes này nhận session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Định nghĩa "api" routes cho application
     *
     * Các routes này thường là stateless
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }
}

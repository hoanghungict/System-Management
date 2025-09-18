<?php

namespace Modules\Task\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Event Service Provider của module Task
 * 
 * Provider này đăng ký các event listeners cho module Task
 * Tuân thủ Clean Architecture: chỉ đăng ký event mappings, không chứa business logic
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Mapping giữa events và listeners cho application
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Cho biết có nên tự động discover events không
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Cấu hình event listeners cho email verification
     */
    protected function configureEmailVerification(): void {}
}

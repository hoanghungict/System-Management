<?php

namespace Modules\Task\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Task\app\Repositories\Interfaces\RedisCacheRepositoryInterface;
use Modules\Task\app\Repositories\RedisCacheRepository;
use Modules\Task\app\Services\Interfaces\RedisCacheServiceInterface;
use Modules\Task\app\Services\RedisCacheService;
use Modules\Task\app\Services\Interfaces\CacheServiceInterface;
use Modules\Task\app\Services\CacheService;
use Modules\Task\app\Listeners\CacheEventListener;
use Modules\Task\app\Events\CacheCreatedEvent;
use Modules\Task\app\Events\CacheDeletedEvent;
use Modules\Task\app\Events\CacheHitEvent;
use Modules\Task\app\Events\CacheMissedEvent;
use Modules\Task\app\Events\CacheInvalidatedEvent;


/**
 * Cache Service Provider
 * 
 * Tuân thủ Clean Architecture: Service provider đăng ký cache dependencies
 * Infrastructure Layer - xử lý dependency injection và service registration
 */
class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services
     * 
     * @return void
     */
    public function register(): void
    {
        // ✅ Register Redis Cache Repository
        $this->app->bind(RedisCacheRepositoryInterface::class, RedisCacheRepository::class);

        // ✅ Register Redis Cache Service
        $this->app->bind(RedisCacheServiceInterface::class, RedisCacheService::class);

        // ✅ Register Legacy Cache Service (for backward compatibility)
        $this->app->bind(CacheServiceInterface::class, CacheService::class);

        // ✅ Register Cache Service as singleton for better performance
        $this->app->singleton(RedisCacheServiceInterface::class, function ($app) {
            return new RedisCacheService(
                $app->make(RedisCacheRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap services
     * 
     * @return void
     */
    public function boot(): void
    {
        // ✅ Register cache event listeners
        $this->registerEventListeners();

        // ✅ Load cache configuration
        $this->loadCacheConfiguration();

        // ✅ Register cache commands if needed
        $this->registerCommands();
    }

    /**
     * Register event listeners
     * 
     * @return void
     */
    private function registerEventListeners(): void
    {
        $listener = $this->app->make(CacheEventListener::class);

        // ✅ Register cache event listeners
        $this->app['events']->listen(CacheCreatedEvent::class, [
            $listener,
            'handleCacheCreated'
        ]);

        $this->app['events']->listen(CacheDeletedEvent::class, [
            $listener,
            'handleCacheDeleted'
        ]);

        $this->app['events']->listen(CacheHitEvent::class, [
            $listener,
            'handleCacheHit'
        ]);

        $this->app['events']->listen(CacheMissedEvent::class, [
            $listener,
            'handleCacheMissed'
        ]);

        $this->app['events']->listen(CacheInvalidatedEvent::class, [
            $listener,
            'handleCacheInvalidated'
        ]);
    }

    /**
     * Load cache configuration
     * 
     * @return void
     */
    private function loadCacheConfiguration(): void
    {
        // ✅ Merge module cache configuration with main config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/cache.php',
            'cache'
        );

        // ✅ Publish cache configuration if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/cache.php' => config_path('cache.php'),
            ], 'task-cache-config');
        }
    }

    /**
     * Register cache commands
     * 
     * @return void
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // ✅ Register cache-related commands if needed
            // $this->commands([
            //     \Modules\Task\app\Console\Commands\CacheWarmUpCommand::class,
            //     \Modules\Task\app\Console\Commands\CacheClearCommand::class,
            // ]);
        }
    }

    /**
     * Get the services provided by the provider
     * 
     * @return array
     */
    public function provides(): array
    {
        return [
            RedisCacheRepositoryInterface::class,
            RedisCacheServiceInterface::class,
            CacheServiceInterface::class,
        ];
    }
}
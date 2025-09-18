<?php

namespace Modules\Task\app\Listeners;

use Modules\Task\app\Events\CacheEvent;
use Modules\Task\app\Events\CacheCreatedEvent;
use Modules\Task\app\Events\CacheDeletedEvent;
use Modules\Task\app\Events\CacheHitEvent;
use Modules\Task\app\Events\CacheMissedEvent;
use Modules\Task\app\Events\CacheInvalidatedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Cache Event Listener
 * 
 * Tuân thủ Clean Architecture: Listener xử lý cache events
 * Infrastructure Layer - xử lý events và side effects
 */
class CacheEventListener
{
    /**
     * Handle cache created event
     * 
     * @param CacheCreatedEvent $event
     * @return void
     */
    public function handleCacheCreated(CacheCreatedEvent $event): void
    {
        Log::info('Cache created', $event->getEventData());
        
        // ✅ Business logic: Track cache creation metrics
        $this->trackCacheCreation($event->key, $event->metadata);
        
        // ✅ Business logic: Notify monitoring systems
        $this->notifyMonitoringSystem($event);
    }

    /**
     * Handle cache deleted event
     * 
     * @param CacheDeletedEvent $event
     * @return void
     */
    public function handleCacheDeleted(CacheDeletedEvent $event): void
    {
        Log::info('Cache deleted', $event->getEventData());
        
        // ✅ Business logic: Track cache deletion metrics
        $this->trackCacheDeletion($event->key, $event->metadata);
        
        // ✅ Business logic: Update cache statistics
        $this->updateCacheStatistics($event);
    }

    /**
     * Handle cache hit event
     * 
     * @param CacheHitEvent $event
     * @return void
     */
    public function handleCacheHit(CacheHitEvent $event): void
    {
        Log::debug('Cache hit', $event->getEventData());
        
        // ✅ Business logic: Track cache hit metrics
        $this->trackCacheHit($event->key, $event->metadata);
        
        // ✅ Business logic: Update hit rate statistics
        $this->updateHitRateStatistics($event);
    }

    /**
     * Handle cache missed event
     * 
     * @param CacheMissedEvent $event
     * @return void
     */
    public function handleCacheMissed(CacheMissedEvent $event): void
    {
        Log::debug('Cache miss', $event->getEventData());
        
        // ✅ Business logic: Track cache miss metrics
        $this->trackCacheMiss($event->key, $event->metadata);
        
        // ✅ Business logic: Update miss rate statistics
        $this->updateMissRateStatistics($event);
    }

    /**
     * Handle cache invalidated event
     * 
     * @param CacheInvalidatedEvent $event
     * @return void
     */
    public function handleCacheInvalidated(CacheInvalidatedEvent $event): void
    {
        Log::info('Cache invalidated', $event->getEventData());
        
        // ✅ Business logic: Track cache invalidation metrics
        $this->trackCacheInvalidation($event->key, $event->metadata);
        
        // ✅ Business logic: Notify dependent systems
        $this->notifyDependentSystems($event);
    }

    /**
     * Track cache creation metrics
     * 
     * @param string $key Cache key
     * @param array $metadata Metadata
     * @return void
     */
    private function trackCacheCreation(string $key, array $metadata): void
    {
        $metricKey = 'cache_creation_count';
        $currentCount = Cache::get($metricKey, 0);
        Cache::put($metricKey, $currentCount + 1, 3600);
        
        // Track by key pattern
        $pattern = $this->extractKeyPattern($key);
        $patternKey = "cache_creation_pattern:{$pattern}";
        $patternCount = Cache::get($patternKey, 0);
        Cache::put($patternKey, $patternCount + 1, 3600);
    }

    /**
     * Track cache deletion metrics
     * 
     * @param string $key Cache key
     * @param array $metadata Metadata
     * @return void
     */
    private function trackCacheDeletion(string $key, array $metadata): void
    {
        $metricKey = 'cache_deletion_count';
        $currentCount = Cache::get($metricKey, 0);
        Cache::put($metricKey, $currentCount + 1, 3600);
        
        // Track by key pattern
        $pattern = $this->extractKeyPattern($key);
        $patternKey = "cache_deletion_pattern:{$pattern}";
        $patternCount = Cache::get($patternKey, 0);
        Cache::put($patternKey, $patternCount + 1, 3600);
    }

    /**
     * Track cache hit metrics
     * 
     * @param string $key Cache key
     * @param array $metadata Metadata
     * @return void
     */
    private function trackCacheHit(string $key, array $metadata): void
    {
        $metricKey = 'cache_hit_count';
        $currentCount = Cache::get($metricKey, 0);
        Cache::put($metricKey, $currentCount + 1, 3600);
        
        // Track by key pattern
        $pattern = $this->extractKeyPattern($key);
        $patternKey = "cache_hit_pattern:{$pattern}";
        $patternCount = Cache::get($patternKey, 0);
        Cache::put($patternKey, $patternCount + 1, 3600);
    }

    /**
     * Track cache miss metrics
     * 
     * @param string $key Cache key
     * @param array $metadata Metadata
     * @return void
     */
    private function trackCacheMiss(string $key, array $metadata): void
    {
        $metricKey = 'cache_miss_count';
        $currentCount = Cache::get($metricKey, 0);
        Cache::put($metricKey, $currentCount + 1, 3600);
        
        // Track by key pattern
        $pattern = $this->extractKeyPattern($key);
        $patternKey = "cache_miss_pattern:{$pattern}";
        $patternCount = Cache::get($patternKey, 0);
        Cache::put($patternKey, $patternCount + 1, 3600);
    }

    /**
     * Track cache invalidation metrics
     * 
     * @param string $key Cache key
     * @param array $metadata Metadata
     * @return void
     */
    private function trackCacheInvalidation(string $key, array $metadata): void
    {
        $metricKey = 'cache_invalidation_count';
        $currentCount = Cache::get($metricKey, 0);
        Cache::put($metricKey, $currentCount + 1, 3600);
        
        // Track by key pattern
        $pattern = $this->extractKeyPattern($key);
        $patternKey = "cache_invalidation_pattern:{$pattern}";
        $patternCount = Cache::get($patternKey, 0);
        Cache::put($patternKey, $patternCount + 1, 3600);
    }

    /**
     * Update cache statistics
     * 
     * @param CacheEvent $event
     * @return void
     */
    private function updateCacheStatistics(CacheEvent $event): void
    {
        $statsKey = 'cache_statistics';
        $stats = Cache::get($statsKey, [
            'total_operations' => 0,
            'total_hits' => 0,
            'total_misses' => 0,
            'total_creations' => 0,
            'total_deletions' => 0,
            'total_invalidations' => 0,
            'last_updated' => now()->toISOString()
        ]);
        
        $stats['total_operations']++;
        $stats['last_updated'] = now()->toISOString();
        
        switch ($event->getEventName()) {
            case 'cache.hit':
                $stats['total_hits']++;
                break;
            case 'cache.missed':
                $stats['total_misses']++;
                break;
            case 'cache.created':
                $stats['total_creations']++;
                break;
            case 'cache.deleted':
                $stats['total_deletions']++;
                break;
            case 'cache.invalidated':
                $stats['total_invalidations']++;
                break;
        }
        
        Cache::put($statsKey, $stats, 3600);
    }

    /**
     * Update hit rate statistics
     * 
     * @param CacheHitEvent $event
     * @return void
     */
    private function updateHitRateStatistics(CacheHitEvent $event): void
    {
        $hitRateKey = 'cache_hit_rate';
        $totalHits = Cache::get('cache_hit_count', 0);
        $totalMisses = Cache::get('cache_miss_count', 0);
        $totalRequests = $totalHits + $totalMisses;
        
        if ($totalRequests > 0) {
            $hitRate = ($totalHits / $totalRequests) * 100;
            Cache::put($hitRateKey, $hitRate, 3600);
        }
    }

    /**
     * Update miss rate statistics
     * 
     * @param CacheMissedEvent $event
     * @return void
     */
    private function updateMissRateStatistics(CacheMissedEvent $event): void
    {
        $missRateKey = 'cache_miss_rate';
        $totalHits = Cache::get('cache_hit_count', 0);
        $totalMisses = Cache::get('cache_miss_count', 0);
        $totalRequests = $totalHits + $totalMisses;
        
        if ($totalRequests > 0) {
            $missRate = ($totalMisses / $totalRequests) * 100;
            Cache::put($missRateKey, $missRate, 3600);
        }
    }

    /**
     * Notify monitoring system
     * 
     * @param CacheEvent $event
     * @return void
     */
    private function notifyMonitoringSystem(CacheEvent $event): void
    {
        // ✅ Business logic: Send metrics to monitoring system
        $monitoringData = [
            'event' => $event->getEventName(),
            'key' => $event->key,
            'timestamp' => $event->timestamp,
            'module' => 'task',
            'environment' => config('app.env')
        ];
        
        Log::info('Cache monitoring notification', $monitoringData);
    }

    /**
     * Notify dependent systems
     * 
     * @param CacheInvalidatedEvent $event
     * @return void
     */
    private function notifyDependentSystems(CacheInvalidatedEvent $event): void
    {
        // ✅ Business logic: Notify systems that depend on this cache
        $notificationData = [
            'event' => 'cache.invalidated',
            'key' => $event->key,
            'timestamp' => $event->timestamp,
            'action' => 'refresh_dependent_data'
        ];
        
        Log::info('Cache invalidation notification', $notificationData);
    }

    /**
     * Extract key pattern from cache key
     * 
     * @param string $key Cache key
     * @return string
     */
    private function extractKeyPattern(string $key): string
    {
        // Extract the main pattern from the key
        $parts = explode(':', $key);
        return $parts[0] ?? 'unknown';
    }
}

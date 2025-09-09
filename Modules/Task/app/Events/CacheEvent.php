<?php

namespace Modules\Task\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Cache Event Base Class
 * 
 * Tuân thủ Clean Architecture: Event-driven cache invalidation
 * Infrastructure Layer - xử lý events và notifications
 */
abstract class CacheEvent
{
    use Dispatchable, SerializesModels;

    public string $key;
    public array $metadata;
    public string $timestamp;

    /**
     * Khởi tạo cache event
     * 
     * @param string $key Cache key
     * @param array $metadata Additional metadata
     */
    public function __construct(string $key, array $metadata = [])
    {
        $this->key = $key;
        $this->metadata = $metadata;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get event name
     * 
     * @return string
     */
    abstract public function getEventName(): string;

    /**
     * Get event data
     * 
     * @return array
     */
    public function getEventData(): array
    {
        return [
            'event' => $this->getEventName(),
            'key' => $this->key,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp,
        ];
    }
}

/**
 * Cache Created Event
 */
class CacheCreatedEvent extends CacheEvent
{
    public function getEventName(): string
    {
        return 'cache.created';
    }
}

/**
 * Cache Updated Event
 */
class CacheUpdatedEvent extends CacheEvent
{
    public function getEventName(): string
    {
        return 'cache.updated';
    }
}

/**
 * Cache Deleted Event
 */
class CacheDeletedEvent extends CacheEvent
{
    public function getEventName(): string
    {
        return 'cache.deleted';
    }
}

/**
 * Cache Missed Event
 */
class CacheMissedEvent extends CacheEvent
{
    public function getEventName(): string
    {
        return 'cache.missed';
    }
}

/**
 * Cache Hit Event
 */
class CacheHitEvent extends CacheEvent
{
    public function getEventName(): string
    {
        return 'cache.hit';
    }
}

/**
 * Cache Invalidated Event
 */
class CacheInvalidatedEvent extends CacheEvent
{
    public function getEventName(): string
    {
        return 'cache.invalidated';
    }
}

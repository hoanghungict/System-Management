<?php

namespace Modules\Task\app\Repositories\Interfaces;

/**
 * Interface cho Cached Calendar Repository
 * 
 * Repository này cung cấp các method để cache calendar data
 */
interface CachedCalendarRepositoryInterface
{
    /**
     * Clear calendar cache cho user
     * 
     * @param int $userId User ID
     * @param string $userType User type (student, lecturer, admin)
     * @return bool
     */
    public function clearCalendarCache(int $userId, string $userType): bool;

    /**
     * Clear tất cả calendar cache
     * 
     * @return bool
     */
    public function clearAllCalendarCache(): bool;

    /**
     * Get calendar events với cache
     * 
     * @param array $filters Filters
     * @return array
     */
    public function getCachedEvents(array $filters = []): array;

    /**
     * Cache calendar events
     * 
     * @param array $events Events data
     * @param string $cacheKey Cache key
     * @param int $ttl TTL in seconds
     * @return bool
     */
    public function cacheEvents(array $events, string $cacheKey, int $ttl = 3600): bool;
}

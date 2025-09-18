<?php

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Repositories\Interfaces\CachedCalendarRepositoryInterface;
use Modules\Task\app\Services\Interfaces\CacheServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * Cached Calendar Repository Implementation
 * 
 * Repository này cung cấp các method để cache calendar data
 */
class CachedCalendarRepository implements CachedCalendarRepositoryInterface
{
    private const CACHE_PREFIX = 'calendar';
    private const DEFAULT_TTL = 3600; // 1 hour

    public function __construct(
        private CacheServiceInterface $cacheService
    ) {}

    /**
     * Clear calendar cache cho user
     */
    public function clearCalendarCache(int $userId, string $userType): bool
    {
        try {
            $patterns = [
                self::CACHE_PREFIX . ":user:{$userId}:{$userType}:*",
                self::CACHE_PREFIX . ":events:*",
                self::CACHE_PREFIX . ":reminders:*"
            ];

            foreach ($patterns as $pattern) {
                $this->cacheService->forgetPattern($pattern);
            }

            Log::info('Calendar cache cleared for user', [
                'user_id' => $userId,
                'user_type' => $userType
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error clearing calendar cache: ' . $e->getMessage(), [
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            return false;
        }
    }

    /**
     * Clear tất cả calendar cache
     */
    public function clearAllCalendarCache(): bool
    {
        try {
            $pattern = self::CACHE_PREFIX . ":*";
            $this->cacheService->forgetPattern($pattern);

            Log::info('All calendar cache cleared');
            return true;
        } catch (\Exception $e) {
            Log::error('Error clearing all calendar cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get calendar events với cache
     */
    public function getCachedEvents(array $filters = []): array
    {
        try {
            $cacheKey = self::CACHE_PREFIX . ":events:" . md5(serialize($filters));
            
            return $this->cacheService->remember($cacheKey, function () use ($filters) {
                // Tạm thời trả về empty array
                // Có thể implement logic lấy events từ database sau
                return [];
            }, self::DEFAULT_TTL);
        } catch (\Exception $e) {
            Log::error('Error getting cached events: ' . $e->getMessage(), $filters);
            return [];
        }
    }

    /**
     * Cache calendar events
     */
    public function cacheEvents(array $events, string $cacheKey, int $ttl = 3600): bool
    {
        try {
            $fullCacheKey = self::CACHE_PREFIX . ":events:" . $cacheKey;
            $this->cacheService->put($fullCacheKey, $events, $ttl);

            Log::info('Calendar events cached', [
                'cache_key' => $fullCacheKey,
                'events_count' => count($events),
                'ttl' => $ttl
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error caching events: ' . $e->getMessage(), [
                'cache_key' => $cacheKey,
                'events_count' => count($events)
            ]);
            return false;
        }
    }
}

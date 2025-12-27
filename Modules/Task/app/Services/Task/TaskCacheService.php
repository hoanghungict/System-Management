<?php

namespace Modules\Task\app\Services\Task;

use Modules\Task\app\Models\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Service xử lý cache cho task
 */
class TaskCacheService
{
    /**
     * Thu thập tất cả cache keys bị ảnh hưởng bởi task
     */
    public function collectAffectedCacheKeys(Task $task): array
    {
        $cacheKeys = [];

        // Task cache key
        $cacheKeys[] = "task:{$task->id}";
        $cacheKeys[] = "task:{$task->id}:detail";

        // Creator cache keys
        if ($task->creator_id && $task->creator_type) {
            $cacheKeys[] = "user:{$task->creator_type}:{$task->creator_id}:tasks";
            $cacheKeys[] = "user:{$task->creator_type}:{$task->creator_id}:created_tasks";
        }

        // Receiver cache keys
        if ($task->receivers) {
            foreach ($task->receivers as $receiver) {
                $cacheKeys[] = "user:{$receiver->receiver_type}:{$receiver->receiver_id}:tasks";
                $cacheKeys[] = "user:{$receiver->receiver_type}:{$receiver->receiver_id}:assigned_tasks";
            }
        }

        // Statistics cache keys
        $cacheKeys[] = "statistics:tasks:overview";
        $cacheKeys[] = "statistics:tasks:total";

        return array_unique($cacheKeys);
    }

    /**
     * Invalidate multiple caches efficiently
     */
    public function invalidateMultipleCaches(array $cacheKeys): bool
    {
        if (empty($cacheKeys)) {
            return true;
        }

        try {
            // Try Redis pipeline for efficiency
            if ($this->isRedisAvailable()) {
                return $this->invalidateWithRedis($cacheKeys);
            }

            // Fallback to Laravel Cache
            return $this->fallbackCacheInvalidation($cacheKeys);
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', [
                'error' => $e->getMessage(),
                'keys_count' => count($cacheKeys)
            ]);
            return false;
        }
    }

    /**
     * Invalidate caches using Redis pipeline
     */
    private function invalidateWithRedis(array $cacheKeys): bool
    {
        $prefix = config('cache.prefix', 'laravel_cache');
        
        Redis::pipeline(function ($pipe) use ($cacheKeys, $prefix) {
            foreach ($cacheKeys as $key) {
                $pipe->del("{$prefix}:{$key}");
            }
        });

        Log::info('Cache invalidated via Redis pipeline', [
            'keys_count' => count($cacheKeys)
        ]);

        return true;
    }

    /**
     * Fallback cache invalidation using Laravel Cache
     */
    private function fallbackCacheInvalidation(array $cacheKeys): bool
    {
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        Log::info('Cache invalidated via fallback method', [
            'keys_count' => count($cacheKeys)
        ]);

        return true;
    }

    /**
     * Check if Redis is available
     */
    private function isRedisAvailable(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear all task-related caches for a specific user
     */
    public function clearUserTaskCache(int $userId, string $userType): void
    {
        $keys = [
            "user:{$userType}:{$userId}:tasks",
            "user:{$userType}:{$userId}:assigned_tasks",
            "user:{$userType}:{$userId}:created_tasks",
            "user:{$userType}:{$userId}:statistics"
        ];

        $this->invalidateMultipleCaches($keys);
    }

    /**
     * Clear permission cache for a task
     */
    public function clearTaskPermissionCache(Task $task): void
    {
        $cacheKeys = [];

        // Clear permission cache for all receivers
        if ($task->receivers) {
            foreach ($task->receivers as $receiver) {
                $cacheKeys[] = "permission:{$receiver->receiver_type}:{$receiver->receiver_id}:task:{$task->id}";
            }
        }

        // Clear creator permission cache
        if ($task->creator_id && $task->creator_type) {
            $cacheKeys[] = "permission:{$task->creator_type}:{$task->creator_id}:task:{$task->id}";
        }

        $this->invalidateMultipleCaches($cacheKeys);
    }
}

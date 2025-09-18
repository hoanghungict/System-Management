<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Services\Interfaces\RedisCacheServiceInterface;
use Modules\Task\app\Repositories\Interfaces\RedisCacheRepositoryInterface;
use Modules\Task\app\DTOs\CacheDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Redis Cache Service Implementation
 * 
 * Tuân thủ Clean Architecture: Implementation của RedisCacheServiceInterface
 * Business Logic Layer - chứa business rules và logic xử lý cache
 */
class RedisCacheService implements RedisCacheServiceInterface
{
    private RedisCacheRepositoryInterface $redisRepository;
    private const MODULE_PREFIX = 'task_module';

    /**
     * Khởi tạo service với dependency injection
     * 
     * @param RedisCacheRepositoryInterface $redisRepository Redis repository
     */
    public function __construct(RedisCacheRepositoryInterface $redisRepository)
    {
        $this->redisRepository = $redisRepository;
    }

    /**
     * Lấy dữ liệu từ cache với business logic
     * 
     * @param string $key Cache key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        try {
            // ✅ Business logic: Validate key format
            if (!$this->isValidKey($key)) {
                Log::warning('Invalid cache key format', ['key' => $key]);
                return $default;
            }

            $value = $this->redisRepository->get($key, $default);
            
            // ✅ Business logic: Track cache hit/miss metrics
            $this->trackCacheMetrics($key, $value !== $default);
            
            return $value;
            
        } catch (\Exception $e) {
            Log::error('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Lưu dữ liệu vào cache với business logic
     * 
     * @param CacheDTO $cacheDTO Cache data transfer object
     * @return bool
     */
    public function set(CacheDTO $cacheDTO): bool
    {
        try {
            // ✅ Business logic: Validate DTO
            if (!$cacheDTO->isValid()) {
                Log::warning('Invalid cache DTO', ['dto' => $cacheDTO->toArray()]);
                return false;
            }

            // ✅ Business logic: Apply TTL based on level if not specified
            $ttl = $cacheDTO->ttl ?? $cacheDTO->getTtlForLevel();
            
            // ✅ Business logic: Size validation
            if (!$this->validateCacheSize($cacheDTO->value)) {
                Log::warning('Cache value too large', [
                    'key' => $cacheDTO->key,
                    'size' => strlen(serialize($cacheDTO->value))
                ]);
                return false;
            }

            $result = $this->redisRepository->set($cacheDTO->key, $cacheDTO->value, $ttl);
            
            // ✅ Business logic: Add tags if specified
            if ($result && !empty($cacheDTO->metadata['tags'] ?? [])) {
                $this->addTags($cacheDTO->key, $cacheDTO->metadata['tags']);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Cache set failed', [
                'key' => $cacheDTO->key ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lưu dữ liệu với level
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param string $level Cache level
     * @return bool
     */
    public function setWithLevel(string $key, $value, string $level = 'normal'): bool
    {
        $cacheDTO = new CacheDTO($key, $value, null, $level);
        return $this->set($cacheDTO);
    }

    /**
     * Xóa cache với business logic
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            // ✅ Business logic: Validate key format
            if (!$this->isValidKey($key)) {
                Log::warning('Invalid cache key format for deletion', ['key' => $key]);
                return false;
            }

            $result = $this->redisRepository->delete($key);
            
            // ✅ Business logic: Track deletion metrics
            $this->trackDeletionMetrics($key, $result);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Cache delete failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Xóa multiple cache keys
     * 
     * @param array $keys Array of cache keys
     * @return bool
     */
    public function deleteMultiple(array $keys): bool
    {
        try {
            // ✅ Business logic: Filter valid keys
            $validKeys = array_filter($keys, [$this, 'isValidKey']);
            
            if (empty($validKeys)) {
                Log::warning('No valid cache keys for deletion');
                return true;
            }

            $result = $this->redisRepository->deleteMultiple($validKeys);
            
            // ✅ Business logic: Track bulk deletion metrics
            $this->trackBulkDeletionMetrics(count($validKeys), $result);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Cache delete multiple failed', [
                'keys_count' => count($keys),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Xóa cache theo pattern
     * 
     * @param string $pattern Pattern to match
     * @return bool
     */
    public function deletePattern(string $pattern): bool
    {
        try {
            // ✅ Business logic: Validate pattern
            if (!$this->isValidPattern($pattern)) {
                Log::warning('Invalid cache pattern', ['pattern' => $pattern]);
                return false;
            }

            $result = $this->redisRepository->deletePattern($pattern);
            
            // ✅ Business logic: Track pattern deletion metrics
            $this->trackPatternDeletionMetrics($pattern, $result);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Cache delete pattern failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Kiểm tra cache exists
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function exists(string $key): bool
    {
        try {
            return $this->redisRepository->exists($key);
            
        } catch (\Exception $e) {
            Log::error('Cache exists check failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy hoặc tạo cache với callback
     * 
     * @param string $key Cache key
     * @param callable $callback Callback to generate data
     * @param int|null $ttl TTL in seconds
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        try {
            // ✅ Business logic: Check cache first
            if ($this->exists($key)) {
                return $this->get($key);
            }

            // ✅ Business logic: Generate data with callback
            $value = $callback();
            
            // ✅ Business logic: Cache the generated value
            $cacheDTO = new CacheDTO($key, $value, $ttl);
            $this->set($cacheDTO);
            
            return $value;
            
        } catch (\Exception $e) {
            Log::error('Cache remember failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to callback execution
            return $callback();
        }
    }

    /**
     * Remember với level
     * 
     * @param string $key Cache key
     * @param callable $callback Callback
     * @param string $level Cache level
     * @return mixed
     */
    public function rememberWithLevel(string $key, callable $callback, string $level = 'normal')
    {
        $cacheDTO = new CacheDTO($key, null, null, $level);
        $ttl = $cacheDTO->getTtlForLevel();
        
        return $this->remember($key, $callback, $ttl);
    }

    /**
     * Tạo cache key từ parameters
     * 
     * @param string $prefix Key prefix
     * @param array $params Parameters
     * @return string
     */
    public function generateKey(string $prefix, array $params = []): string
    {
        $key = $prefix;
        
        if (!empty($params)) {
            // ✅ Business logic: Sort parameters for consistent key generation
            ksort($params);
            $key .= ':' . md5(serialize($params));
        }
        
        return $key;
    }

    /**
     * Xóa tất cả cache của module
     * 
     * @return bool
     */
    public function clearAll(): bool
    {
        try {
            // ✅ Business logic: Use pattern to clear only module cache
            $pattern = self::MODULE_PREFIX . ':*';
            $result = $this->deletePattern($pattern);
            
            Log::info('Cache clear all for Task module', [
                'pattern' => $pattern,
                'success' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Cache clear all failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy cache statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        try {
            $stats = $this->redisRepository->getStatistics();
            
            // ✅ Business logic: Add module-specific metrics
            $stats['module_prefix'] = self::MODULE_PREFIX;
            $stats['module_keys'] = count($this->redisRepository->getKeys(self::MODULE_PREFIX . ':*'));
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Cache statistics failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Ping Redis connection
     * 
     * @return bool
     */
    public function ping(): bool
    {
        try {
            return $this->redisRepository->ping();
            
        } catch (\Exception $e) {
            Log::error('Cache ping failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Warm up cache với data
     * 
     * @param array $cacheData Array of cache data
     * @return bool
     */
    public function warmUp(array $cacheData): bool
    {
        try {
            $successCount = 0;
            $totalCount = count($cacheData);
            
            foreach ($cacheData as $data) {
                if (isset($data['key']) && isset($data['value'])) {
                    $cacheDTO = CacheDTO::fromArray($data);
                    if ($this->set($cacheDTO)) {
                        $successCount++;
                    }
                }
            }
            
            Log::info('Cache warm up completed', [
                'total' => $totalCount,
                'success' => $successCount,
                'success_rate' => $totalCount > 0 ? ($successCount / $totalCount) * 100 : 0
            ]);
            
            return $successCount > 0;
            
        } catch (\Exception $e) {
            Log::error('Cache warm up failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache tags
     * 
     * @param array $tags Array of tags
     * @return bool
     */
    public function invalidateTags(array $tags): bool
    {
        try {
            $successCount = 0;
            
            foreach ($tags as $tag) {
                $pattern = self::MODULE_PREFIX . ':tag:' . $tag . ':*';
                if ($this->deletePattern($pattern)) {
                    $successCount++;
                }
            }
            
            Log::info('Cache tags invalidated', [
                'tags' => $tags,
                'success_count' => $successCount
            ]);
            
            return $successCount > 0;
            
        } catch (\Exception $e) {
            Log::error('Cache invalidate tags failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Add cache tag
     * 
     * @param string $key Cache key
     * @param array $tags Array of tags
     * @return bool
     */
    public function addTags(string $key, array $tags): bool
    {
        try {
            $successCount = 0;
            
            foreach ($tags as $tag) {
                $tagKey = self::MODULE_PREFIX . ':tag:' . $tag . ':' . $key;
                if ($this->redisRepository->set($tagKey, $key, 86400)) { // 24 hours
                    $successCount++;
                }
            }
            
            return $successCount > 0;
            
        } catch (\Exception $e) {
            Log::error('Cache add tags failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate cache key format
     * 
     * @param string $key Cache key
     * @return bool
     */
    private function isValidKey(string $key): bool
    {
        return !empty($key) && strlen($key) <= 250 && preg_match('/^[a-zA-Z0-9:_-]+$/', $key);
    }

    /**
     * Validate cache pattern
     * 
     * @param string $pattern Pattern
     * @return bool
     */
    private function isValidPattern(string $pattern): bool
    {
        return !empty($pattern) && strlen($pattern) <= 250;
    }

    /**
     * Validate cache value size
     * 
     * @param mixed $value Cache value
     * @return bool
     */
    private function validateCacheSize($value): bool
    {
        $size = strlen(serialize($value));
        $maxSize = Config::get('cache.max_value_size', 1048576); // 1MB default
        
        return $size <= $maxSize;
    }

    /**
     * Track cache metrics
     * 
     * @param string $key Cache key
     * @param bool $hit Cache hit or miss
     */
    private function trackCacheMetrics(string $key, bool $hit): void
    {
        // ✅ Business logic: Track cache performance metrics
        $metric = $hit ? 'cache_hit' : 'cache_miss';
        
        Log::debug('Cache metric tracked', [
            'metric' => $metric,
            'key' => $key
        ]);
    }

    /**
     * Track deletion metrics
     * 
     * @param string $key Cache key
     * @param bool $success Deletion success
     */
    private function trackDeletionMetrics(string $key, bool $success): void
    {
        Log::debug('Cache deletion tracked', [
            'key' => $key,
            'success' => $success
        ]);
    }

    /**
     * Track bulk deletion metrics
     * 
     * @param int $count Keys count
     * @param bool $success Deletion success
     */
    private function trackBulkDeletionMetrics(int $count, bool $success): void
    {
        Log::debug('Cache bulk deletion tracked', [
            'count' => $count,
            'success' => $success
        ]);
    }

    /**
     * Track pattern deletion metrics
     * 
     * @param string $pattern Pattern
     * @param bool $success Deletion success
     */
    private function trackPatternDeletionMetrics(string $pattern, bool $success): void
    {
        Log::debug('Cache pattern deletion tracked', [
            'pattern' => $pattern,
            'success' => $success
        ]);
    }
}

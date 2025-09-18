<?php

namespace Modules\Task\app\Services\Interfaces;

use Modules\Task\app\DTOs\CacheDTO;

/**
 * Interface cho Redis Cache Service
 * 
 * Tuân thủ Clean Architecture: Interface định nghĩa contract cho Redis cache business logic
 * Business Logic Layer - chứa business rules và logic xử lý cache
 */
interface RedisCacheServiceInterface
{
    /**
     * Lấy dữ liệu từ cache với business logic
     * 
     * @param string $key Cache key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Lưu dữ liệu vào cache với business logic
     * 
     * @param CacheDTO $cacheDTO Cache data transfer object
     * @return bool
     */
    public function set(CacheDTO $cacheDTO): bool;

    /**
     * Lưu dữ liệu với level
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param string $level Cache level
     * @return bool
     */
    public function setWithLevel(string $key, $value, string $level = 'normal'): bool;

    /**
     * Xóa cache với business logic
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Xóa multiple cache keys
     * 
     * @param array $keys Array of cache keys
     * @return bool
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Xóa cache theo pattern
     * 
     * @param string $pattern Pattern to match
     * @return bool
     */
    public function deletePattern(string $pattern): bool;

    /**
     * Kiểm tra cache exists
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Lấy hoặc tạo cache với callback
     * 
     * @param string $key Cache key
     * @param callable $callback Callback to generate data
     * @param int|null $ttl TTL in seconds
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null);

    /**
     * Remember với level
     * 
     * @param string $key Cache key
     * @param callable $callback Callback
     * @param string $level Cache level
     * @return mixed
     */
    public function rememberWithLevel(string $key, callable $callback, string $level = 'normal');

    /**
     * Tạo cache key từ parameters
     * 
     * @param string $prefix Key prefix
     * @param array $params Parameters
     * @return string
     */
    public function generateKey(string $prefix, array $params = []): string;

    /**
     * Xóa tất cả cache của module
     * 
     * @return bool
     */
    public function clearAll(): bool;

    /**
     * Lấy cache statistics
     * 
     * @return array
     */
    public function getStatistics(): array;

    /**
     * Ping Redis connection
     * 
     * @return bool
     */
    public function ping(): bool;

    /**
     * Warm up cache với data
     * 
     * @param array $cacheData Array of cache data
     * @return bool
     */
    public function warmUp(array $cacheData): bool;

    /**
     * Invalidate cache tags
     * 
     * @param array $tags Array of tags
     * @return bool
     */
    public function invalidateTags(array $tags): bool;

    /**
     * Add cache tag
     * 
     * @param string $key Cache key
     * @param array $tags Array of tags
     * @return bool
     */
    public function addTags(string $key, array $tags): bool;
}

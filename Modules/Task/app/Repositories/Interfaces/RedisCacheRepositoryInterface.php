<?php

namespace Modules\Task\app\Repositories\Interfaces;

/**
 * Interface cho Redis Cache Repository
 * 
 * Tuân thủ Clean Architecture: Interface định nghĩa contract cho Redis cache operations
 * Data Access Layer - chỉ xử lý việc truy cập dữ liệu cache
 */
interface RedisCacheRepositoryInterface
{
    /**
     * Lấy dữ liệu từ Redis cache
     * 
     * @param string $key Cache key
     * @param mixed $default Giá trị mặc định
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Lưu dữ liệu vào Redis cache
     * 
     * @param string $key Cache key
     * @param mixed $value Giá trị cần cache
     * @param int|null $ttl Thời gian sống (seconds)
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool;

    /**
     * Xóa cache key
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
     * @param string $pattern Pattern để match keys
     * @return bool
     */
    public function deletePattern(string $pattern): bool;

    /**
     * Kiểm tra key có tồn tại không
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Lấy TTL của key
     * 
     * @param string $key Cache key
     * @return int|null TTL in seconds, null if key doesn't exist
     */
    public function getTtl(string $key): ?int;

    /**
     * Set TTL cho key
     * 
     * @param string $key Cache key
     * @param int $ttl TTL in seconds
     * @return bool
     */
    public function setTtl(string $key, int $ttl): bool;

    /**
     * Lấy tất cả keys theo pattern
     * 
     * @param string $pattern Pattern để match keys
     * @return array Array of matching keys
     */
    public function getKeys(string $pattern): array;

    /**
     * Lấy thông tin cache statistics
     * 
     * @return array Cache statistics
     */
    public function getStatistics(): array;

    /**
     * Flush tất cả cache
     * 
     * @return bool
     */
    public function flush(): bool;

    /**
     * Ping Redis connection
     * 
     * @return bool
     */
    public function ping(): bool;
}

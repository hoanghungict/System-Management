<?php

namespace Modules\Task\app\Services\Interfaces;

/**
 * Interface cho Cache Service
 * 
 * Tuân thủ Clean Architecture: Interface định nghĩa contract cho cache operations
 */
interface CacheServiceInterface
{
    /**
     * Lấy dữ liệu từ cache
     * 
     * @param string $key Cache key
     * @param mixed $default Giá trị mặc định nếu không tìm thấy
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Lưu dữ liệu vào cache
     * 
     * @param string $key Cache key
     * @param mixed $value Giá trị cần cache
     * @param int|null $ttl Thời gian sống (seconds)
     * @return bool
     */
    public function put(string $key, $value, ?int $ttl = null): bool;

    /**
     * Xóa cache theo key
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Xóa cache theo pattern
     * 
     * @param string $pattern Pattern để match keys
     * @return bool
     */
    public function forgetPattern(string $pattern): bool;

    /**
     * Kiểm tra key có tồn tại trong cache không
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Lấy hoặc tạo cache với callback
     * 
     * @param string $key Cache key
     * @param callable $callback Callback để tạo dữ liệu
     * @param int|null $ttl Thời gian sống (seconds)
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null);

    /**
     * Tạo cache key từ parameters
     * 
     * @param string $prefix Prefix cho key
     * @param array $params Parameters
     * @return string
     */
    public function generateKey(string $prefix, array $params = []): string;

    /**
     * Xóa tất cả cache của module Task
     * 
     * @return bool
     */
    public function clearAll(): bool;

    /**
     * ✅ Xóa multiple cache keys trong một lần
     * 
     * @param array $keys Array of cache keys
     * @return bool
     */
    public function forgetMultiple(array $keys): bool;
}

<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Services\Interfaces\CacheServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cache Service Implementation
 * 
 * Tuân thủ Clean Architecture: Implementation của CacheServiceInterface
 * Xử lý tất cả cache operations cho module Task
 */
class CacheService implements CacheServiceInterface
{
    private const MODULE_PREFIX = 'task_module';
    private const DEFAULT_TTL = 3600; // 1 hour

    /**
     * Cache TTL levels
     */
    private const TTL_LEVELS = [
        'critical' => 300,    // 5 minutes
        'important' => 900,   // 15 minutes
        'normal' => 1800,     // 30 minutes
        'background' => 3600  // 1 hour
    ];

    /**
     * Lấy dữ liệu từ cache
     * 
     * @param string $key Cache key
     * @param mixed $default Giá trị mặc định nếu không tìm thấy
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $fullKey = $this->generateFullKey($key);
        $value = Cache::get($fullKey, $default);
        
        Log::debug('Cache get', [
            'key' => $fullKey,
            'found' => $value !== $default
        ]);
        
        return $value;
    }

    /**
     * Lưu dữ liệu vào cache
     * 
     * @param string $key Cache key
     * @param mixed $value Giá trị cần cache
     * @param int|null $ttl Thời gian sống (seconds)
     * @return bool
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $fullKey = $this->generateFullKey($key);
        $ttl = $ttl ?? self::DEFAULT_TTL;
        
        $result = Cache::put($fullKey, $value, $ttl);
        
        Log::debug('Cache put', [
            'key' => $fullKey,
            'ttl' => $ttl,
            'success' => $result
        ]);
        
        return $result;
    }

    /**
     * Xóa cache theo key
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $fullKey = $this->generateFullKey($key);
        $result = Cache::forget($fullKey);
        
        Log::debug('Cache forget', [
            'key' => $fullKey,
            'success' => $result
        ]);
        
        return $result;
    }

    /**
     * Xóa cache theo pattern
     * 
     * @param string $pattern Pattern để match keys
     * @return bool
     */
    public function forgetPattern(string $pattern): bool
    {
        $fullPattern = $this->generateFullKey($pattern);
        $storePrefix = method_exists(Cache::getStore(), 'getPrefix') ? (string) Cache::getStore()->getPrefix() : '';
        $redisMatchPattern = $storePrefix . $fullPattern;
        
        try {
            // ✅ Implement proper Redis pattern matching
            if (config('cache.default') === 'redis') {
                $redis = \Illuminate\Support\Facades\Redis::connection();
                
                // Use SCAN to get matching keys safely
                $iterator = null;
                $matchingKeys = [];
                
                do {
                    $result = $redis->scan($iterator, [
                        'MATCH' => $redisMatchPattern,
                        'COUNT' => 100
                    ]);
                    
                    if ($result !== false) {
                        [$iterator, $keys] = $result;
                        $matchingKeys = array_merge($matchingKeys, $keys);
                    }
                } while ($iterator !== 0 && $iterator !== null);
                
                // Delete matching keys in batches
                if (!empty($matchingKeys)) {
                    $chunks = array_chunk($matchingKeys, 100);
                    foreach ($chunks as $chunk) {
                        $redis->del($chunk);
                    }
                    
                    Log::debug('Cache forget pattern success', [
                        'pattern' => $redisMatchPattern,
                        'deleted_keys' => count($matchingKeys)
                    ]);
                    
                    return true;
                }
            } else {
                // Fallback for non-Redis cache drivers
                Log::warning('Pattern deletion not supported for cache driver: ' . config('cache.default'));
            }
        } catch (\Exception $e) {
            Log::error('Cache forget pattern failed', [
                'pattern' => $fullPattern,
                'error' => $e->getMessage()
            ]);
            return false;
        }
        
        Log::debug('Cache forget pattern', [
            'pattern' => $fullPattern,
            'result' => 'no_keys_found'
        ]);
        
        return true;
    }

    /**
     * Kiểm tra key có tồn tại trong cache không
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        $fullKey = $this->generateFullKey($key);
        return Cache::has($fullKey);
    }

    /**
     * Lấy hoặc tạo cache với callback
     * 
     * @param string $key Cache key
     * @param callable $callback Callback để tạo dữ liệu
     * @param int|null $ttl Thời gian sống (seconds)
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $fullKey = $this->generateFullKey($key);
        $ttl = $ttl ?? self::DEFAULT_TTL;
        
        $value = Cache::remember($fullKey, $ttl, $callback);
        
        Log::debug('Cache remember', [
            'key' => $fullKey,
            'ttl' => $ttl,
            'cached' => Cache::has($fullKey)
        ]);
        
        return $value;
    }

    /**
     * Tạo cache key từ parameters
     * 
     * @param string $prefix Prefix cho key
     * @param array $params Parameters
     * @return string
     */
    public function generateKey(string $prefix, array $params = []): string
    {
        $key = $prefix;
        
        if (!empty($params)) {
            $key .= ':' . md5(serialize($params));
        }
        
        return $key;
    }

    /**
     * Xóa tất cả cache của module Task
     * 
     * @return bool
     */
    public function clearAll(): bool
    {
        // ✅ Use pattern matching to clear all Task module cache
        $pattern = self::MODULE_PREFIX . ':*';
        $result = $this->forgetPattern($pattern);
        
        Log::info('Cache clear all for Task module', [
            'pattern' => $pattern,
            'success' => $result
        ]);
        
        return $result;
    }

    /**
     * Xóa multiple cache keys trong một lần
     * 
     * @param array $keys Array of cache keys
     * @return bool
     */
    public function forgetMultiple(array $keys): bool
    {
        if (empty($keys)) {
            return true;
        }

        try {
            $fullKeys = array_map([$this, 'generateFullKey'], $keys);
            
            // Always use Cache::forget to respect store prefixing
            foreach ($fullKeys as $key) {
                Cache::forget($key);
            }
            
            Log::debug('Cache forget multiple', [
                'keys_count' => count($keys),
                'success' => true
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Cache forget multiple failed', [
                'keys_count' => count($keys),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Tạo full cache key với module prefix
     * 
     * @param string $key Base key
     * @return string Full key
     */
    private function generateFullKey(string $key): string
    {
        return self::MODULE_PREFIX . ':' . $key;
    }

    /**
     * Lấy TTL theo level
     * 
     * @param string $level Cache level
     * @return int TTL in seconds
     */
    public function getTtlByLevel(string $level): int
    {
        return self::TTL_LEVELS[$level] ?? self::DEFAULT_TTL;
    }

    /**
     * Cache với level
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param string $level Cache level
     * @return bool
     */
    public function putWithLevel(string $key, $value, string $level = 'normal'): bool
    {
        $ttl = $this->getTtlByLevel($level);
        return $this->put($key, $value, $ttl);
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
        $ttl = $this->getTtlByLevel($level);
        return $this->remember($key, $callback, $ttl);
    }
}

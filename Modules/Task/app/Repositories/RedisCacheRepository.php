<?php

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Repositories\Interfaces\RedisCacheRepositoryInterface;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Redis Cache Repository Implementation
 * 
 * Tuân thủ Clean Architecture: Implementation của RedisCacheRepositoryInterface
 * Data Access Layer - chỉ xử lý việc truy cập dữ liệu Redis cache
 */
class RedisCacheRepository implements RedisCacheRepositoryInterface
{
    private $redis;
    private const MODULE_PREFIX = 'task_module';
    private const DEFAULT_TTL = 3600; // 1 hour

    /**
     * Khởi tạo repository với Redis connection
     */
    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * Lấy dữ liệu từ Redis cache
     * 
     * @param string $key Cache key
     * @param mixed $default Giá trị mặc định
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        try {
            $fullKey = $this->generateFullKey($key);
            $value = $this->redis->get($fullKey);
            
            if ($value === null) {
                Log::debug('Redis cache miss', ['key' => $fullKey]);
                return $default;
            }
            
            $decodedValue = $this->decodeValue($value);
            
            Log::debug('Redis cache hit', ['key' => $fullKey]);
            return $decodedValue;
            
        } catch (\Exception $e) {
            Log::error('Redis get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Lưu dữ liệu vào Redis cache
     * 
     * @param string $key Cache key
     * @param mixed $value Giá trị cần cache
     * @param int|null $ttl Thời gian sống (seconds)
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        try {
            $fullKey = $this->generateFullKey($key);
            $ttl = $ttl ?? self::DEFAULT_TTL;
            $encodedValue = $this->encodeValue($value);
            
            $result = $this->redis->setex($fullKey, $ttl, $encodedValue);
            
            Log::debug('Redis cache set', [
                'key' => $fullKey,
                'ttl' => $ttl,
                'success' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Redis set failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Xóa cache key
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            $fullKey = $this->generateFullKey($key);
            $result = $this->redis->del($fullKey);
            
            Log::debug('Redis cache delete', [
                'key' => $fullKey,
                'deleted' => $result > 0
            ]);
            
            return $result > 0;
            
        } catch (\Exception $e) {
            Log::error('Redis delete failed', [
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
        if (empty($keys)) {
            return true;
        }

        try {
            $fullKeys = array_map([$this, 'generateFullKey'], $keys);
            
            // Delete in batches for better performance
            $chunks = array_chunk($fullKeys, 100);
            $totalDeleted = 0;
            
            foreach ($chunks as $chunk) {
                $deleted = $this->redis->del($chunk);
                $totalDeleted += $deleted;
            }
            
            Log::debug('Redis cache delete multiple', [
                'keys_count' => count($keys),
                'deleted_count' => $totalDeleted
            ]);
            
            return $totalDeleted > 0;
            
        } catch (\Exception $e) {
            Log::error('Redis delete multiple failed', [
                'keys_count' => count($keys),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Xóa cache theo pattern
     * 
     * @param string $pattern Pattern để match keys
     * @return bool
     */
    public function deletePattern(string $pattern): bool
    {
        try {
            $fullPattern = $this->generateFullKey($pattern);
            
            // Use SCAN to get matching keys safely
            $iterator = null;
            $matchingKeys = [];
            
            do {
                $result = $this->redis->scan($iterator, [
                    'MATCH' => $fullPattern,
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
                $totalDeleted = 0;
                
                foreach ($chunks as $chunk) {
                    $deleted = $this->redis->del($chunk);
                    $totalDeleted += $deleted;
                }
                
                Log::debug('Redis cache delete pattern', [
                    'pattern' => $fullPattern,
                    'deleted_keys' => $totalDeleted
                ]);
                
                return $totalDeleted > 0;
            }
            
            Log::debug('Redis cache delete pattern - no keys found', [
                'pattern' => $fullPattern
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Redis delete pattern failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Kiểm tra key có tồn tại không
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function exists(string $key): bool
    {
        try {
            $fullKey = $this->generateFullKey($key);
            return $this->redis->exists($fullKey) > 0;
            
        } catch (\Exception $e) {
            Log::error('Redis exists failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy TTL của key
     * 
     * @param string $key Cache key
     * @return int|null TTL in seconds, null if key doesn't exist
     */
    public function getTtl(string $key): ?int
    {
        try {
            $fullKey = $this->generateFullKey($key);
            $ttl = $this->redis->ttl($fullKey);
            
            return $ttl > 0 ? $ttl : null;
            
        } catch (\Exception $e) {
            Log::error('Redis get TTL failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set TTL cho key
     * 
     * @param string $key Cache key
     * @param int $ttl TTL in seconds
     * @return bool
     */
    public function setTtl(string $key, int $ttl): bool
    {
        try {
            $fullKey = $this->generateFullKey($key);
            $result = $this->redis->expire($fullKey, $ttl);
            
            Log::debug('Redis set TTL', [
                'key' => $fullKey,
                'ttl' => $ttl,
                'success' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Redis set TTL failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy tất cả keys theo pattern
     * 
     * @param string $pattern Pattern để match keys
     * @return array Array of matching keys
     */
    public function getKeys(string $pattern): array
    {
        try {
            $fullPattern = $this->generateFullKey($pattern);
            $keys = [];
            $iterator = null;
            
            do {
                $result = $this->redis->scan($iterator, [
                    'MATCH' => $fullPattern,
                    'COUNT' => 100
                ]);
                
                if ($result !== false) {
                    [$iterator, $batchKeys] = $result;
                    $keys = array_merge($keys, $batchKeys);
                }
            } while ($iterator !== 0 && $iterator !== null);
            
            return $keys;
            
        } catch (\Exception $e) {
            Log::error('Redis get keys failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Lấy thông tin cache statistics
     * 
     * @return array Cache statistics
     */
    public function getStatistics(): array
    {
        try {
            $info = $this->redis->info();
            
            return [
                'total_connections_received' => $info['total_connections_received'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_peak' => $info['used_memory_peak'] ?? 0,
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0,
                'connected_clients' => $info['connected_clients'] ?? 0,
            ];
            
        } catch (\Exception $e) {
            Log::error('Redis get statistics failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Flush tất cả cache
     * 
     * @return bool
     */
    public function flush(): bool
    {
        try {
            $result = $this->redis->flushdb();
            
            Log::info('Redis flush all cache', [
                'success' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Redis flush failed', [
                'error' => $e->getMessage()
            ]);
            return false;
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
            $result = $this->redis->ping();
            return $result === 'PONG';
            
        } catch (\Exception $e) {
            Log::error('Redis ping failed', [
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
     * Encode value để lưu vào Redis
     * 
     * @param mixed $value Value to encode
     * @return string Encoded value
     */
    private function encodeValue($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode value từ Redis
     * 
     * @param string $value Encoded value
     * @return mixed Decoded value
     */
    private function decodeValue(string $value)
    {
        return json_decode($value, true);
    }
}

<?php

namespace Modules\Task\app\DTOs;

/**
 * Data Transfer Object cho Cache Operations
 * 
 * Tuân thủ Clean Architecture: DTO để truyền dữ liệu giữa các layer
 */
class CacheDTO
{
    public string $key;
    public $value;
    public ?int $ttl;
    public string $level;
    public array $metadata;

    /**
     * Khởi tạo CacheDTO
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl TTL in seconds
     * @param string $level Cache level
     * @param array $metadata Additional metadata
     */
    public function __construct(
        string $key,
        $value = null,
        ?int $ttl = null,
        string $level = 'normal',
        array $metadata = []
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->ttl = $ttl;
        $this->level = $level;
        $this->metadata = $metadata;
    }

    /**
     * Tạo CacheDTO từ array
     * 
     * @param array $data Array data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            $data['key'] ?? '',
            $data['value'] ?? null,
            $data['ttl'] ?? null,
            $data['level'] ?? 'normal',
            $data['metadata'] ?? []
        );
    }

    /**
     * Convert DTO to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'ttl' => $this->ttl,
            'level' => $this->level,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Validate DTO data
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->key);
    }

    /**
     * Get cache level TTL mapping
     * 
     * @return array
     */
    public static function getTtlLevels(): array
    {
        return [
            'critical' => 300,    // 5 minutes
            'important' => 900,   // 15 minutes
            'normal' => 1800,     // 30 minutes
            'background' => 3600, // 1 hour
            'long_term' => 7200   // 2 hours
        ];
    }

    /**
     * Get TTL for current level
     * 
     * @return int
     */
    public function getTtlForLevel(): int
    {
        $levels = self::getTtlLevels();
        return $levels[$this->level] ?? $levels['normal'];
    }
}

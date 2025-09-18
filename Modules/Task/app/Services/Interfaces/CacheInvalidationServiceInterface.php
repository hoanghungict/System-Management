<?php

namespace Modules\Task\app\Services\Interfaces;

/**
 * Interface cho Cache Invalidation Service
 * 
 * Service này cho phép các module khác invalidate cache của Task module
 * khi có thay đổi dữ liệu liên quan (student, lecturer, faculty, class)
 */
interface CacheInvalidationServiceInterface
{
    /**
     * Invalidate student cache (simplified version for API)
     * 
     * @return array
     */
    public function invalidateStudentCache(): array;

    /**
     * Invalidate lecturer cache (simplified version for API)
     * 
     * @return array
     */
    public function invalidateLecturerCache(): array;

    /**
     * Invalidate department cache (simplified version for API)
     * 
     * @return array
     */
    public function invalidateDepartmentCache(): array;

    /**
     * Invalidate class cache (simplified version for API)
     * 
     * @return array
     */
    public function invalidateClassCache(): array;

    /**
     * Invalidate bulk cache
     * 
     * @param array $types Array of cache types to invalidate
     * @return array
     */
    public function invalidateBulkCache(array $types): array;

    /**
     * Invalidate all cache
     * 
     * @return array
     */
    public function invalidateAllCache(): array;

    /**
     * Get cache health status
     * 
     * @return array
     */
    public function getHealthStatus(): array;
}
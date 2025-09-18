<?php

namespace Modules\Task\app\Cache\Contracts;

/**
 * Cache Invalidation Contract
 * 
 * Interface định nghĩa các method cần thiết cho Cache invalidation
 */
interface CacheInvalidationInterface
{
    /**
     * Invalidate student cache
     */
    public function invalidateStudentCache(int $studentId, ?int $classId = null, ?int $facultyId = null): bool;

    /**
     * Invalidate lecturer cache
     */
    public function invalidateLecturerCache(int $lecturerId, ?int $facultyId = null): bool;

    /**
     * Invalidate department cache
     */
    public function invalidateDepartmentCache(int $departmentId): bool;

    /**
     * Invalidate class cache
     */
    public function invalidateClassCache(int $classId, ?int $departmentId = null): bool;

    /**
     * Invalidate bulk cache
     */
    public function invalidateBulkCache(array $affectedIds, string $type): bool;

    /**
     * Invalidate all cache
     */
    public function invalidateAllCache(): bool;

    /**
     * Get cache health status
     */
    public function getCacheHealth(): array;
}

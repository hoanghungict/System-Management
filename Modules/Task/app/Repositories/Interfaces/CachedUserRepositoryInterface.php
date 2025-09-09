<?php

namespace Modules\Task\app\Repositories\Interfaces;

/**
 * Interface cho Cached User Repository
 * 
 * Tuân thủ Clean Architecture: Interface định nghĩa contract cho cached user operations
 */
interface CachedUserRepositoryInterface
{
    /**
     * Lấy danh sách faculties với cache
     * 
     * @param mixed $user User hiện tại
     * @return array
     */
    public function getFacultiesForUser($user): array;

    /**
     * Lấy danh sách classes theo department với cache
     * 
     * @param mixed $user User hiện tại
     * @param int $departmentId Department ID
     * @return array
     */
    public function getClassesByDepartmentForUser($user, int $departmentId): array;

    /**
     * Lấy danh sách students theo class với cache
     * 
     * @param mixed $user User hiện tại
     * @param int $classId Class ID
     * @return array
     */
    public function getStudentsByClassForUser($user, int $classId): array;

    /**
     * Lấy danh sách lecturers với cache
     * 
     * @param mixed $user User hiện tại
     * @return array
     */
    public function getLecturersForUser($user): array;

    /**
     * Lấy danh sách tất cả students với cache
     * 
     * @param mixed $user User hiện tại
     * @return array
     */
    public function getAllStudentsForUser($user): array;

    /**
     * Lấy tất cả faculties (admin only) với cache
     * 
     * @return array
     */
    public function getAllFaculties(): array;

    /**
     * Lấy tất cả classes của department với cache
     * 
     * @param int $departmentId Department ID
     * @return array
     */
    public function getAllClassesByDepartment(int $departmentId): array;

    /**
     * Lấy tất cả students của class với cache
     * 
     * @param int $classId Class ID
     * @return array
     */
    public function getAllStudentsByClass(int $classId): array;

    /**
     * Lấy tất cả lecturers với cache
     * 
     * @return array
     */
    public function getAllLecturers(): array;

    /**
     * Xóa cache cho user
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @return bool
     */
    public function clearUserCache(int $userId, string $userType): bool;

    /**
     * Xóa cache cho department
     * 
     * @param int $departmentId Department ID
     * @return bool
     */
    public function clearDepartmentCache(int $departmentId): bool;

    /**
     * Xóa cache cho class
     * 
     * @param int $classId Class ID
     * @return bool
     */
    public function clearClassCache(int $classId): bool;

    /**
     * Xóa tất cả user management cache
     * 
     * @return bool
     */
    public function clearAllUserCache(): bool;
}

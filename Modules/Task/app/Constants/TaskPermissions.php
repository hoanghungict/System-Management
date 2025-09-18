<?php

namespace Modules\Task\app\Constants;

/**
 * ✅ Task Permission Constants
 * 
 * Centralized permission constants để tránh magic strings
 * Dễ dàng maintain và refactor permissions
 */
class TaskPermissions
{
    // ===== BASIC PERMISSIONS =====
    const CREATE_TASKS = 'create_tasks';
    const VIEW_TASKS = 'view_tasks';
    const EDIT_TASKS = 'edit_tasks';
    const DELETE_TASKS = 'delete_tasks';
    
    // ===== ADVANCED PERMISSIONS =====
    const VIEW_ALL_TASKS = 'view_all_tasks';
    const EDIT_ANY_TASK = 'edit_any_task';
    const DELETE_ANY_TASK = 'delete_any_task';
    const ASSIGN_TASKS = 'assign_tasks';
    const REVOKE_TASKS = 'revoke_tasks';
    
    // ===== STATUS PERMISSIONS =====
    const UPDATE_TASK_STATUS = 'update_task_status';
    const UPDATE_ANY_TASK_STATUS = 'update_any_task_status';
    const MARK_TASK_COMPLETE = 'mark_task_complete';
    const CANCEL_TASKS = 'cancel_tasks';
    
    // ===== FILE PERMISSIONS =====
    const UPLOAD_TASK_FILES = 'upload_task_files';
    const DELETE_TASK_FILES = 'delete_task_files';
    const VIEW_TASK_FILES = 'view_task_files';
    const DOWNLOAD_TASK_FILES = 'download_task_files';
    
    // ===== ADMINISTRATIVE PERMISSIONS =====
    const MANAGE_USERS = 'manage_users';
    const VIEW_USER_STATISTICS = 'view_user_statistics';
    const GENERATE_REPORTS = 'generate_reports';
    const ADMIN_ACCESS = 'admin_access';
    
    // ===== DATA PERMISSIONS =====
    const VIEW_FACULTY_DATA = 'view_faculty_data';
    const VIEW_CLASS_DATA = 'view_class_data';
    const VIEW_STUDENT_DATA = 'view_student_data';
    const VIEW_LECTURER_DATA = 'view_lecturer_data';
    
    // ===== CACHE PERMISSIONS =====
    const CLEAR_CACHE = 'clear_cache';
    const VIEW_CACHE_STATUS = 'view_cache_status';
    const INVALIDATE_CACHE = 'invalidate_cache';
    
    // ===== SYSTEM PERMISSIONS =====
    const VIEW_SYSTEM_LOGS = 'view_system_logs';
    const SYSTEM_MAINTENANCE = 'system_maintenance';
    const BACKUP_RESTORE = 'backup_restore';

    /**
     * ✅ Get all available permissions
     * 
     * @return array
     */
    public static function getAllPermissions(): array
    {
        $reflection = new \ReflectionClass(__CLASS__);
        return array_values($reflection->getConstants());
    }

    /**
     * ✅ Get permissions by category
     * 
     * @return array
     */
    public static function getPermissionsByCategory(): array
    {
        return [
            'basic' => [
                self::CREATE_TASKS,
                self::VIEW_TASKS,
                self::EDIT_TASKS,
                self::DELETE_TASKS
            ],
            'advanced' => [
                self::VIEW_ALL_TASKS,
                self::EDIT_ANY_TASK,
                self::DELETE_ANY_TASK,
                self::ASSIGN_TASKS,
                self::REVOKE_TASKS
            ],
            'status' => [
                self::UPDATE_TASK_STATUS,
                self::UPDATE_ANY_TASK_STATUS,
                self::MARK_TASK_COMPLETE,
                self::CANCEL_TASKS
            ],
            'files' => [
                self::UPLOAD_TASK_FILES,
                self::DELETE_TASK_FILES,
                self::VIEW_TASK_FILES,
                self::DOWNLOAD_TASK_FILES
            ],
            'admin' => [
                self::MANAGE_USERS,
                self::VIEW_USER_STATISTICS,
                self::GENERATE_REPORTS,
                self::ADMIN_ACCESS
            ],
            'data' => [
                self::VIEW_FACULTY_DATA,
                self::VIEW_CLASS_DATA,
                self::VIEW_STUDENT_DATA,
                self::VIEW_LECTURER_DATA
            ],
            'cache' => [
                self::CLEAR_CACHE,
                self::VIEW_CACHE_STATUS,
                self::INVALIDATE_CACHE
            ],
            'system' => [
                self::VIEW_SYSTEM_LOGS,
                self::SYSTEM_MAINTENANCE,
                self::BACKUP_RESTORE
            ]
        ];
    }

    /**
     * ✅ Get default permissions for user types
     * 
     * @return array
     */
    public static function getDefaultPermissions(): array
    {
        return [
            'admin' => [
                // Admin có tất cả permissions
                ...self::getAllPermissions()
            ],
            'lecturer' => [
                // Lecturer permissions
                self::CREATE_TASKS,
                self::VIEW_TASKS,
                self::EDIT_TASKS,
                self::DELETE_TASKS,
                self::ASSIGN_TASKS,
                self::REVOKE_TASKS,
                self::UPDATE_TASK_STATUS,
                self::UPDATE_ANY_TASK_STATUS,
                self::UPLOAD_TASK_FILES,
                self::DELETE_TASK_FILES,
                self::VIEW_TASK_FILES,
                self::DOWNLOAD_TASK_FILES,
                self::GENERATE_REPORTS,
                self::VIEW_FACULTY_DATA,
                self::VIEW_CLASS_DATA,
                self::VIEW_STUDENT_DATA
            ],
            'student' => [
                // Student permissions (limited)
                self::VIEW_TASKS,
                self::UPDATE_TASK_STATUS,
                self::MARK_TASK_COMPLETE,
                self::UPLOAD_TASK_FILES,
                self::VIEW_TASK_FILES,
                self::DOWNLOAD_TASK_FILES
            ]
        ];
    }

    /**
     * ✅ Check if permission exists
     * 
     * @param string $permission
     * @return bool
     */
    public static function isValidPermission(string $permission): bool
    {
        return in_array($permission, self::getAllPermissions());
    }

    /**
     * ✅ Get permission description
     * 
     * @param string $permission
     * @return string
     */
    public static function getPermissionDescription(string $permission): string
    {
        $descriptions = [
            self::CREATE_TASKS => 'Tạo tasks mới',
            self::VIEW_TASKS => 'Xem tasks',
            self::EDIT_TASKS => 'Chỉnh sửa tasks',
            self::DELETE_TASKS => 'Xóa tasks',
            self::VIEW_ALL_TASKS => 'Xem tất cả tasks trong hệ thống',
            self::EDIT_ANY_TASK => 'Chỉnh sửa bất kỳ task nào',
            self::DELETE_ANY_TASK => 'Xóa bất kỳ task nào',
            self::ASSIGN_TASKS => 'Gán tasks cho users',
            self::REVOKE_TASKS => 'Thu hồi tasks đã gán',
            self::UPDATE_TASK_STATUS => 'Cập nhật trạng thái task',
            self::UPDATE_ANY_TASK_STATUS => 'Cập nhật trạng thái bất kỳ task nào',
            self::MARK_TASK_COMPLETE => 'Đánh dấu task hoàn thành',
            self::CANCEL_TASKS => 'Hủy tasks',
            self::UPLOAD_TASK_FILES => 'Upload files cho tasks',
            self::DELETE_TASK_FILES => 'Xóa files của tasks',
            self::VIEW_TASK_FILES => 'Xem files của tasks',
            self::DOWNLOAD_TASK_FILES => 'Download files của tasks',
            self::MANAGE_USERS => 'Quản lý users',
            self::VIEW_USER_STATISTICS => 'Xem thống kê users',
            self::GENERATE_REPORTS => 'Tạo báo cáo',
            self::ADMIN_ACCESS => 'Truy cập chức năng admin',
            self::VIEW_FACULTY_DATA => 'Xem dữ liệu khoa',
            self::VIEW_CLASS_DATA => 'Xem dữ liệu lớp',
            self::VIEW_STUDENT_DATA => 'Xem dữ liệu sinh viên',
            self::VIEW_LECTURER_DATA => 'Xem dữ liệu giảng viên',
            self::CLEAR_CACHE => 'Xóa cache',
            self::VIEW_CACHE_STATUS => 'Xem trạng thái cache',
            self::INVALIDATE_CACHE => 'Invalidate cache',
            self::VIEW_SYSTEM_LOGS => 'Xem system logs',
            self::SYSTEM_MAINTENANCE => 'Bảo trì hệ thống',
            self::BACKUP_RESTORE => 'Backup và restore dữ liệu'
        ];

        return $descriptions[$permission] ?? 'Không có mô tả';
    }
}

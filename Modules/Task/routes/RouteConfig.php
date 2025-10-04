<?php

namespace Modules\Task\routes;

use Modules\Task\app\Http\Controllers\Task\TaskController;
use Modules\Task\app\Admin\Controllers\AdminTaskController;
use Modules\Task\app\Http\Controllers\CacheController;
use Modules\Task\app\Http\Controllers\Task\TaskMonitoringController;
use Modules\Task\app\Admin\Controllers\AdminCalendarController;
use Modules\Task\app\Lecturer\Controllers\LecturerTaskController;
use Modules\Task\app\Lecturer\Controllers\LecturerCalendarController;
use Modules\Task\app\Http\Controllers\Calendar\CalendarController;
use Modules\Task\app\Lecturer\Controllers\LecturerProfileController;
use Modules\Task\app\Lecturer\Controllers\LecturerClassController;
use Modules\Task\app\Student\Controllers\StudentTaskController;
use Modules\Task\app\Student\Controllers\StudentCalendarController;
use Modules\Task\app\Student\Controllers\StudentProfileController;
use Modules\Task\app\Student\Controllers\StudentClassController;


/**
 * Cấu hình routes cho module Task với JWT và phân quyền
 * 
 * Class này chứa tất cả cấu hình routes để dễ dàng quản lý và bảo trì
 */
class RouteConfig
{
    /**
     * Cấu hình routes cho tất cả người dùng đã đăng nhập (JWT)
     * 
     * @return array
     */
    public static function getCommonRoutes(): array
    {
        return [
            'middleware' => ['jwt'],
            'prefix' => 'v1',
            'tasks' => [
                'prefix' => 'tasks',
                'controller' => TaskController::class,
                'name' => 'tasks',
                'resource_only' => ['show', 'destroy', 'store'], // Cho phép show, destroy và store
                'additional_routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'my-tasks',
                        'action' => 'getMyTasks',
                        'name' => 'my-tasks'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'my-assigned-tasks',
                        'action' => 'getMyAssignedTasks',
                        'name' => 'my-assigned-tasks'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'statistics/my',
                        'action' => 'getMyStatistics',
                        'name' => 'my-statistics'
                    ],
                    [
                        'methods' => ['PATCH'],
                        'uri' => '{task}/status',
                        'action' => 'updateStatus',
                        'name' => 'update-status'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '{task}/files',
                        'action' => 'uploadFiles',
                        'name' => 'upload-files'
                    ],
                    [
                        'methods' => ['DELETE'],
                        'uri' => '{task}/files/{file}',
                        'action' => 'deleteFile',
                        'name' => 'delete-file'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'departments',
                        'action' => 'getDepartments',
                        'name' => 'departments'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'classes/by-department',
                        'action' => 'getClassesByDepartment',
                        'name' => 'classes.by-department'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'students/by-class',
                        'action' => 'getStudentsByClass',
                        'name' => 'students.by-class'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'lecturers',
                        'action' => 'getLecturers',
                        'name' => 'lecturers'
                    ]
                ]
            ]
        ];
    }

    /**
     * Cấu hình routes chỉ dành cho Giảng viên
     * 
     * @return array
     */
    public static function getLecturerRoutes(): array
    {
        return [
            'middleware' => ['jwt', 'lecturer'],
            'prefix' => 'v1',
            'lecturer-tasks' => [
                'prefix' => 'lecturer-tasks',
                'controller' => LecturerTaskController::class,
                'name' => 'lecturer-tasks',
                'additional_routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => '',
                        'action' => 'index',
                        'name' => 'index'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'created',
                        'action' => 'getCreatedTasks',
                        'name' => 'created'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'assigned',
                        'action' => 'getAssignedTasks',
                        'name' => 'assigned'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'statistics',
                        'action' => 'getLecturerStatistics',
                        'name' => 'statistics'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '',
                        'action' => 'store',
                        'name' => 'store'
                    ],
                    [
                        'methods' => ['PUT'],
                        'uri' => '{task}',
                        'action' => 'update',
                        'name' => 'update'
                    ],
                    [
                        'methods' => ['PATCH'],
                        'uri' => '{task}/assign',
                        'action' => 'assignTask',
                        'name' => 'assign'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '{task}/revoke',
                        'action' => 'revokeTask',
                        'name' => 'revoke'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'recurring',
                        'action' => 'createRecurringTask',
                        'name' => 'recurring'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'create-with-permissions',
                        'action' => 'createTaskWithPermissions',
                        'name' => 'create-with-permissions'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'generate-report',
                        'action' => 'generateReport',
                        'name' => 'generate-report'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'send-report-email',
                        'action' => 'sendReportEmail',
                        'name' => 'send-report-email'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '{task}/process-files',
                        'action' => 'processTaskFiles',
                        'name' => 'process-files'
                    ],
                ],
                'resource_actions' => ['show', 'destroy'] // Chỉ cho phép xem chi tiết và xóa
            ],
            'lecturer-calendar' => [
                'prefix' => 'lecturer-calendar',
                'controller' => LecturerCalendarController::class,
                'name' => 'lecturer-calendar',
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'events',
                        'action' => 'getLecturerEvents',
                        'name' => 'events'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/by-date',
                        'action' => 'getEventsByDate',
                        'name' => 'events.by-date'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/by-range',
                        'action' => 'getEventsByRange',
                        'name' => 'events.by-range'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/upcoming',
                        'action' => 'getUpcomingEvents',
                        'name' => 'events.upcoming'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/overdue',
                        'action' => 'getOverdueEvents',
                        'name' => 'events.overdue'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/count-by-status',
                        'action' => 'getEventsCountByStatus',
                        'name' => 'events.count-by-status'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'reminders',
                        'action' => 'getReminders',
                        'name' => 'reminders'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'reminders',
                        'action' => 'setReminder',
                        'name' => 'reminders.store'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'events',
                        'action' => 'createEvent',
                        'name' => 'events.create'
                    ],
                    [
                        'methods' => ['PUT'],
                        'uri' => 'events/{event}',
                        'action' => 'updateEvent',
                        'name' => 'events.update'
                    ],
                    [
                        'methods' => ['DELETE'],
                        'uri' => 'events/{event}',
                        'action' => 'deleteEvent',
                        'name' => 'events.delete'
                    ],
                ]
            ],
            'lecturer-profile' => [
                'prefix' => 'lecturer-profile',
                'controller' => LecturerProfileController::class,
                'name' => 'lecturer-profile',
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => '',
                        'action' => 'show',
                        'name' => 'show'
                    ],
                    [
                        'methods' => ['PUT'],
                        'uri' => '',
                        'action' => 'update',
                        'name' => 'update'
                    ],
                ]
            ],
            'lecturer-classes' => [
                'prefix' => 'lecturer-classes',
                'controller' => LecturerClassController::class,
                'name' => 'lecturer-classes',
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => '',
                        'action' => 'getLecturerClasses',
                        'name' => 'index'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => '{class}/students',
                        'action' => 'getClassStudents',
                        'name' => 'students'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '{class}/announcements',
                        'action' => 'createAnnouncement',
                        'name' => 'announcements'
                    ],
                ]
            ],
            'calendar' => [
                'prefix' => 'calendar',
                'name' => 'calendar',
                'controller' => CalendarController::class,
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/by-date',
                        'action' => 'getEventsByDate',
                        'name' => 'events.by-date'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/by-range',
                        'action' => 'getEventsByRange',
                        'name' => 'events.by-range'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/upcoming',
                        'action' => 'getUpcomingEvents',
                        'name' => 'events.upcoming'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/overdue',
                        'action' => 'getOverdueEvents',
                        'name' => 'events.overdue'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/count-by-status',
                        'action' => 'getEventsCountByStatus',
                        'name' => 'events.count-by-status'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'reminders',
                        'action' => 'getReminders',
                        'name' => 'reminders.index'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'reminders',
                        'action' => 'setReminder',
                        'name' => 'reminders.store'
                    ],
                ]
            ],
            'email' => [
                'prefix' => 'email',
                'name' => 'email',
                'controller' => \Modules\Task\app\Http\Controllers\Email\EmailController::class,
                'routes' => [
                    [
                        'methods' => ['POST'],
                        'uri' => 'send-report',
                        'action' => 'sendReportEmail',
                        'name' => 'send-report'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'send-notification',
                        'action' => 'sendNotificationEmail',
                        'name' => 'send-notification'
                    ],
                ]
            ]
        ];
    }

    /**
     * Cấu hình routes chỉ dành cho Sinh viên
     * 
     * @return array
     */
    public static function getStudentRoutes(): array
    {
        return [
            'middleware' => ['jwt', 'student'],
            'prefix' => 'v1',
            'student-tasks' => [
                'prefix' => 'student-tasks',
                'controller' => StudentTaskController::class,
                'name' => 'student-tasks',
                'additional_routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => '',
                        'action' => 'index',
                        'name' => 'index'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'pending',
                        'action' => 'getPendingTasks',
                        'name' => 'pending'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'submitted',
                        'action' => 'getSubmittedTasks',
                        'name' => 'submitted'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'overdue',
                        'action' => 'getOverdueTasks',
                        'name' => 'overdue'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'statistics',
                        'action' => 'getStudentStatistics',
                        'name' => 'statistics'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '{task}/submit',
                        'action' => 'submitTask',
                        'name' => 'submit'
                    ],
                    [
                        'methods' => ['PUT'],
                        'uri' => '{task}/submission',
                        'action' => 'updateSubmission',
                        'name' => 'update-submission'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => '{task}/submission',
                        'action' => 'getSubmission',
                        'name' => 'get-submission'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '{task}/upload-file',
                        'action' => 'uploadFile',
                        'name' => 'upload-file'
                    ],
                    [
                        'methods' => ['DELETE'],
                        'uri' => '{task}/files/{file}',
                        'action' => 'deleteFile',
                        'name' => 'delete-file'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => '{task}/files',
                        'action' => 'getFiles',
                        'name' => 'get-files'
                    ],
                ],
                'resource_actions' => ['show'] // Chỉ cho phép xem chi tiết
            ],
            'student-calendar' => [
                'prefix' => 'student-calendar',
                'controller' => StudentCalendarController::class,
                'name' => 'student-calendar',
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'events',
                        'action' => 'getStudentEvents',
                        'name' => 'events'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/by-date',
                        'action' => 'getEventsByDate',
                        'name' => 'events.by-date'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/by-range',
                        'action' => 'getEventsByRange',
                        'name' => 'events.by-range'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/upcoming',
                        'action' => 'getUpcomingEvents',
                        'name' => 'events.upcoming'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/overdue',
                        'action' => 'getOverdueEvents',
                        'name' => 'events.overdue'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/count-by-status',
                        'action' => 'getEventsCountByStatus',
                        'name' => 'events.count-by-status'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'reminders',
                        'action' => 'getReminders',
                        'name' => 'reminders'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'setReminder',
                        'action' => 'setReminder',
                        'name' => 'set-reminder'
                    ],
                ]
            ],
            'student-profile' => [
                'prefix' => 'student-profile',
                'controller' => StudentProfileController::class,
                'name' => 'student-profile',
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => '',
                        'action' => 'show',
                        'name' => 'show'
                    ],
                    [
                        'methods' => ['PUT'],
                        'uri' => '',
                        'action' => 'update',
                        'name' => 'update'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'class-info',
                        'action' => 'getClassInfo',
                        'name' => 'class-info'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'grades',
                        'action' => 'getGrades',
                        'name' => 'grades'
                    ],
                ]
            ],
            'student-class' => [
                'prefix' => 'student-class',
                'controller' => StudentClassController::class,
                'name' => 'student-class',
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => '',
                        'action' => 'getStudentClass',
                        'name' => 'show'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'classmates',
                        'action' => 'getClassmates',
                        'name' => 'classmates'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'lecturers',
                        'action' => 'getClassLecturers',
                        'name' => 'lecturers'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'announcements',
                        'action' => 'getClassAnnouncements',
                        'name' => 'announcements'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'schedule',
                        'action' => 'getClassSchedule',
                        'name' => 'schedule'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'attendance',
                        'action' => 'getAttendance',
                        'name' => 'attendance'
                    ],
                ]
            ]
        ];
    }

    /**
     * Cấu hình routes chỉ dành cho Admin
     * 
     * @return array
     */
    public static function getAdminRoutes(): array
    {
        return [
            'middleware' => ['jwt', 'admin'],
            'prefix' => 'v1',
            'tasks' => [
                'prefix' => 'tasks',
                'controller' => AdminTaskController::class,
                'name' => 'tasks',
                'additional_routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => '',
                        'action' => 'index',
                        'name' => 'index'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'admin/all',
                        'action' => 'getAllTasks',
                        'name' => 'all'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'statistics/overview',
                        'action' => 'getOverviewStatistics',
                        'name' => 'overview-statistics'
                    ],
                    [
                        'methods' => ['DELETE'],
                        'uri' => '{task}/force',
                        'action' => 'forceDelete',
                        'name' => 'force-delete'
                    ],
                ],
                'resource_actions' => [] // Không sử dụng resource routes để tránh conflict với /tasks/all
            ],
            'admin-tasks' => [
                'prefix' => 'admin-tasks',
                'controller' => AdminTaskController::class,
                'name' => 'admin-tasks',
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'check-role',
                        'action' => 'checkAdminRole',
                        'name' => 'check-role'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'lecturers',
                        'action' => 'getLecturers',
                        'name' => 'lecturers'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'departments',
                        'action' => 'getDepartments',
                        'name' => 'departments'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'assign',
                        'action' => 'assignTaskToLecturers',
                        'name' => 'assign'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'assigned',
                        'action' => 'getAssignedTasks',
                        'name' => 'assigned'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => '{taskId}',
                        'action' => 'getTaskDetail',
                        'name' => 'detail'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '{taskId}/restore',
                        'action' => 'restore',
                        'name' => 'restore'
                    ],
                ]
            ],
            'calendar' => [
                'prefix' => 'calendar',
                'name' => 'calendar',
                'controller' => AdminCalendarController::class,
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'events',
                        'action' => 'getAllEvents',
                        'name' => 'events.index'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/by-type',
                        'action' => 'getEventsByType',
                        'name' => 'events.by-type'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'events/recurring',
                        'action' => 'getRecurringEvents',
                        'name' => 'events.recurring'
                    ],
                ]
            ],
            'monitoring' => [
                'prefix' => 'monitoring',
                'controller' => \Modules\Task\app\Http\Controllers\TaskMonitoringController::class,
                'name' => 'monitoring',
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'metrics',
                        'action' => 'getMetrics',
                        'name' => 'metrics'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'health',
                        'action' => 'healthCheck',
                        'name' => 'health'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'dashboard',
                        'action' => 'getDashboardData',
                        'name' => 'dashboard'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'alerts/acknowledge',
                        'action' => 'acknowledgeAlert',
                        'name' => 'alerts.acknowledge'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'logs',
                        'action' => 'getLogs',
                        'name' => 'logs'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'maintenance',
                        'action' => 'performMaintenance',
                        'name' => 'maintenance'
                    ]
                ]
            ],
            'cache' => [
                'prefix' => 'cache',
                'controller' => CacheController::class,
                'name' => 'cache',
                'additional_routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'health',
                        'action' => 'getHealth',
                        'name' => 'health'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'invalidate/student',
                        'action' => 'invalidateStudentCache',
                        'name' => 'invalidate-student'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'invalidate/lecturer',
                        'action' => 'invalidateLecturerCache',
                        'name' => 'invalidate-lecturer'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'invalidate/department',
                        'action' => 'invalidateDepartmentCache',
                        'name' => 'invalidate-department'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'invalidate/class',
                        'action' => 'invalidateClassCache',
                        'name' => 'invalidate-class'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'invalidate/bulk',
                        'action' => 'invalidateBulkCache',
                        'name' => 'invalidate-bulk'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'invalidate/all',
                        'action' => 'invalidateAllCache',
                        'name' => 'invalidate-all'
                    ]
                ]
            ]
        ];
    }

    /**
     * Lấy tất cả cấu hình routes theo cấp độ phân quyền
     * 
     * @return array
     */
    public static function getAllRoutes(): array
    {
        return [
            'common' => self::getCommonRoutes(),
            'lecturer' => self::getLecturerRoutes(),
            'student' => self::getStudentRoutes(),
            'admin' => self::getAdminRoutes(),
        ];
    }
}

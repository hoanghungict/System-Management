<?php

namespace Modules\Task\routes;

use Modules\Task\app\Http\Controllers\Task\TaskController;
use Modules\Task\app\Http\Controllers\Task\TaskDependencyController;
use Modules\Task\app\Http\Controllers\Task\TaskSubmitController;
use Modules\Task\app\Http\Controllers\Admin\AdminTaskController;
use Modules\Task\app\Http\Controllers\Lecturer\LecturerTaskController;
use Modules\Task\app\Http\Controllers\Student\StudentTaskController;
use Modules\Task\app\Http\Controllers\Reports\TaskReportController;
use Modules\Task\app\Http\Controllers\Statistics\TaskStatisticsController;
use Modules\Task\app\Http\Controllers\CacheController;
use Modules\Task\app\Http\Controllers\Task\TaskMonitoringController;
use Modules\Task\app\Http\Controllers\Calendar\CalendarController;
use Modules\Task\app\Http\Controllers\Reminder\ReminderController;
use Modules\Task\app\Http\Controllers\Email\EmailController;


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
                'resource_only' => ['index', 'show'], // Cho phép index và show
                'exclude_routes' => ['update', 'destroy'], // Loại bỏ update và destroy vì chỉ admin được cập nhật/xóa
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
                        'uri' => '{task}/submit',
                        'action' => 'submitTask',
                        'name' => 'submit-task',
                        'controller' => TaskSubmitController::class
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
            ],
            'task-dependencies' => [
                'prefix' => 'task-dependencies',
                'controller' => TaskDependencyController::class,
                'name' => 'task-dependencies',
                'additional_routes' => [
                    // Dependencies for specific task
                    [
                        'methods' => ['GET'],
                        'uri' => 'task/{taskId}',
                        'action' => 'index',
                        'name' => 'by-task'
                    ],
                    // Create dependency
                    [
                        'methods' => ['POST'],
                        'uri' => '',
                        'action' => 'store',
                        'name' => 'store'
                    ],
                    // Show dependency
                    [
                        'methods' => ['GET'],
                        'uri' => '{dependencyId}',
                        'action' => 'show',
                        'name' => 'show'
                    ],
                    // Update dependency
                    [
                        'methods' => ['PUT', 'PATCH'],
                        'uri' => '{dependencyId}',
                        'action' => 'update',
                        'name' => 'update'
                    ],
                    // Delete dependency
                    [
                        'methods' => ['DELETE'],
                        'uri' => '{dependencyId}',
                        'action' => 'destroy',
                        'name' => 'destroy'
                    ],
                    // Task with dependencies
                    [
                        'methods' => ['GET'],
                        'uri' => 'task/{taskId}/with-dependencies',
                        'action' => 'getTaskWithDependencies',
                        'name' => 'task-with-dependencies'
                    ],
                    // Validate dependency
                    [
                        'methods' => ['POST'],
                        'uri' => 'validate',
                        'action' => 'validate',
                        'name' => 'validate'
                    ],
                    // Check if task can start
                    [
                        'methods' => ['GET'],
                        'uri' => 'task/{taskId}/can-start',
                        'action' => 'canTaskStart',
                        'name' => 'can-start'
                    ],
                    // Get blocked tasks
                    [
                        'methods' => ['GET'],
                        'uri' => 'task/{taskId}/blocked-tasks',
                        'action' => 'getBlockedTasks',
                        'name' => 'blocked-tasks'
                    ],
                    // Get dependency chain
                    [
                        'methods' => ['GET'],
                        'uri' => 'task/{taskId}/dependency-chain',
                        'action' => 'getDependencyChain',
                        'name' => 'dependency-chain'
                    ],
                    // Bulk operations
                    [
                        'methods' => ['POST'],
                        'uri' => 'bulk-create',
                        'action' => 'bulkStore',
                        'name' => 'bulk-create'
                    ],
                    [
                        'methods' => ['DELETE'],
                        'uri' => 'bulk-delete',
                        'action' => 'bulkDestroy',
                        'name' => 'bulk-delete'
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
                        'uri' => '{task}/process-files',
                        'action' => 'processTaskFiles',
                        'name' => 'process-files'
                    ],
                ],
                'resource_actions' => ['show', 'destroy'] // Chỉ cho phép xem chi tiết và xóa
            ],
            'lecturer-calendar' => [
                'prefix' => 'lecturer-calendar',
                'controller' => \Modules\Task\app\Lecturer\Controllers\LecturerCalendarController::class,
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
            // Profile APIs được xử lý bởi Auth Module
            // Không cần profile APIs trong Task Module
            'lecturer-classes' => [
                'prefix' => 'lecturer-classes',
                'controller' => \Modules\Task\app\Lecturer\Controllers\LecturerClassController::class,
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
            // Calendar APIs đã được tích hợp vào lecturer-calendar và student-calendar
            // Không cần calendar chung vì mỗi role có calendar riêng
            'reminders' => [
                'prefix' => 'reminders',
                'name' => 'reminders',
                'controller' => ReminderController::class,
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => '',
                        'action' => 'index',
                        'name' => 'index'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '',
                        'action' => 'store',
                        'name' => 'store'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => '{id}',
                        'action' => 'show',
                        'name' => 'show'
                    ],
                    [
                        'methods' => ['PUT', 'PATCH'],
                        'uri' => '{id}',
                        'action' => 'update',
                        'name' => 'update'
                    ],
                    [
                        'methods' => ['DELETE'],
                        'uri' => '{id}',
                        'action' => 'destroy',
                        'name' => 'destroy'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'process-due',
                        'action' => 'processDue',
                        'name' => 'process-due'
                    ],
                ]
            ],
            'statistics' => [
                'prefix' => 'statistics',
                'name' => 'statistics',
                'controller' => TaskStatisticsController::class,
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'user',
                        'action' => 'getUserStatistics',
                        'name' => 'user'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'created',
                        'action' => 'getCreatedStatistics',
                        'name' => 'created'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'overview',
                        'action' => 'getOverviewStatistics',
                        'name' => 'overview'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'completion-rate',
                        'action' => 'getTaskCompletionRate',
                        'name' => 'completion-rate'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'priority-distribution',
                        'action' => 'getTaskPriorityDistribution',
                        'name' => 'priority-distribution'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'status-distribution',
                        'action' => 'getTaskStatusDistribution',
                        'name' => 'status-distribution'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'trend',
                        'action' => 'getTaskTrend',
                        'name' => 'trend'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'breakdown-by-class',
                        'action' => 'getTaskBreakdownByClass',
                        'name' => 'breakdown-by-class'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'breakdown-by-department',
                        'action' => 'getTaskBreakdownByDepartment',
                        'name' => 'breakdown-by-department'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'submission-rate',
                        'action' => 'getTaskSubmissionRate',
                        'name' => 'submission-rate'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'grading-status',
                        'action' => 'getTaskGradingStatus',
                        'name' => 'grading-status'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'dependency-statistics',
                        'action' => 'getTaskDependencyStatistics',
                        'name' => 'dependency-statistics'
                    ],
                ]
            ],
            'reports' => [
                'prefix' => 'reports',
                'name' => 'reports',
                'controller' => TaskReportController::class,
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => 'export/excel',
                        'action' => 'exportExcel',
                        'name' => 'export-excel'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'export/pdf',
                        'action' => 'exportPdf',
                        'name' => 'export-pdf'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'export/csv',
                        'action' => 'exportCsv',
                        'name' => 'export-csv'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'comprehensive',
                        'action' => 'generateComprehensiveReport',
                        'name' => 'comprehensive'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'student/{studentId}/progress',
                        'action' => 'generateStudentProgressReport',
                        'name' => 'student-progress'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'class/{classId}/performance',
                        'action' => 'generateClassPerformanceReport',
                        'name' => 'class-performance'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'formats',
                        'action' => 'getExportFormats',
                        'name' => 'formats'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'dashboard-summary',
                        'action' => 'getDashboardSummary',
                        'name' => 'dashboard-summary'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'recent-activities',
                        'action' => 'getRecentActivities',
                        'name' => 'recent-activities'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'overdue-tasks',
                        'action' => 'getOverdueTasks',
                        'name' => 'overdue-tasks'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'upcoming-deadlines',
                        'action' => 'getUpcomingDeadlines',
                        'name' => 'upcoming-deadlines'
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
                'controller' => \Modules\Task\app\Student\Controllers\StudentCalendarController::class,
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
            // Profile APIs được xử lý bởi Auth Module
            // Không cần profile APIs trong Task Module
            'student-class' => [
                'prefix' => 'student-class',
                'controller' => \Modules\Task\app\Student\Controllers\StudentClassController::class,
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
            'middleware' => ['jwt', 'admin', 'lecturer'],
            'prefix' => 'v1',
            'admin-tasks' => [
                'prefix' => 'admin-tasks',
                'controller' => AdminTaskController::class,
                'name' => 'admin-tasks',
                'routes' => [
                    [
                        'methods' => ['GET'],
                        'uri' => '',
                        'action' => 'index',
                        'name' => 'index'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => '',
                        'action' => 'store',
                        'name' => 'store'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => '{id}',
                        'action' => 'show',
                        'name' => 'show'
                    ],
                    [
                        'methods' => ['PUT', 'PATCH'],
                        'uri' => '{id}',
                        'action' => 'update',
                        'name' => 'update'
                    ],
                    [
                        'methods' => ['DELETE'],
                        'uri' => '{id}',
                        'action' => 'destroy',
                        'name' => 'destroy'
                    ],
                    [
                        'methods' => ['GET'],
                        'uri' => 'system-statistics',
                        'action' => 'getSystemStatistics',
                        'name' => 'system-statistics'
                    ],
                    [
                        'methods' => ['PATCH'],
                        'uri' => '{id}/override-status',
                        'action' => 'overrideStatus',
                        'name' => 'override-status'
                    ],
                    [
                        'methods' => ['POST'],
                        'uri' => 'bulk-action',
                        'action' => 'bulkAction',
                        'name' => 'bulk-action'
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

<?php

return [
    // Redis patterns to psubscribe
    'patterns' => [
        'task.*',
        'student.*',
        'lecturer.*',
        'course.*',
        'library.*',
        'recruitment.*',
    ],

    // Map exact channels or wildcard patterns to handlers
    'handlers' => [
        // 'user.registered' => Modules\Notifications\app\Handlers\UserRegisteredHandler::class,
        'task.assigned' => Modules\Notifications\app\Handlers\TaskHandle\TaskAssignedHandler::class,
        'student.registered' => Modules\Notifications\app\Handlers\StudentHandle\RegisterStudentHandle::class,
        // 'lecturer.registered' => Modules\Notifications\app\Handlers\LecturerHandle\RegisterLecturerHandle::class,
        // 'task.completed' => Modules\Notifications\app\Handlers\TaskCompletedHandler::class,
        // 'task.overdue' => Modules\Notifications\app\Handlers\TaskOverdueHandler::class,
        // 'course.enrolled' => Modules\Notifications\app\Handlers\CourseEnrolledHandler::class,
        // 'course.completed' => Modules\Notifications\app\Handlers\CourseCompletedHandler::class,
        // 'library.due' => Modules\Notifications\app\Handlers\LibraryDueHandler::class,
        // 'library.overdue' => Modules\Notifications\app\Handlers\LibraryOverdueHandler::class,
        // 'recruitment.applied' => Modules\Notifications\app\Handlers\RecruitmentAppliedHandler::class,
        // 'recruitment.approved' => Modules\Notifications\app\Handlers\RecruitmentApprovedHandler::class,
        // 'system.announcement' => Modules\Notifications\app\Handlers\SystemAnnouncementHandler::class,
    ],
];



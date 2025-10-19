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
        'student.registered' => Modules\Notifications\app\Handlers\StudentHandle\RegisterStudentHandle::class,
        // 'lecturer.registered' => Modules\Notifications\app\Handlers\LecturerHandle\RegisterLecturerHandle::class,
        
        // Task Event Handlers
        'task.created' => Modules\Notifications\app\Handlers\TaskHandle\TaskCreatedHandler::class,
        'task.updated' => Modules\Notifications\app\Handlers\TaskHandle\TaskUpdatedHandler::class,
        'task.assigned' => Modules\Notifications\app\Handlers\TaskHandle\TaskAssignedHandler::class,
        'task.submitted' => Modules\Notifications\app\Handlers\TaskHandle\TaskSubmittedHandler::class,
        'task.graded' => Modules\Notifications\app\Handlers\TaskHandle\TaskGradedHandler::class,
        
        // Reminder Handlers
        'reminder.task.deadline' => Modules\Notifications\app\Handlers\ReminderHandle\TaskDeadlineReminderHandler::class,
        'reminder.task.overdue' => Modules\Notifications\app\Handlers\ReminderHandle\TaskOverdueHandler::class,
        
        'official.dispatch' => Modules\Notifications\app\Handlers\OfficialDispatchHandle\SendOfficialHandle::class,
        'lecturer.registered' => Modules\Notifications\app\Handlers\LecturerHandle\RegisterLecturerHandle::class,
        'official.dispatch.status.update' => Modules\Notifications\app\Handlers\OfficialDispatchHandle\NotifyOnDispatchStatusUpdateHandle::class,
        'quiz.result' => Modules\Notifications\app\Handlers\Quiz\QuizResultHandle::class,
        'lecturer.create.course' => Modules\Notifications\app\Handlers\CourseHandle\LecturerCreateCourseHandle::class,
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

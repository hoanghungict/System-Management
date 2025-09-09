<?php

return [
    // Redis patterns to psubscribe
    'patterns' => [
        'task.*',
        'course.*',
        'library.*',
        'recruitment.*',
    ],

    // Map exact channels or wildcard patterns to handlers
    'handlers' => [
        'task.assigned' => Modules\Notifications\app\Handlers\TaskAssignedHandler::class,
        // 'course.*' => Modules\Notifications\app\Handlers\CourseHandler::class,
        // 'library.due' => Modules\Notifications\app\Handlers\LibraryDueHandler::class,
        // 'recruitment.*' => Modules\Notifications\app\Handlers\RecruitmentHandler::class,
    ],
];



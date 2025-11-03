<?php

return [
    'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),
    'group_id' => env('KAFKA_GROUP_ID', 'notifications-consumer'),
    'topics' => [
        'student_account_created' => 'student.registered',
        'lecturer_account_created' => 'lecturer.registered',
        'task_assigned' => 'task.assigned',
        'official_dispatch' => 'official.dispatch',
        'official_dispatch_status_update' => 'official.dispatch.status.update',
        'quiz_result' => 'quiz.result',
        'lecturer_create_course' => 'lecturer.create.course',
        'task_submission' => 'task.submission',
    ],
];

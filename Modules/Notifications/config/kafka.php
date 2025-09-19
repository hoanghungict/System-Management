<?php

return [
    'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),
    'group_id' => env('KAFKA_GROUP_ID', 'notifications-consumer'),
    'topics' => [
        'student_account_created' => 'student.registered',
        'lecturer_account_created' => 'lecturer.registered',
        'task_assigned' => 'task.assigned',
    ],
];
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Configuration for Task Module
    |--------------------------------------------------------------------------
    |
    | Cấu hình email cho module Task
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Task Management System'),
    ],

    'markdown' => [
        'theme' => 'default',
        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Module Email Settings
    |--------------------------------------------------------------------------
    */

    'task_module' => [
        // Email templates
        'templates' => [
            'reports' => [
                'daily' => 'emails.reports.daily',
                'weekly' => 'emails.reports.weekly',
                'monthly' => 'emails.reports.monthly',
                'performance' => 'emails.reports.performance',
                'analytics' => 'emails.reports.analytics',
                'default' => 'emails.reports.default'
            ],
            'notifications' => [
                'task_created' => 'emails.notifications.task_created',
                'task_updated' => 'emails.notifications.task_updated',
                'task_completed' => 'emails.notifications.task_completed',
                'task_assigned' => 'emails.notifications.task_assigned'
            ]
        ],

        // Email subjects
        'subjects' => [
            'reports' => [
                'daily' => 'Báo cáo hàng ngày - {date}',
                'weekly' => 'Báo cáo hàng tuần - {week}',
                'monthly' => 'Báo cáo hàng tháng - {month}',
                'performance' => 'Báo cáo hiệu suất - {date}',
                'analytics' => 'Báo cáo phân tích - {date}',
                'default' => 'Báo cáo Task - {date}'
            ],
            'notifications' => [
                'task_created' => 'Task mới được tạo: {task_title}',
                'task_updated' => 'Task đã được cập nhật: {task_title}',
                'task_completed' => 'Task đã hoàn thành: {task_title}',
                'task_assigned' => 'Task được giao: {task_title}'
            ]
        ],

        // Email settings
        'settings' => [
            'max_recipients_per_email' => env('EMAIL_MAX_RECIPIENTS', 50),
            'max_attachments_per_email' => env('EMAIL_MAX_ATTACHMENTS', 10),
            'max_attachment_size' => env('EMAIL_MAX_ATTACHMENT_SIZE', 10485760), // 10MB
            'retry_attempts' => env('EMAIL_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('EMAIL_RETRY_DELAY', 300), // 5 minutes
            'queue_timeout' => env('EMAIL_QUEUE_TIMEOUT', 120), // 2 minutes
        ],

        // Email monitoring
        'monitoring' => [
            'enabled' => env('EMAIL_MONITORING_ENABLED', true),
            'success_rate_threshold' => env('EMAIL_SUCCESS_RATE_THRESHOLD', 95), // 95%
            'alert_on_failure' => env('EMAIL_ALERT_ON_FAILURE', true),
            'alert_recipients' => env('EMAIL_ALERT_RECIPIENTS', []),
        ],

        // Email logging
        'logging' => [
            'enabled' => env('EMAIL_LOGGING_ENABLED', true),
            'log_success' => env('EMAIL_LOG_SUCCESS', true),
            'log_failure' => env('EMAIL_LOG_FAILURE', true),
            'log_retention_days' => env('EMAIL_LOG_RETENTION_DAYS', 30),
        ],

        // Email queue
        'queue' => [
            'name' => env('EMAIL_QUEUE_NAME', 'emails'),
            'connection' => env('EMAIL_QUEUE_CONNECTION', 'redis'),
            'batch_size' => env('EMAIL_BATCH_SIZE', 100),
            'delay_between_batches' => env('EMAIL_DELAY_BETWEEN_BATCHES', 60), // 1 minute
        ],

        // Email templates path
        'template_path' => resource_path('views/emails'),

        // Email test settings
        'test' => [
            'enabled' => env('EMAIL_TEST_ENABLED', false),
            'test_email' => env('EMAIL_TEST_EMAIL', 'test@example.com'),
            'test_recipients' => env('EMAIL_TEST_RECIPIENTS', []),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Validation Rules
    |--------------------------------------------------------------------------
    */

    'validation' => [
        'email' => 'required|email|max:255',
        'subject' => 'required|string|max:255',
        'content' => 'required|string|max:10000',
        'recipients' => 'required|array|min:1',
        'recipients.*' => 'required|string|email',
        'attachments' => 'array',
        'attachments.*.path' => 'required|string|file',
        'attachments.*.name' => 'string|max:255',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Error Messages
    |--------------------------------------------------------------------------
    */

    'error_messages' => [
        'invalid_email' => 'Email không hợp lệ',
        'invalid_recipients' => 'Danh sách người nhận không hợp lệ',
        'invalid_subject' => 'Tiêu đề email không hợp lệ',
        'invalid_content' => 'Nội dung email không hợp lệ',
        'invalid_attachment' => 'File đính kèm không hợp lệ',
        'too_many_recipients' => 'Số lượng người nhận vượt quá giới hạn',
        'too_many_attachments' => 'Số lượng file đính kèm vượt quá giới hạn',
        'attachment_too_large' => 'File đính kèm quá lớn',
        'sending_failed' => 'Gửi email thất bại',
        'connection_failed' => 'Kết nối email thất bại',
    ],
];

<?php
    return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // ⚠️ KHÔNG để '*' khi dùng credentials
    'allowed_origins' => ['http://localhost:3001'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // ⚠️ BẮT BUỘC PHẢI TRUE nếu dùng cookie/session
    'supports_credentials' => true,
    ];
?>
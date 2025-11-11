<?php
    return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // ⚠️ KHÔNG để '*' khi dùng credentials
    'allowed_origins' => ['http://localhost:3001','http://localhost:3000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // Cache preflight requests trong 1 giờ để giảm số lượng preflight requests
    'max_age' => 3600,

    // Không cần supports_credentials vì đang dùng Bearer token thay vì cookies
    'supports_credentials' => true,
    // 'supports_credentials' => false,
    ];
?>
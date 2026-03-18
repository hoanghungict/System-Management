<?php
return [
    "paths" => [
        "api/*",
        "broadcasting/auth",
        "sanctum/csrf-cookie",
        "storage/*",
    ],

    "allowed_methods" => ["*"],

    // ⚠️ KHÔNG để '*' khi dùng credentials
    // Cho phép các origin FE phổ biến trong môi trường dev
    "allowed_origins" => [
        "http://localhost:3000",
        "http://localhost:3001",
        "http://127.0.0.1:3000",
        "http://127.0.0.1:3001",
        "http://localhost:5173",
        "http://127.0.0.1:5173",

        "http://103.126.161.228:3001",
        "http://103.126.161.228:3000",
        "http://hethongdientu.khoacongnghethongtinhpc.io.vn",
        "https://hethongdientu.khoacongnghethongtinhpc.io.vn",
        "http://hethongdientu.khoacongnghethongtinhpc.io.vn:3001",
        "https://hethongdientu.khoacongnghethongtinhpc.io.vn:3001",
    ],

    "allowed_origins_patterns" => [],

    "allowed_headers" => ["*"],

    "exposed_headers" => [],

    "max_age" => 3600,

    // ⚠️ BẮT BUỘC PHẢI TRUE nếu dùng cookie/session
    "supports_credentials" => true,
];
?>

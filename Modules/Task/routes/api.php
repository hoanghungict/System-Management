<?php

use Modules\Task\routes\RouteHelper;
use Modules\Task\routes\RouteConfig;

/*
|--------------------------------------------------------------------------
| Task Module API Routes
|--------------------------------------------------------------------------
|
| Đây là các routes API cho module Task với JWT authentication và phân quyền
| Sử dụng RouteHelper và RouteConfig để quản lý routes một cách rút gọn
|
*/

// Lấy cấu hình routes
$allRoutes = RouteConfig::getAllRoutes();

// Đăng ký routes cho tất cả người dùng đã đăng nhập (JWT)
RouteHelper::registerRoutesGroup($allRoutes['common']);

// Đăng ký routes chỉ dành cho Giảng viên
RouteHelper::registerRoutesGroup($allRoutes['lecturer']);

// Đăng ký routes chỉ dành cho Sinh viên
RouteHelper::registerRoutesGroup($allRoutes['student']);

// Đăng ký routes chỉ dành cho Admin
RouteHelper::registerRoutesGroup($allRoutes['admin']);

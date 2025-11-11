# Hệ Thống Quản Lý Trường Học - Backend API

## 🚀 Công Nghệ

### Core Framework
- **Laravel 12** (PHP 8.3)
- **Module-based Architecture** (nwidart/laravel-modules)
- **RESTful API** với Swagger/OpenAPI documentation

### Authentication & Security
- **JWT Authentication** (Firebase JWT)
- **Role-based Access Control** (Admin, Lecturer, Student)
- **CORS** configured

### Infrastructure & Services
- **Kafka** - Event-driven messaging
- **Redis** - Caching layer
- **Laravel Reverb** - WebSockets for real-time
- **Laravel Queue** - Background jobs
- **MySQL/SQLite** - Database

### Development Tools
- **L5-Swagger** - API documentation
- **Laravel Pint** - Code formatting
- **PHPUnit** - Testing
- **Laravel Pail** - Log viewer

## 📦 Chức Năng Chính

### 1. **Authentication & User Management (Auth Module)**
- 🔐 Đăng nhập/đăng xuất với JWT
- 👥 Quản lý người dùng: Sinh viên, Giảng viên, Admin
- 🏢 Quản lý khoa/phòng ban (Department)
- 📚 Quản lý lớp học (Class)
- ✅ Điểm danh (Roll Call) - Class-based và Manual
- 🔑 Đổi mật khẩu

### 2. **Task Management (Task Module)**
- 📋 Quản lý nhiệm vụ/bài tập
- 👨‍🏫 Phân công và giao việc
- 📝 Nộp bài và chấm điểm
- 📅 Calendar tích hợp
- ⏰ Hệ thống nhắc nhở tự động
- 📊 Thống kê và báo cáo
- 📎 Quản lý file đính kèm

### 3. **Notification System (Notifications Module)**
- 🔔 Thông báo đa kênh: Email, Push, SMS, In-app
- 📧 Template-based email system
- 🎯 Event-driven notifications (Kafka)
- 📬 Quản lý notification settings
- 📈 Notification history

### 4. **Roll Call System (Auth Module)**
- ✅ Điểm danh theo lớp (Class-based)
- ✅ Điểm danh thủ công (Manual)
- 📊 Thống kê điểm danh
- 📝 Quản lý trạng thái: Có mặt, Vắng mặt, Có phép, Muộn
- 🔄 Cập nhật hàng loạt

## 🏗️ Kiến Trúc

### Module Structure
```
HPCProject/
├── Modules/
│   ├── Auth/           # Authentication & User Management
│   ├── Task/           # Task/Công việc Management
│   └── Notifications/  # Notification System
├── app/                # Core application
├── config/             # Configuration files
├── database/           # Migrations & Seeders
└── routes/             # API routes
```

### Design Patterns
- **Repository Pattern** - Data access layer
- **Service Layer** - Business logic
- **DTO (Data Transfer Object)** - Data transformation
- **Event-Driven** - Async processing với Kafka
- **Clean Architecture** - Separation of concerns

## 🔌 API Endpoints

### Base URL
```
http://localhost:8080/api/v1
```

### Authentication
Tất cả endpoints (trừ login) yêu cầu JWT token:
```
Authorization: Bearer <jwt_token>
```

### Main Endpoints
- **Auth**: `/api/v1/login`, `/api/v1/me`, `/api/v1/logout`
- **Users**: `/api/v1/students`, `/api/v1/lecturers`
- **Tasks**: `/api/v1/tasks`, `/api/v1/lecturer-tasks`, `/api/v1/student-tasks`
- **Notifications**: `/api/v1/notifications`
- **Roll Call**: `/api/v1/roll-calls`

Xem chi tiết trong Swagger UI hoặc các file documentation:
- `API_README.md`
- `AUTH_API_DOCUMENTATION.md`
- `NOTIFICATION_API_DOCUMENTATION.md`
- `ROLLCALL_API_DOCUMENTATION.md`
- `Modules/Task/API_ENDPOINTS.md`

## 👥 Phân Quyền

### 🔧 Admin
- Quản lý toàn bộ hệ thống
- CRUD Users, Departments, Classes
- Xem tất cả tasks và thống kê
- Override task status

### 👨‍🏫 Lecturer (Giảng viên)
- Tạo và quản lý tasks
- Chấm điểm sinh viên
- Điểm danh lớp học
- Xem thống kê lớp

### 👨‍🎓 Student (Sinh viên)
- Xem tasks được giao
- Nộp bài và xem kết quả
- Xem thông tin cá nhân

## 🚀 Cài Đặt & Chạy

### Yêu cầu
- PHP 8.3+
- Composer
- MySQL/SQLite
- Redis (optional)
- Kafka (optional, cho notifications)

### Installation
```bash
# Clone repository
git clone <repository-url>
cd HPCProject

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed

# Generate Swagger documentation
php artisan l5-swagger:generate

# Start server
php artisan serve
```

### Docker
```bash
# Chạy với Docker
docker-compose up -d
```

## 📚 Documentation

- **API Documentation**: Swagger UI tại `/api/documentation`
- **Module Docs**: Xem trong từng module folder
- **Testing**: `php artisan test`

## 🔧 Development

```bash
# Development mode (server + queue + logs + vite)
composer dev

# Code formatting
./vendor/bin/pint

# Clear cache
php artisan optimize:clear
```

## 📝 Notes

- **Database**: Sử dụng SQLite cho development, MySQL cho production
- **CORS**: Configured trong `config/cors.php`
- **Queue**: Sử dụng Laravel Queue cho background jobs
- **Cache**: Redis cache layer cho performance
- **Events**: Kafka integration cho real-time notifications

## 📄 License

MIT License



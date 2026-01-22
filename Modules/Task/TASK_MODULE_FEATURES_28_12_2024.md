# 📋 Task Module Features - 28/12/2024

> **Tài liệu tổng hợp các chức năng hiện có trong Task Module sau cleanup**

---

## 📊 Tổng quan Module

| Thành phần | Số lượng |
|------------|----------|
| Controllers | 45 files |
| Services | 26 files |
| Repositories | 14 files |
| Models | 7 files |
| UseCases | 7 files |
| DTOs | 3 files |
| Transformers | 3 files |
| Events | 8 files |
| Jobs | 10 files |

---

## 🔐 Phân quyền API theo Role

### 1️⃣ Common Routes (Tất cả user đã đăng nhập)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/tasks` | Danh sách tasks |
| GET | `/api/v1/tasks/{task}` | Chi tiết task |
| GET | `/api/v1/tasks/my-tasks` | Tasks của tôi |
| GET | `/api/v1/tasks/my-assigned-tasks` | Tasks được giao |
| GET | `/api/v1/tasks/statistics/my` | Thống kê cá nhân |
| PATCH | `/api/v1/tasks/{task}/status` | Cập nhật trạng thái |
| POST | `/api/v1/tasks/{task}/submit` | Nộp bài |
| POST | `/api/v1/tasks/{task}/files` | Upload file |
| DELETE | `/api/v1/tasks/{task}/files/{file}` | Xóa file |
| GET | `/api/v1/tasks/{task}/files/{file}/download` | Download file |

---

### 2️⃣ Lecturer Routes (Giảng viên)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/lecturer-tasks` | Danh sách tasks của GV |
| POST | `/api/v1/lecturer-tasks` | Tạo task mới |
| GET | `/api/v1/lecturer-tasks/{task}` | Chi tiết task |
| PUT | `/api/v1/lecturer-tasks/{task}` | Cập nhật task |
| DELETE | `/api/v1/lecturer-tasks/{task}` | Xóa task |
| POST | `/api/v1/lecturer-tasks/{task}/assign` | Giao task cho SV |
| POST | `/api/v1/lecturer-tasks/{task}/revoke` | Thu hồi task |
| POST | `/api/v1/lecturer-tasks/recurring` | Tạo task định kỳ |
| POST | `/api/v1/lecturer-tasks/create-with-permissions` | Tạo task với phân quyền |
| POST | `/api/v1/lecturer-tasks/{task}/files` | Upload files |
| DELETE | `/api/v1/lecturer-tasks/{task}/files/{file}` | Xóa file |
| GET | `/api/v1/lecturer-tasks/{task}/files/{file}/download` | Download file |
| GET | `/api/v1/lecturer-tasks/{task}/submissions` | Xem bài nộp |
| POST | `/api/v1/lecturer-tasks/{task}/submissions/{id}/grade` | Chấm điểm |
| GET/POST/PUT/DELETE | `/api/v1/lecturer-calendar` | Calendar GV |

---

### 3️⃣ Student Routes (Sinh viên)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/student-tasks` | Danh sách tasks |
| GET | `/api/v1/student-tasks/{task}` | Chi tiết task |
| GET | `/api/v1/student-tasks/pending` | Tasks chưa làm |
| GET | `/api/v1/student-tasks/submitted` | Tasks đã nộp |
| GET | `/api/v1/student-tasks/overdue` | Tasks quá hạn |
| GET | `/api/v1/student-tasks/statistics` | Thống kê |
| POST | `/api/v1/student-tasks/{task}/submit` | Nộp bài |
| POST | `/api/v1/student-tasks/{task}/upload-file` | Upload file |
| GET | `/api/v1/student-tasks/{task}/files` | Xem files |
| DELETE | `/api/v1/student-tasks/{task}/files/{file}` | Xóa file |
| GET | `/api/v1/student-tasks/{task}/submission` | Xem bài nộp |
| PUT | `/api/v1/student-tasks/{task}/submission` | Cập nhật bài nộp |
| GET/POST/PUT/DELETE | `/api/v1/student-calendar` | Calendar SV |

---

### 4️⃣ Admin Routes (Quản trị viên)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/admin-tasks` | Tất cả tasks |
| POST | `/api/v1/admin-tasks` | Tạo task |
| GET | `/api/v1/admin-tasks/{task}` | Chi tiết |
| PUT | `/api/v1/admin-tasks/{task}` | Cập nhật |
| DELETE | `/api/v1/admin-tasks/{task}` | Xóa |

---

### 5️⃣ Miscellaneous Routes

| Resource | Endpoint | Mô tả |
|----------|----------|-------|
| Reports | `/api/v1/reports` | Báo cáo tasks |
| Statistics | `/api/v1/statistics` | Thống kê hệ thống |
| Reminders | `/api/v1/reminders` | Nhắc nhở |
| Email | `/api/v1/email` | Gửi email |

---

## 🏗️ Kiến trúc Code (Clean Architecture)

```
Modules/Task/app/
├── Admin/           # Admin-specific logic
│   ├── Controllers/
│   ├── Services/
│   ├── UseCases/
│   └── Providers/
├── Http/
│   └── Controllers/
│       ├── Task/Actions/      # Task Actions (Single Action Pattern)
│       ├── Lecturer/Actions/  # Lecturer Actions
│       ├── Admin/
│       ├── Calendar/
│       ├── Cache/
│       ├── Email/
│       ├── Monitoring/
│       ├── Reminder/
│       ├── Reports/
│       └── Statistics/
├── Services/
│   ├── Task/              # Specialized services
│   │   ├── TaskAssignmentService.php
│   │   ├── TaskCacheService.php
│   │   ├── TaskFileService.php
│   │   ├── TaskQueryService.php
│   │   ├── TaskStatisticsService.php
│   │   └── TaskValidationService.php
│   ├── TaskService.php    # Main service
│   ├── CacheService.php
│   └── EmailService.php
├── Repositories/          # Data access layer
├── Models/               # Eloquent models
├── UseCases/             # Business logic
├── DTOs/                 # Data Transfer Objects
├── Transformers/         # API Resources
├── Events/               # Domain events
├── Jobs/                 # Background jobs
├── Listeners/            # Event listeners
└── Monitoring/           # System monitoring
```

---

## 🗑️ Đã xóa trong cleanup (28/12/2024)

- ❌ **TaskDependency feature** - Không sử dụng
- ❌ **Duplicate Auth routes** - Dùng Auth module thay thế
- ❌ **50+ orphaned files** - Controllers, UseCases, Repositories không dùng
- ❌ **30+ documentation files** - README, GUIDE không cần thiết

---

## 📌 Lưu ý sử dụng Auth Module

Các dữ liệu departments, classes, students, lecturers lấy từ **Auth Module**:

```
GET /api/v1/departments     → Danh sách khoa
GET /api/v1/classes         → Danh sách lớp
GET /api/v1/lecturers       → Danh sách giảng viên
GET /api/v1/students        → Danh sách sinh viên
```

---

*Cập nhật: 28/12/2024*

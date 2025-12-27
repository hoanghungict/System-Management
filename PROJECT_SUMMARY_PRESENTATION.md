# 📊 HỆ THỐNG QUẢN LÝ TRƯỜNG HỌC - TÀI LIỆU TỔNG HỢP

> **Tài liệu tổng hợp công nghệ và chức năng cho slide thuyết trình báo cáo**

---

## 📑 MỤC LỤC

1. [Tổng Quan Hệ Thống](#1-tổng-quan-hệ-thống)
2. [Công Nghệ Sử Dụng](#2-công-nghệ-sử-dụng)
3. [Kiến Trúc Hệ Thống](#3-kiến-trúc-hệ-thống)
4. [Các Module & Chức Năng](#4-các-module--chức-năng)
5. [Tính Năng Nổi Bật](#5-tính-năng-nổi-bật)
6. [Thống Kê Hệ Thống](#6-thống-kê-hệ-thống)

---

## 1. TỔNG QUAN HỆ THỐNG

### 🎯 Giới Thiệu

**Hệ Thống Quản Lý Trường Học** - Ứng dụng web hiện đại quản lý toàn diện các hoạt động học tập và giảng dạy trong môi trường đại học.

### ✨ Đặc Điểm Nổi Bật

- ✅ **Hiện đại**: Laravel 12, PHP 8.3, Apache Kafka
- ✅ **Scalable**: Kiến trúc module hóa, event-driven
- ✅ **Real-time**: WebSocket, notifications đồng bộ
- ✅ **Bảo mật**: JWT authentication, RBAC
- ✅ **Dễ bảo trì**: Clean Architecture, SOLID principles

### 👥 Người Dùng

| Role | Mô Tả | Quyền Hạn |
|------|-------|-----------|
| **Admin** | Quản trị viên | Full control, monitoring, cache management |
| **Lecturer** | Giảng viên | Tạo task, chấm điểm, quản lý lớp |
| **Student** | Sinh viên | Nộp bài, xem điểm, calendar |

---

## 2. CÔNG NGHỆ SỬ DỤNG

### 💻 Backend Core

```yaml
Framework: Laravel 12.x
Language: PHP 8.3+
Architecture: Nwidart Laravel Modules 12.0
```

**Lý do chọn:**
- Laravel 12: Framework mới nhất, hiệu năng cao
- PHP 8.3: JIT compilation, performance cải thiện 20-30%
- Modular: Dễ bảo trì, phát triển song song

### 🔐 Authentication

```yaml
JWT: firebase/php-jwt 6.11
Features:
  - Token-based (stateless)
  - Role-based access control
  - Token refresh
  - Secure password hashing
```

### 🗄️ Database & Caching

| Technology | Version | Purpose |
|-----------|---------|---------|
| MySQL | 8.0 | Production database |
| Redis | Alpine | Cache, session, queue |
| SQLite | Latest | Development/testing |

### 📨 Message Queue

```yaml
Apache Kafka: 6.2.10
  - Topics: 9 event topics
  - Throughput: Millions msg/sec
  - Use cases: Notifications, async processing

Laravel Queue:
  - Driver: Redis
  - Workers: Email queue, default queue
```

### 🔄 Real-time

```yaml
Laravel Reverb: 1.0
  - WebSocket server
  - Push notifications
  - Live updates
  - Channels: Private user, Class presence
```

### 📧 Notifications

**Multi-Channel Support:**
- ✅ Email (SMTP, Blade templates)
- ✅ Push (WebSocket/Reverb)
- ✅ In-app (Database)
- ⏳ SMS (Planned)

### 📖 Documentation & Testing

```yaml
API Docs: Swagger/OpenAPI (L5-Swagger 9.0)
Testing: PHPUnit 11.5.3, Mockery 1.6
Code Quality: Laravel Pint (PSR-12)
Monitoring: Laravel Pail 1.2.2
```

### 🐳 DevOps

**Docker Services (10 containers):**

| Service | Port | Purpose |
|---------|------|---------|
| Nginx | 8082 | Web server |
| MySQL | 3307 | Database |
| Redis | 6380 | Cache & queue |
| Reverb | 8081 | WebSocket |
| Kafka | 9092 | Message broker |
| Zookeeper | 2181 | Kafka coordination |

---

## 3. KIẾN TRÚC HỆ THỐNG

### 🏗️ Clean Architecture

```
┌─────────────────────────────────────────┐
│      PRESENTATION LAYER                 │
│  Controllers | Middleware | Routes      │
└──────────────────┬──────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│      APPLICATION LAYER                  │
│  UseCases | DTOs | Events               │
└──────────────────┬──────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│      DOMAIN LAYER                       │
│  Services | Models | Interfaces         │
└──────────────────┬──────────────────────┘
                   ↓
┌─────────────────────────────────────────┐
│      INFRASTRUCTURE LAYER               │
│  Repositories | Database | External APIs│
└─────────────────────────────────────────┘
```

### 📦 Module Structure

```
System-Management/
├── Modules/
│   ├── Auth/              # Authentication & Users
│   ├── Task/              # Task Management
│   └── Notifications/     # Notification System
├── app/                   # Core Application
├── config/                # Configuration
├── database/              # Migrations
└── docker/                # Docker setup
```

### 🔄 Data Flow

```
Client → Nginx → Laravel → JWT Auth
                     ↓
              Controllers → UseCases
                     ↓
              Services → Repositories
                     ↓
         ┌───────────┼───────────┐
         ↓           ↓           ↓
      MySQL       Redis       Kafka
                                 ↓
                          Event Handlers
                                 ↓
                          Notifications
```

### 🎨 Design Patterns

| Pattern | Mục Đích | Ví Dụ |
|---------|----------|-------|
| Repository | Data access | `TaskRepository` |
| Service Layer | Business logic | `TaskService` |
| DTO | Data transfer | `TaskDTO` |
| Observer | Event handling | Laravel Events |
| Facade | Simplified interface | `EmailService` |
| Dependency Injection | Loose coupling | Service Container |

---

## 4. CÁC MODULE & CHỨC NĂNG

### 🔐 Module 1: AUTH

**📊 Thông tin:**
- Endpoints: ~30
- Tables: 8
- Roles: Admin, Lecturer, Student

**✨ Chức năng chính:**

#### 1. Authentication
```
✅ Student Login (POST /login/student)
✅ Lecturer/Admin Login (POST /login/lecturer)
✅ Get Profile (GET /me)
✅ Logout, Token Refresh
```

#### 2. User Management
```
✅ CRUD Students
✅ CRUD Lecturers
✅ CRUD Departments (Khoa)
✅ CRUD Classes (Lớp học)
✅ Bulk Import (CSV/Excel)
✅ Search & Filter
```

#### 3. Roll Call System
```
✅ 2 Loại:
   - Class-based: Auto lấy toàn bộ lớp
   - Manual: Tự chọn sinh viên

✅ 4 Trạng thái:
   - Có Mặt | Vắng Mặt | Muộn | Có Phép

✅ Features:
   - Create/Update/Delete roll calls
   - Bulk status update
   - Statistics by class/date
```

**🗄️ Models:**
`User`, `Student`, `Lecturer`, `Department`, `Classroom`, `StudentAccount`, `LecturerAccount`, `Unit`

---

### 📋 Module 2: TASK

**📊 Thông tin:**
- Endpoints: 131+
- Tables: 8+
- Organized: Admin/Lecturer/Student routes

**✨ Chức năng theo Role:**

#### 👨‍🎓 Student (26 endpoints)

**Task Management:**
```
✅ Get Tasks (filters, search, pagination)
✅ Get Task Detail
✅ Submit Task (content + files)
✅ Update Submission
✅ File Upload/Download/Delete
✅ Statistics (completion rate, performance)
```

**Calendar:**
```
✅ Get Events (all deadlines)
✅ Get by Date/Range
✅ Upcoming/Overdue Events
✅ Set Reminders
```

**Class:**
```
✅ Class Info, Classmates, Lecturers
✅ Announcements, Schedule, Attendance
```

#### 👨‍🏫 Lecturer (26 endpoints)

**Task Management:**
```
✅ Create/Update/Delete Tasks
✅ Assign to Students/Classes
✅ Revoke Assignments
✅ Grade Submissions (score + feedback)
✅ Create Recurring Tasks
✅ Statistics (submission rates, grading)
```

**Calendar:**
```
✅ All Student features
✅ Create/Update/Delete Custom Events
```

#### 🔧 Admin (24 endpoints)

**Task Management:**
```
✅ Get All Tasks (system-wide)
✅ Full CRUD control
✅ Override Task Status
✅ Bulk Actions
```

**Monitoring:**
```
✅ System Metrics (CPU, Memory, API times)
✅ Health Check
✅ Logs (App, Error, Access)
✅ Maintenance Mode
```

**Cache Management:**
```
✅ Invalidate Student/Lecturer/Dept/Class
✅ Bulk/Clear All Cache
```

#### 🔗 Advanced Features

**Task Dependencies (13 endpoints):**
```
✅ Create Dependencies (Task A → depends on → Task B)
✅ Validate (circular check)
✅ Can Start Check
✅ Dependency Chain
```

**Statistics & Reports (23 endpoints):**
```
✅ Completion Rate, Priority/Status Distribution
✅ Trend Analysis, Breakdown by Class/Dept
✅ Export: Excel, PDF, CSV
✅ Reports: Student Progress, Class Performance
```

**🗄️ Models:**
`Task`, `TaskFile`, `TaskSubmission`, `TaskReceiver`, `TaskDependency`, `Calendar`, `Reminder`

---

### 🔔 Module 3: NOTIFICATIONS

**📊 Thông tin:**
- Endpoints: ~15
- Tables: 3
- Handlers: 9 Kafka handlers

**✨ Architecture:**

```
Service → Kafka Producer → Topic
            ↓
    Kafka Consumer → Handler
            ↓
    Notification Service
            ↓
    ┌───────┼───────┐
    ↓       ↓       ↓
  Email   Push   In-app
```

**🎯 9 Kafka Topics:**

| Topic | Purpose |
|-------|---------|
| `student.registered` | Tạo tài khoản SV |
| `lecturer.registered` | Tạo tài khoản GV |
| `task.created` | Task mới |
| `task.updated` | Task cập nhật |
| `task.assigned` | Giao task |
| `task.submitted` | SV nộp bài |
| `task.graded` | Bài được chấm |
| `reminder.task.deadline` | Nhắc deadline |
| `reminder.task.overdue` | Task quá hạn |

**📧 Multi-Channel:**

```
✅ Email:
   - Template-based (Blade)
   - Queue-based sending
   - HTML formatted

✅ Push:
   - WebSocket real-time
   - Browser notifications

✅ In-app:
   - Notification center
   - Mark read/unread
   - Filter & search

⏳ SMS: Planned
```

**🗄️ Models:**
`NotificationTemplate`, `Notification`, `UserNotification`

---

## 5. TÍNH NĂNG NỔI BẬT

### 🎯 1. Kiến Trúc Module Hóa

```
✅ Tách biệt: Auth, Task, Notifications
✅ Độc lập, dễ bảo trì
✅ Phát triển song song
✅ Tái sử dụng code cao
```

### 🏗️ 2. Clean Architecture

```
✅ Separation of Concerns
✅ SOLID Principles
✅ Repository + Service Layer
✅ Testable & Maintainable
```

### 🔐 3. Role-Based Access Control

```
✅ 3 Roles: Admin, Lecturer, Student
✅ JWT token with role info
✅ Middleware protection
✅ Fine-grained permissions
```

### 📨 4. Event-Driven với Kafka

```
✅ Asynchronous processing
✅ Decoupled services
✅ 9 Event topics
✅ Message persistence
✅ Scalable (horizontal)
```

### 🔔 5. Multi-Channel Notifications

```
✅ Email, Push, In-app, SMS
✅ Template-based
✅ Queue processing
✅ Real-time delivery
```

### 📅 6. Calendar System

```
✅ Task deadlines tracking
✅ Custom events
✅ Recurring events
✅ Reminders (email/push)
✅ Multi-view (day/week/month)
```

### 📊 7. Advanced Task Management

```
✅ Task dependencies
✅ Recurring tasks
✅ File attachments
✅ Submissions & grading
✅ Priority levels (4 levels)
✅ Bulk operations
```

### 📈 8. Statistics & Reports

```
✅ Completion rates
✅ Performance analytics
✅ Export Excel/PDF/CSV
✅ Student progress reports
✅ Real-time dashboard
```

### ✅ 9. Roll Call System

```
✅ Class-based + Manual
✅ 4 attendance statuses
✅ Bulk update
✅ Statistics & reports
```

### 🚀 10. Developer-Friendly

```
✅ Swagger documentation
✅ Comprehensive API docs
✅ Error logging
✅ Code formatting (Pint)
✅ Unit tests
✅ Docker ready
```

---

## 6. THỐNG KÊ HỆ THỐNG

### 📈 Code Metrics

```yaml
Modules: 3 (Auth, Task, Notifications)
API Endpoints: 176+
Database Tables: 19+
Event Handlers: 9
Notification Templates: 8+
User Roles: 3
Models: 20+
Services: 25+
Repositories: 15+
```

### 🐳 Infrastructure

**Docker Services:** 10 containers
```
✅ app, webserver, db, redis
✅ reverb, kafka, zookeeper
✅ queue, queue_default, kafka_consumer
```

**Ports:**
```
8082: Web Server
3307: MySQL
6380: Redis
8081: WebSocket
9092: Kafka
2181: Zookeeper
```

### 🌐 API Statistics

| Module | Endpoints | Features |
|--------|-----------|----------|
| Auth | ~30 | Login, Users, Roll Call |
| Task | 131+ | Tasks, Calendar, Reports |
| Notifications | ~15 | Send, Templates, History |
| **Total** | **176+** | Full-featured API |

### 📊 Database Schema

**19+ Tables:**
```
Auth Module: 8 tables
  - users, students, lecturers
  - departments, classes
  - accounts, units, roll_calls

Task Module: 8+ tables
  - tasks, task_files, task_submissions
  - task_receivers, task_dependencies
  - calendars, reminders

Notifications Module: 3 tables
  - notification_templates
  - notifications
  - user_notifications
```

---

## 🎯 KẾT LUẬN

### ✅ Ưu Điểm Hệ Thống

**1. Kiến trúc hiện đại:**
- Clean Architecture + Modular Design
- SOLID principles
- Design patterns best practices

**2. Công nghệ tiên tiến:**
- Laravel 12 + PHP 8.3
- Apache Kafka event-driven
- Redis caching layer
- WebSocket real-time

**3. Tính năng phong phú:**
- 176+ API endpoints
- Multi-role support (Admin/Lecturer/Student)
- Multi-channel notifications
- Advanced task management with dependencies

**4. Scalability:**
- Event-driven architecture
- Horizontal scaling ready
- Microservices-ready
- Docker containerization

**5. Developer experience:**
- Comprehensive documentation
- Swagger API docs
- Unit testing
- Code quality tools

### 🚀 Công Nghệ Stack Tổng Hợp

```yaml
Backend:
  - Laravel 12 + PHP 8.3
  - Modular Architecture (Nwidart)
  - Clean Architecture Pattern

Database:
  - MySQL 8.0 (Production)
  - Redis (Cache, Queue, Session)
  - SQLite (Development)

Messaging:
  - Apache Kafka 6.2.10
  - Laravel Queue
  - 9 Event Topics

Real-time:
  - Laravel Reverb 1.0
  - WebSocket
  - Push Notifications

Authentication:
  - JWT (firebase/php-jwt)
  - Role-based Access Control
  - Stateless Authentication

DevOps:
  - Docker + Docker Compose
  - 10 Microservices
  - Nginx Web Server
  - Supervisor Process Manager

Documentation:
  - Swagger/OpenAPI
  - Extensive MD docs
  - API testing tools
```

### 📋 Tổng Hợp Chức Năng

**Auth Module:**
- Authentication (Login/Logout/JWT)
- User Management (Students, Lecturers)
- Department & Class Management
- Roll Call System (2 types, 4 statuses)

**Task Module:**
- Task CRUD with role separation
- Task Dependencies
- File Management
- Submissions & Grading
- Calendar & Reminders
- Statistics & Reports (Excel/PDF/CSV)
- Recurring Tasks

**Notifications Module:**
- Event-driven (9 Kafka topics)
- Multi-channel (Email, Push, In-app)
- Template-based
- Queue processing
- Real-time delivery

### 🎓 Giá Trị Mang Lại

**Cho Sinh viên:**
- Quản lý task hiệu quả
- Theo dõi deadline
- Nộp bài trực tuyến
- Nhận thông báo real-time

**Cho Giảng viên:**
- Tạo và quản lý task dễ dàng
- Chấm điểm online
- Thống kê lớp học
- Điểm danh tự động

**Cho Quản trị:**
- Giám sát toàn hệ thống
- Thống kê chi tiết
- Quản lý cache
- Monitoring real-time

---

**🎓 Hệ thống quản lý trường học hiện đại, scalable và dễ bảo trì với kiến trúc module hóa và event-driven architecture!**

---

**Prepared by:** Development Team  
**Date:** 2025  
**Version:** 1.0

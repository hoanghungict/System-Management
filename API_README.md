# 📚 API Documentation Index

Đây là tổng hợp toàn bộ API documentation của hệ thống.

---

## 📂 Available Documents

### 1. 🔐 [Auth Module API](./AUTH_API_DOCUMENTATION.md)

**File:** `AUTH_API_DOCUMENTATION.md`

**Bao gồm:**

-   ✅ Authentication APIs (Login Student, Login Lecturer, JWT Refresh, Logout, Get Me)
-   ✅ Student Management APIs (CRUD, Get by Class, Profile)
-   ✅ Lecturer Management APIs (CRUD, Admin Status, Profile)
-   ✅ Department Management APIs (CRUD, Tree Structure)
-   ✅ Class Management APIs (CRUD, Get by Faculty/Lecturer)

**Endpoints:** ~30 endpoints

**Base URL:** `http://localhost:8000/api/v1`

---

### 2. 📋 [RollCall (Điểm Danh) API](./ROLLCALL_API_DOCUMENTATION.md)

**File:** `ROLLCALL_API_DOCUMENTATION.md`

**Bao gồm:**

-   ✅ Get Resources (Classrooms, Students, All Students)
-   ✅ Create Roll Call (Class-Based & Manual)
-   ✅ Query Roll Calls (All, By Class, Details)
-   ✅ Update Attendance Status (Single & Bulk)
-   ✅ Manage Participants (Add/Remove for Manual)
-   ✅ Complete & Cancel Roll Call
-   ✅ Statistics (By Class, Date Range)

**Endpoints:** ~15 endpoints

**Base URL:** `http://localhost:8000/api/v1/roll-calls`

**Features:**

-   🎯 2 loại điểm danh: `class_based` (tự động lấy tất cả SV) và `manual` (tự chọn)
-   📊 4 trạng thái: Có Mặt, Vắng Mặt, Muộn, Có Phép
-   ⚡ Bulk update hỗ trợ cập nhật hàng loạt
-   📈 Thống kê chi tiết theo lớp và thời gian

---

### 3. 🔔 [Notification Module API](./NOTIFICATION_API_DOCUMENTATION.md)

**File:** `NOTIFICATION_API_DOCUMENTATION.md`

**Bao gồm:**

-   ✅ Kafka Event-Driven Notifications (7 events chi tiết)
-   ✅ REST API Endpoints (Send, Bulk Send, Schedule, Templates)
-   ✅ Notification Templates (Student/Lecturer Account, Task events)
-   ✅ WebSocket Real-time Push
-   ✅ User Notification APIs (Get notifications, Mark as read)

**Endpoints:** ~10 endpoints

**Base URL:** `http://localhost:8000/api/v1/notifications`

---

### 4. 📨 [Kafka Events Publishing Guide](./KAFKA_PUBLISH_EVENTS_GUIDE.md) ⭐ **FOR EXTERNAL SERVICES**

**File:** `KAFKA_PUBLISH_EVENTS_GUIDE.md`

**📍 Dành cho:** Các services/modules mới muốn publish Kafka events

**Bao gồm:**

-   ✅ How to Publish Events (3 methods)
-   ✅ **Complete Payload Structure** cho từng topic
-   ✅ **Required vs Optional Fields** rõ ràng
-   ✅ **Handler Logic Explanation** - Handler xử lý như thế nào
-   ✅ **Code Examples** - Copy & paste ready
-   ✅ **Testing Guide** - Kafka console, REST API, Logs
-   ✅ **Best Practices** - Do's and Don'ts
-   ✅ **Troubleshooting** - Common issues và solutions
-   ✅ **Handler Registration** - Cách thêm handler mới

**9 Topics Chi Tiết:**

-   `student.registered` - Tạo SV
-   `lecturer.registered` - Tạo GV
-   `task.created` - Tạo task
-   `task.updated` - Cập nhật task
-   `task.assigned` - Gán task
-   `task.submitted` - Nộp bài
-   `task.graded` - Chấm điểm
-   `reminder.task.deadline` - Nhắc deadline
-   `reminder.task.overdue` - Quá hạn

---

## 🚀 Quick Start

### 1. Authentication

```bash
# Login Student
POST http://localhost:8000/api/v1/login/student
Content-Type: application/json

{
  "username": "sv_SV001",
  "password": "123456"
}

# Response: JWT token
```

### 2. Get Current User

```bash
GET http://localhost:8000/api/v1/me
Authorization: Bearer {JWT_TOKEN}
```

### 3. Create Roll Call

```bash
POST http://localhost:8000/api/v1/roll-calls
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json

{
  "type": "class_based",
  "class_id": 5,
  "title": "Điểm danh buổi 1",
  "date": "2024-01-15 08:00:00"
}
```

### 4. Get User Notifications

```bash
GET http://localhost:8000/api/v1/internal/notifications/user?limit=20
Authorization: Bearer {JWT_TOKEN}
```

---

## 📊 System Overview

### Architecture

```
┌─────────────────┐
│   Frontend      │
│   (Next.js)     │
└────────┬────────┘
         │ HTTP/WebSocket
         ▼
┌─────────────────┐
│   API Gateway   │
│  (Laravel API)  │
└────────┬────────┘
         │
    ┌────┴────┬────────────┐
    ▼         ▼            ▼
┌────────┐ ┌──────┐ ┌──────────┐
│  Auth  │ │ Roll │ │Notification│
│ Module │ │ Call │ │  Module   │
└────────┘ └──────┘ └─────┬────┘
                           │
                           ▼
                    ┌──────────┐
                    │  Kafka   │
                    └──────────┘
```

### Data Flow

#### Authentication Flow:

```
Client → POST /login → AuthController → AuthService → JWT Token → Client
```

#### Roll Call Flow:

```
Lecturer → POST /roll-calls → RollCallController
→ RollCallService → Create RollCall + Details
→ Cache Invalidation → Response
```

#### Notification Flow:

```
Service → Kafka Producer → Kafka Topic
→ Kafka Consumer → Handler → NotificationService
→ [Email, Push, In-app] → User
```

---

## 🔑 Authentication Headers

Tất cả authenticated endpoints yêu cầu JWT token:

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

**Token được lấy từ:**

-   `/api/v1/login/student`
-   `/api/v1/login/lecturer`

**Token chứa:**

-   `user_id`: ID của user
-   `user_type`: Loại user (student, lecturer)
-   `is_admin`: Quyền admin (true/false)
-   `email`, `full_name`, `department_id`, `class_id`

---

## 📝 Response Format

### Success Response:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response:

```json
{
    "success": false,
    "message": "Error message",
    "error": "Detailed error"
}
```

### Paginated Response:

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [ ... ],
    "first_page_url": "...",
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

---

## 🎯 Common Use Cases

### 1. Student Login và Xem Notifications

```bash
# 1. Login
POST /api/v1/login/student
{ "username": "sv_SV001", "password": "123456" }

# 2. Get profile
GET /api/v1/me
Authorization: Bearer {token}

# 3. Get notifications
GET /api/v1/internal/notifications/user?limit=20
Authorization: Bearer {token}

# 4. Mark as read
POST /api/v1/internal/notifications/mark-read
{ "notification_ids": [1, 2, 3] }
```

### 2. Lecturer Tạo Roll Call

```bash
# 1. Login
POST /api/v1/login/lecturer
{ "username": "gv_GV001", "password": "123456" }

# 2. Get classrooms
GET /api/v1/roll-calls/classrooms
Authorization: Bearer {token}

# 3. Create roll call
POST /api/v1/roll-calls
{
  "type": "class_based",
  "class_id": 5,
  "title": "Điểm danh buổi 1",
  "date": "2024-01-15 08:00:00"
}

# 4. Update status
PUT /api/v1/roll-calls/1/status
{
  "student_id": 1,
  "status": "Có Mặt"
}
```

### 3. Admin Tạo Student và Gửi Notification

```bash
# 1. Login as admin
POST /api/v1/login/lecturer
{ "username": "admin", "password": "admin123" }

# 2. Create student
POST /api/v1/students
{
  "full_name": "Nguyễn Văn A",
  "student_code": "SV001",
  "email": "nguyenvana@email.com",
  "class_id": 5
}

# → Automatically triggers Kafka event: student.registered
# → Student receives notification via Email + Push + In-app
```

---

## 🛠️ Development Tools

### Postman Collection

Import base URL: `http://localhost:8000/api/v1`

### Testing Kafka Events

```bash
# Using Kafka console producer
kafka-console-producer --broker-list localhost:9092 --topic student.registered

# Message:
{"user_id":1,"name":"Test","user_name":"test","password":"123"}
```

### WebSocket Testing (Frontend)

```javascript
import Echo from "laravel-echo";

const echo = new Echo({
    broadcaster: "pusher",
    key: process.env.PUSHER_APP_KEY,
    cluster: process.env.PUSHER_APP_CLUSTER,
    wsHost: "localhost",
    wsPort: 6001,
});

echo.private("user-student-1").listen("UserNotificationPushed", (e) => {
    console.log("Notification:", e);
});
```

---

## 📞 Support & Contact

**Backend Repository:** `/HPCProject`  
**Frontend Repository:** `/HPCProject-FE`

**Documentation Version:** 1.0.0  
**Last Updated:** 2024-01-15

---

## 📌 Notes

1. **Default Passwords:** Tất cả accounts mới có password mặc định `123456`
2. **JWT Expiry:** Token hết hạn sau 60 phút (có thể refresh)
3. **Cache:** Một số endpoints sử dụng Redis cache
4. **Rate Limiting:** Chưa implement (sẽ thêm sau)
5. **Pagination:** Mặc định 15 items/page, max 100 items/page
6. **Real-time:** Notifications được push real-time qua WebSocket

---

## 🔄 Version History

| Version | Date       | Changes                                   |
| ------- | ---------- | ----------------------------------------- |
| 1.0.0   | 2024-01-15 | Initial documentation với 3 modules chính |

---

## ✅ Checklist for Integration

### Auth Module

-   [ ] Implement login flow
-   [ ] Store JWT token
-   [ ] Handle token refresh
-   [ ] Implement logout
-   [ ] Profile management

### RollCall Module

-   [ ] Display classrooms list
-   [ ] Create roll call form
-   [ ] Attendance marking interface
-   [ ] Real-time status updates
-   [ ] Statistics dashboard

### Notification Module

-   [ ] Subscribe to WebSocket
-   [ ] Display notification list
-   [ ] Mark as read functionality
-   [ ] Notification badge counter
-   [ ] Filter by read/unread

# 🔔 Notification Module API Documentation

## Base URL

```
http://localhost:8000/api/v1
```

## Authentication

Một số endpoints yêu cầu JWT token:

```
Authorization: Bearer {JWT_TOKEN}
```

---

# 📑 Table of Contents

1. [Kafka Event-Driven Notifications](#1-kafka-event-driven-notifications)
2. [REST API Endpoints](#2-rest-api-endpoints)
3. [Notification Templates](#3-notification-templates)
4. [WebSocket Real-time](#4-websocket-real-time)
5. [User Notification APIs](#5-user-notification-apis)

---

# 1. Kafka Event-Driven Notifications

## Overview

Hệ thống sử dụng **Kafka** để xử lý notifications bất đồng bộ. Các services khác có thể gửi events lên Kafka, Notification module sẽ consume và xử lý.

## Architecture Flow

```
Service (Auth/Task) → Kafka Producer → Kafka Topic → Kafka Consumer → Handler → NotificationService → Channels (Email/Push/In-app)
```

---

## 1.1. Student Registration Event

**Topic:** `student.registered`

**Khi nào được trigger:**

-   Khi Admin tạo student account mới
-   Sau khi tạo thành công student trong `StudentService::createStudentWithAccount()`

**Producer Code Location:**

```php
// Modules/Auth/app/Services/AuthUserService/StudentService.php
$this->kafkaProducer->send('student.registered', [
    'user_id' => $student->id,
    'name' => $dataStudent->full_name ?? "Unknown",
    'user_name' => $username ?? "Unknown",
    'password' => $password
]);
```

**Payload Format:**

```json
{
    "user_id": 1,
    "name": "Nguyễn Văn A",
    "user_name": "sv_SV001",
    "password": "123456"
}
```

**Handler:** `RegisterStudentHandle.php`

**Notification Template:** `student_account_created`

**Channels:** Email, Push, In-app

**Receivers:** Student mới được tạo

**Example Complete Flow:**

```php
// 1. Admin creates student
POST /api/v1/students
{
  "full_name": "Nguyễn Văn A",
  "student_code": "SV001",
  "email": "nguyenvana@email.com",
  "class_id": 5
}

// 2. StudentService tạo account và gửi Kafka event
KafkaProducer::send('student.registered', {
  "user_id": 1,
  "name": "Nguyễn Văn A",
  "user_name": "sv_SV001",
  "password": "123456"
})

// 3. Handler nhận event và gửi notification
NotificationService::sendNotification(
  'student_account_created',
  [['user_id' => 1, 'user_type' => 'student']],
  {
    "user_name": "sv_SV001",
    "name": "Nguyễn Văn A",
    "password": "123456",
    "app_name": "Hệ Điện Tử Khoa CNTT",
    "subject": "Đăng ký tài khoản sinh viên"
  }
)

// 4. Student nhận notification qua:
// - Email: Thông tin tài khoản
// - Push: Real-time notification
// - In-app: Hiển thị trong app
```

---

## 1.2. Lecturer Registration Event

**Topic:** `lecturer.registered`

**Khi nào được trigger:**

-   Khi Admin tạo lecturer account mới
-   Sau khi tạo thành công lecturer trong `LecturerService::createLecturerWithAccount()`

**Producer Code Location:**

```php
// Modules/Auth/app/Services/AuthUserService/LecturerService.php
$this->producerService->send('lecturer.registered', [
    'user_id' => $lecturer->id,
    'name' => $dataLecturer->full_name ?? "Unknown",
    'user_name' => $username ?? "Unknown",
    'password' => $password
]);
```

**Payload Format:**

```json
{
    "user_id": 1,
    "name": "Trần Thị B",
    "user_name": "gv_GV001",
    "password": "123456"
}
```

**Handler:** `RegisterLecturerHandle.php`

**Notification Template:** `lecturer_account_created`

**Channels:** Email, Push, In-app

**Receivers:** Lecturer mới được tạo

**Example Complete Flow:**

```php
// 1. Admin creates lecturer
POST /api/v1/lecturers
{
  "full_name": "Trần Thị B",
  "lecturer_code": "GV001",
  "email": "tranthib@email.com",
  "department_id": 3
}

// 2. Kafka event được gửi
{
  "user_id": 1,
  "name": "Trần Thị B",
  "user_name": "gv_GV001",
  "password": "123456"
}

// 3. Lecturer nhận notification với:
// - Username: gv_GV001
// - Password: 123456
// - Link đăng nhập
```

---

## 1.3. Task Assigned Event

**Topic:** `task.assigned`

**Khi nào được trigger:**

-   Khi tạo task mới
-   Trong `TaskService::createTask()` sau khi tạo thành công

**Producer Code Location:**

```php
// Modules/Task/app/Services/TaskService.php
$this->kafkaProducer->send('task.assigned', [
    'user_id' => $task->creator_id,
    'name' => $task->creator_name ?? "Unknown",
    'user_name' => $task->creator_name ?? "Unknown",
    'user_email' => $task->creator_email ?? 'no-email@example.com'
]);
```

**Payload Format:**

```json
{
    "user_id": 1,
    "name": "Trần Thị B",
    "user_name": "Trần Thị B",
    "user_email": "tranthib@email.com"
}
```

**Handler:** `TaskAssignedHandler.php`

**Notification Template:** `task_assigned`

**Channels:** Email, Push, In-app

**Receivers:** Creator và Receivers của task

---

## 1.4. Task Created Event

**Topic:** `task.created` (via Laravel Event)

**Khi nào được trigger:**

-   Sau khi tạo task thành công
-   `event(new TaskCreated($task, ...))`

**Event Data:**

```php
TaskCreated {
  task: Task,
  metadata: [
    'creator_id' => 1,
    'creator_type' => 'lecturer',
    'receivers' => [...]
  ]
}
```

**Handler:** `TaskCreatedHandler.php`

**Payload Format (to Kafka via Job):**

```json
{
    "task_id": 1,
    "task_title": "Bài tập tuần 1",
    "task_description": "Làm bài tập về nhà",
    "deadline": "2024-01-20 23:59:59",
    "priority": "high",
    "creator_name": "Trần Thị B",
    "receiver_id": 1,
    "receiver_type": "student",
    "receiver_name": "Nguyễn Văn A",
    "task_url": "http://localhost:3000/tasks/1"
}
```

**Notification Template:** `task_created`

**Channels:** Email, Push, In-app

**Receivers:** Tất cả receivers của task

**Example:**

```php
// 1. Lecturer creates task
POST /api/v1/tasks
{
  "title": "Bài tập tuần 1",
  "description": "Làm bài tập về nhà",
  "deadline": "2024-01-20 23:59:59",
  "priority": "high",
  "receivers": [
    {"receiver_id": 1, "receiver_type": "student"},
    {"receiver_id": 2, "receiver_type": "student"}
  ]
}

// 2. TaskService tạo task và dispatch event
event(new TaskCreated($task))

// 3. Job được queue: SendTaskCreatedNotificationJob
// 4. Handler process và gửi notification cho từng receiver
// 5. Students nhận notification:
//    - Email: "Bạn có bài tập mới từ Trần Thị B"
//    - Push: Real-time notification
//    - In-app: Hiển thị badge số lượng task mới
```

---

## 1.5. Task Updated Event

**Topic:** `task.updated` (via Laravel Event)

**Khi nào được trigger:**

-   Khi update task
-   `event(new TaskUpdated($task, $changes, ...))`

**Event Data:**

```php
TaskUpdated {
  task: Task,
  changes: [
    'deadline' => [
      'old' => '2024-01-20 23:59:59',
      'new' => '2024-01-25 23:59:59'
    ],
    'priority' => [
      'old' => 'medium',
      'new' => 'high'
    ]
  ],
  metadata: [...]
}
```

**Handler:** `TaskUpdatedHandler.php`

**Payload Format:**

```json
{
    "task_id": 1,
    "task_title": "Bài tập tuần 1",
    "changes": {
        "deadline": {
            "old": "2024-01-20 23:59:59",
            "new": "2024-01-25 23:59:59"
        },
        "priority": {
            "old": "medium",
            "new": "high"
        }
    },
    "updater_name": "Trần Thị B",
    "receiver_id": 1,
    "receiver_type": "student"
}
```

**Notification Template:** `task_updated`

**Channels:** Email, Push, In-app

**Receivers:** Tất cả receivers của task

---

## 1.6. Task Submitted Event

**Topic:** `task.submitted` (via Laravel Event)

**Khi nào được trigger:**

-   Khi student submit task
-   `event(new TaskSubmitted($submission))`

**Handler:** `TaskSubmittedHandler.php`

**Payload Format:**

```json
{
    "task_id": 1,
    "task_title": "Bài tập tuần 1",
    "submission_id": 1,
    "student_id": 1,
    "student_name": "Nguyễn Văn A",
    "submitted_at": "2024-01-19 20:30:00",
    "content": "Đã hoàn thành bài tập",
    "creator_id": 1,
    "creator_type": "lecturer"
}
```

**Notification Template:** `task_submitted`

**Channels:** Email, Push, In-app

**Receivers:** Creator của task (Lecturer)

---

## 1.7. Task Graded Event

**Topic:** `task.graded` (via Laravel Event)

**Khi nào được trigger:**

-   Khi lecturer chấm điểm task
-   `event(new TaskGraded($task, $grade))`

**Handler:** `TaskGradedHandler.php`

**Payload Format:**

```json
{
    "task_id": 1,
    "task_title": "Bài tập tuần 1",
    "submission_id": 1,
    "student_id": 1,
    "student_name": "Nguyễn Văn A",
    "grade": 9.5,
    "max_grade": 10,
    "feedback": "Làm tốt lắm!",
    "graded_by": "Trần Thị B",
    "graded_at": "2024-01-20 10:00:00"
}
```

**Notification Template:** `task_graded`

**Channels:** Email, Push, In-app

**Receivers:** Student được chấm điểm

---

# 2. REST API Endpoints

## 2.1. Send Single Notification

**POST** `/notifications/send`

**Headers:**

```
Content-Type: application/json
```

**Description:** Gửi notification đơn lẻ

**Request Body:**

```json
{
    "template": "student_account_created",
    "recipients": [
        {
            "user_id": 1,
            "user_type": "student",
            "channels": ["email", "push", "in_app"]
        }
    ],
    "data": {
        "user_name": "sv_SV001",
        "name": "Nguyễn Văn A",
        "password": "123456",
        "app_name": "Hệ Điện Tử"
    },
    "options": {
        "priority": "high",
        "sender_id": 1,
        "sender_type": "admin"
    }
}
```

**Response Success (200):**

```json
{
    "success": true,
    "notification_id": 1,
    "recipients_count": 1,
    "message": "Notification sent successfully"
}
```

**Response Error (500):**

```json
{
    "success": false,
    "error": "Template 'student_account_created' not found"
}
```

---

## 2.2. Send Bulk Notification

**POST** `/notifications/send-bulk`

**Headers:**

```
Content-Type: application/json
```

**Description:** Gửi notification hàng loạt cho nhiều recipients

**Request Body:**

```json
{
    "template": "task_created",
    "recipients": [
        {
            "user_id": 1,
            "user_type": "student"
        },
        {
            "user_id": 2,
            "user_type": "student"
        },
        {
            "user_id": 3,
            "user_type": "student"
        }
    ],
    "data": {
        "task_title": "Bài tập tuần 1",
        "task_description": "Làm bài tập về nhà",
        "deadline": "2024-01-20 23:59:59",
        "creator_name": "Trần Thị B"
    },
    "options": {
        "priority": "medium"
    }
}
```

**Response Success (200):**

```json
{
    "success": true,
    "total_chunks": 1,
    "results": [
        {
            "success": true,
            "notification_id": 2,
            "recipients_count": 3
        }
    ]
}
```

---

## 2.3. Schedule Notification

**POST** `/notifications/schedule`

**Headers:**

```
Content-Type: application/json
```

**Description:** Lên lịch gửi notification

**Request Body:**

```json
{
    "template": "task_reminder",
    "recipients": [
        {
            "user_id": 1,
            "user_type": "student"
        }
    ],
    "data": {
        "task_title": "Bài tập tuần 1",
        "deadline": "2024-01-20 23:59:59"
    },
    "scheduled_at": "2024-01-19 08:00:00",
    "options": {
        "priority": "high"
    }
}
```

**Response Success (200):**

```json
{
    "success": true,
    "notification_id": 3,
    "recipients_count": 1,
    "message": "Notification scheduled successfully"
}
```

---

## 2.4. Get Templates

**GET** `/notifications/templates?category={category}`

**Headers:**

```
Content-Type: application/json
```

**Query Parameters:**

-   `category` (optional): Filter theo category (`user_registration`, `task`, `system`, etc.)

**Response Success (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "student_account_created",
            "category": "user_registration",
            "title": "Tài khoản sinh viên đã được tạo",
            "subject": "Đăng ký tài khoản sinh viên",
            "channels": ["email", "push", "in_app"],
            "priority": "medium",
            "email_template": "...",
            "push_template": "...",
            "in_app_template": "..."
        },
        {
            "id": 2,
            "name": "lecturer_account_created",
            "category": "user_registration",
            "title": "Tài khoản giảng viên đã được tạo",
            "channels": ["email", "push", "in_app"]
        },
        {
            "id": 3,
            "name": "task_created",
            "category": "task",
            "title": "Bài tập mới",
            "channels": ["email", "push", "in_app"]
        }
    ]
}
```

---

## 2.5. Get Notification Status

**GET** `/notifications/status/{id}`

**Response Success (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "status": "sent",
        "sent_at": "2024-01-15T10:30:00.000000Z",
        "recipients_count": 3,
        "email_sent_count": 3,
        "push_sent_count": 3,
        "sms_sent_count": 0
    }
}
```

**Response Error (404):**

```json
{
    "success": false,
    "message": "Notification not found"
}
```

---

## 2.6. Publish Event (Single API for All Events)

**POST** `/events/publish`

**Headers:**

```
Content-Type: application/json
```

**Description:** Single API để publish bất kỳ event nào lên Kafka

**Request Body:**

```json
{
    "topic": "student.registered",
    "payload": {
        "user_id": 1,
        "name": "Nguyễn Văn A",
        "user_name": "sv_SV001",
        "password": "123456"
    },
    "priority": "medium",
    "key": "student_1_registered"
}
```

**Response Success (200):**

```json
{
    "success": true,
    "message": "Event published successfully",
    "data": {
        "event_type": "student.registered",
        "event_id": "student_1_registered",
        "timestamp": "2024-01-15T10:30:00.000Z"
    }
}
```

**Example - Publish Task Created:**

```json
POST /api/v1/events/publish
{
  "topic": "task.created",
  "payload": {
    "task_id": 1,
    "task_title": "Bài tập tuần 1",
    "receiver_id": 1,
    "receiver_type": "student"
  }
}
```

**Response Error (500):**

```json
{
    "success": false,
    "error": "Failed to publish event to Kafka"
}
```

---

# 3. Notification Templates

## Available Templates

### 3.1. Student Account Created

```
Name: student_account_created
Category: user_registration
Channels: email, push, in_app

Variables:
- {{user_name}}: Username (sv_SV001)
- {{name}}: Full name
- {{password}}: Password mặc định
- {{app_name}}: Tên ứng dụng
- {{subject}}: Subject của email
```

### 3.2. Lecturer Account Created

```
Name: lecturer_account_created
Category: user_registration
Channels: email, push, in_app

Variables:
- {{user_name}}: Username (gv_GV001)
- {{name}}: Full name
- {{password}}: Password mặc định
- {{app_name}}: Tên ứng dụng
```

### 3.3. Task Created

```
Name: task_created
Category: task
Channels: email, push, in_app

Variables:
- {{task_id}}: Task ID
- {{task_title}}: Tiêu đề task
- {{task_description}}: Mô tả
- {{deadline}}: Deadline
- {{priority}}: Độ ưu tiên
- {{creator_name}}: Người tạo
- {{task_url}}: Link đến task
```

### 3.4. Task Updated

```
Name: task_updated
Category: task
Channels: email, push, in_app

Variables:
- {{task_title}}: Tiêu đề task
- {{changes}}: Các thay đổi (JSON)
- {{updater_name}}: Người cập nhật
```

### 3.5. Task Submitted

```
Name: task_submitted
Category: task
Channels: email, push, in_app

Variables:
- {{task_title}}: Tiêu đề task
- {{student_name}}: Sinh viên submit
- {{submitted_at}}: Thời gian submit
- {{content}}: Nội dung submission
```

### 3.6. Task Graded

```
Name: task_graded
Category: task
Channels: email, push, in_app

Variables:
- {{task_title}}: Tiêu đề task
- {{grade}}: Điểm số
- {{max_grade}}: Điểm tối đa
- {{feedback}}: Nhận xét
- {{graded_by}}: Người chấm
```

---

# 4. WebSocket Real-time

## 4.1. Push Notification Event

**Channel:** `private-user-{user_type}-{user_id}`

**Event:** `UserNotificationPushed`

**Example:**

```javascript
// Frontend subscribe
Echo.private(`user-student-1`).listen("UserNotificationPushed", (data) => {
    console.log("New notification:", data);
    // {
    //   user_id: 1,
    //   user_type: 'student',
    //   content: 'Bạn có bài tập mới',
    //   data: {...},
    //   notification_id: 1,
    //   user_notification_id: 1
    // }
});
```

**Trigger:** Khi `PushService::send()` được gọi

**Flow:**

```
NotificationService → PushService::send()
→ broadcast(new UserNotificationPushed(...))
→ WebSocket → Frontend
```

---

# 5. User Notification APIs

## 5.1. Get User Notifications (Authenticated)

**GET** `/internal/notifications/user?limit={limit}&offset={offset}`

**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
```

**Query Parameters:**

-   `limit` (optional): Số notification per page (default: 20, max: 100)
-   `offset` (optional): Offset (default: 0)

**Description:** Lấy danh sách notifications của user hiện tại (từ JWT token)

**Response Success (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "user_type": "student",
            "notification_id": 1,
            "is_read": false,
            "email_sent": true,
            "push_sent": true,
            "sms_sent": false,
            "in_app_sent": true,
            "read_at": null,
            "created_at": "2024-01-15T10:30:00.000000Z",
            "notification": {
                "id": 1,
                "title": "Tài khoản đã được tạo",
                "content": "Tài khoản của bạn đã được tạo thành công. Username: sv_SV001, Password: 123456",
                "type": "user_registration",
                "priority": "medium",
                "data": {
                    "user_name": "sv_SV001",
                    "password": "123456"
                }
            }
        },
        {
            "id": 2,
            "user_id": 1,
            "user_type": "student",
            "notification_id": 2,
            "is_read": true,
            "read_at": "2024-01-15T11:00:00.000000Z",
            "notification": {
                "id": 2,
                "title": "Bài tập mới",
                "content": "Bạn có bài tập mới: Bài tập tuần 1",
                "type": "task",
                "priority": "high"
            }
        }
    ],
    "pagination": {
        "limit": 20,
        "offset": 0,
        "total": 45
    }
}
```

---

## 5.2. Mark Notifications As Read (Authenticated)

**POST** `/internal/notifications/mark-read`

**Headers:**

```
Authorization: Bearer {JWT_TOKEN}
Content-Type: application/json
```

**Description:** Đánh dấu notifications đã đọc (user từ JWT token)

**Request Body:**

```json
{
    "notification_ids": [1, 2, 3, 5, 7]
}
```

**Response Success (200):**

```json
{
    "success": true,
    "message": "Tất cả thông báo đã được đánh dấu đã đọc",
    "results": [
        {
            "success": true,
            "message": "Notification marked as read",
            "user_notification_id": 1,
            "notification_id": 1
        },
        {
            "success": true,
            "message": "Notification marked as read",
            "user_notification_id": 2,
            "notification_id": 2
        },
        {
            "success": true,
            "message": "Notification marked as read",
            "user_notification_id": 3,
            "notification_id": 3
        }
    ],
    "processed": 5,
    "success_count": 5
}
```

**Response Error (400):**

```json
{
    "success": false,
    "message": "Không có thông báo nào được chọn"
}
```

---

# 6. Complete Examples

## Example 1: Student Registration Flow

```
1. Admin creates student
   POST /api/v1/students
   {
     "full_name": "Nguyễn Văn A",
     "student_code": "SV001",
     "email": "nguyenvana@email.com",
     "class_id": 5
   }

2. StudentService auto-generates account and sends Kafka event
   Topic: student.registered
   Payload: {
     "user_id": 1,
     "name": "Nguyễn Văn A",
     "user_name": "sv_SV001",
     "password": "123456"
   }

3. Kafka Consumer picks up event and routes to RegisterStudentHandle

4. Handler sends notification
   Template: student_account_created
   Recipients: [{"user_id": 1, "user_type": "student"}]
   Channels: email, push, in_app

5. Student receives:
   a) Email: HTML email with account info
   b) Push: Real-time notification via WebSocket
   c) In-app: Stored in database, visible in app

6. Student can check notifications
   GET /api/v1/internal/notifications/user
   Authorization: Bearer {student_jwt_token}
```

---

## Example 2: Task Creation Flow

```
1. Lecturer creates task
   POST /api/v1/tasks
   {
     "title": "Bài tập tuần 1",
     "description": "Làm bài tập về nhà",
     "deadline": "2024-01-20 23:59:59",
     "priority": "high",
     "receivers": [
       {"receiver_id": 1, "receiver_type": "student"},
       {"receiver_id": 2, "receiver_type": "student"}
     ]
   }

2. TaskService creates task and dispatches event
   event(new TaskCreated($task, [
     'creator_id' => 1,
     'creator_type' => 'lecturer',
     'receivers' => [...]
   ]))

3. TaskCreated event triggers SendTaskCreatedNotificationJob for each receiver

4. Job sends Kafka event for each student
   Topic: task.created (handled by TaskCreatedHandler)
   Payload: {
     "task_id": 1,
     "task_title": "Bài tập tuần 1",
     "task_description": "Làm bài tập về nhà",
     "deadline": "2024-01-20 23:59:59",
     "priority": "high",
     "creator_name": "Trần Thị B",
     "receiver_id": 1,
     "receiver_type": "student",
     "task_url": "http://localhost:3000/tasks/1"
   }

5. Handler sends notification
   Template: task_created
   Channels: email, push, in_app

6. Students receive notifications:
   - Student 1: Email + Push + In-app
   - Student 2: Email + Push + In-app

7. Students see real-time notification via WebSocket:
   Event: UserNotificationPushed on channel user-student-1
   Data: { content: "Bạn có bài tập mới: Bài tập tuần 1", ... }
```

---

## Example 3: Using Single API to Publish Event

```
POST /api/v1/events/publish
{
  "topic": "custom.event",
  "payload": {
    "message": "Custom notification",
    "user_id": 1,
    "data": {...}
  },
  "priority": "high"
}

Response:
{
  "success": true,
  "message": "Event published successfully",
  "data": {
    "event_type": "custom.event",
    "event_id": "custom.event_1642234567",
    "timestamp": "2024-01-15T10:30:00.000Z"
  }
}

// Event will be consumed by appropriate handler if registered
```

---

# 7. Notification Channels

## 7.1. Email Channel

-   **Service:** `EmailService`
-   **Queue:** `emails` queue
-   **Job:** `SendEmailNotificationJob`
-   **Processing:** Async via Laravel Queue
-   **Template:** HTML email với variables

## 7.2. Push Channel

-   **Service:** `PushService`
-   **Transport:** WebSocket (Laravel Broadcasting)
-   **Event:** `UserNotificationPushed`
-   **Real-time:** Yes
-   **Private Channel:** `private-user-{user_type}-{user_id}`

## 7.3. In-App Channel

-   **Storage:** Database (`user_notifications` table)
-   **Retrieval:** `/internal/notifications/user`
-   **Mark Read:** `/internal/notifications/mark-read`
-   **Real-time Updates:** Via WebSocket

## 7.4. SMS Channel

-   **Service:** `SmsService`
-   **Status:** Placeholder (not implemented)

---

# 8. Error Codes

| Status Code | Description                       |
| ----------- | --------------------------------- |
| 200         | Success                           |
| 400         | Bad Request                       |
| 401         | Unauthorized                      |
| 404         | Not Found (Template/Notification) |
| 500         | Internal Server Error             |

---

# 9. Notes

1. **Async Processing**: Tất cả notifications được xử lý bất đồng bộ qua Kafka và Laravel Queue
2. **Real-time**: WebSocket cho push notifications real-time
3. **Multi-channel**: Mỗi notification có thể gửi qua nhiều channels
4. **Template System**: Sử dụng templates với variables `{{variable_name}}`
5. **Event-Driven**: Loosely coupled architecture qua Kafka events
6. **Scalable**: Kafka cho phép scale horizontally
7. **Retry Logic**: Queue system tự động retry failed jobs
8. **Logging**: Tất cả notifications đều được log chi tiết

---

# 10. Kafka Topics Summary

| Topic                 | Producer            | Handler                | Template                 | Receivers      |
| --------------------- | ------------------- | ---------------------- | ------------------------ | -------------- |
| `student.registered`  | StudentService      | RegisterStudentHandle  | student_account_created  | New student    |
| `lecturer.registered` | LecturerService     | RegisterLecturerHandle | lecturer_account_created | New lecturer   |
| `task.assigned`       | TaskService         | TaskAssignedHandler    | task_assigned            | Task receivers |
| `task.created`        | TaskCreated Event   | TaskCreatedHandler     | task_created             | Task receivers |
| `task.updated`        | TaskUpdated Event   | TaskUpdatedHandler     | task_updated             | Task receivers |
| `task.submitted`      | TaskSubmitted Event | TaskSubmittedHandler   | task_submitted           | Task creator   |
| `task.graded`         | TaskGraded Event    | TaskGradedHandler      | task_graded              | Student        |

---

# 11. Quick Integration Guide

## For Backend Services:

```php
// Option 1: Send Kafka event
$kafkaProducer->send('student.registered', [
    'user_id' => 1,
    'name' => 'Nguyễn Văn A',
    'user_name' => 'sv_SV001',
    'password' => '123456'
]);

// Option 2: Use REST API
POST http://localhost:8000/api/v1/events/publish
{
  "topic": "student.registered",
  "payload": {...}
}

// Option 3: Direct notification service
$notificationService->sendNotification(
    'template_name',
    [['user_id' => 1, 'user_type' => 'student']],
    ['key' => 'value']
);
```

## For Frontend:

```javascript
// Subscribe to real-time notifications
Echo.private(`user-student-${userId}`).listen(
    "UserNotificationPushed",
    (notification) => {
        // Show toast/alert
        showNotification(notification);
    }
);

// Get notifications list
const response = await axios.get("/api/v1/internal/notifications/user", {
    headers: { Authorization: `Bearer ${token}` },
    params: { limit: 20, offset: 0 },
});

// Mark as read
await axios.post(
    "/api/v1/internal/notifications/mark-read",
    {
        notification_ids: [1, 2, 3],
    },
    {
        headers: { Authorization: `Bearer ${token}` },
    }
);
```

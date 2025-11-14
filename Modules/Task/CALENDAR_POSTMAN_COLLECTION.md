# 📮 Calendar API - Postman Collection

## 🔧 Cấu Hình Cơ Bản

### Base URL
```
http://localhost:8082/api/v1
```

### Headers (Bắt Buộc)
```
Authorization: Bearer {YOUR_JWT_TOKEN}
Content-Type: application/json
Accept: application/json
```

### Lấy JWT Token
**Endpoint**: `POST /api/v1/auth/login`

**Body**:
```json
{
  "username": "admin",
  "password": "password123"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 1,
      "user_type": "admin"
    }
  }
}
```

---

## 👨‍💼 ADMIN CALENDAR APIs

### 1. Lấy Tất Cả Events (với Pagination)

**Method**: `GET`  
**URL**: `{{base_url}}/calendar/events`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params**:
```
page=1
per_page=15
```

**Example URL**:
```
http://localhost:8082/api/v1/calendar/events?page=1&per_page=15
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "title": "Task Title",
        "description": "Task Description",
        "start": "2025-01-20 10:00:00",
        "end": "2025-01-20 10:00:00",
        "start_time": "2025-01-20 10:00:00",
        "end_time": "2025-01-20 10:00:00",
        "event_type": "task",
        "task_id": 1,
        "status": "pending",
        "priority": "high",
        "class_id": 1,
        "creator": {
          "id": 1,
          "type": "lecturer",
          "name": "Lecturer Name"
        },
        "receivers": [
          {
            "id": 2,
            "type": "student",
            "name": "Student Name"
          }
        ],
        "files_count": 2,
        "submissions_count": 5,
        "created_at": "2025-01-15 08:00:00",
        "updated_at": "2025-01-18 12:00:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 100,
      "last_page": 7,
      "from": 1,
      "to": 15
    }
  },
  "message": "All calendar events retrieved successfully"
}
```

---

### 2. Lấy Events Theo Ngày

**Method**: `GET`  
**URL**: `{{base_url}}/calendar/events/by-date`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params**:
```
date=2025-01-20
```

**Example URL**:
```
http://localhost:8082/api/v1/calendar/events/by-date?date=2025-01-20
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "date": "2025-01-20",
    "events": [
      {
        "id": 1,
        "title": "Task Title",
        "description": "Task Description",
        "start": "2025-01-20 10:00:00",
        "end": "2025-01-20 10:00:00",
        "event_type": "task",
        "task_id": 1,
        "status": "pending",
        "priority": "high"
      }
    ],
    "count": 1
  },
  "message": "Events retrieved successfully"
}
```

---

### 3. Lấy Events Theo Khoảng Thời Gian

**Method**: `GET`  
**URL**: `{{base_url}}/calendar/events/by-range`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Có thể dùng `start`/`end` hoặc `start_date`/`end_date`):
```
start=2025-01-01
end=2025-01-31
```

**Hoặc**:
```
start_date=2025-01-01
end_date=2025-01-31
```

**Example URL**:
```
http://localhost:8082/api/v1/calendar/events/by-range?start=2025-01-01&end=2025-01-31
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "start_date": "2025-01-01 00:00:00",
    "end_date": "2025-01-31 23:59:59",
    "events": [
      {
        "id": 1,
        "title": "Task Title",
        "start": "2025-01-20 10:00:00",
        "end": "2025-01-20 10:00:00",
        "event_type": "task",
        "status": "pending"
      }
    ],
    "count": 1
  },
  "message": "Events retrieved successfully"
}
```

---

### 4. Lấy Events Sắp Tới

**Method**: `GET`  
**URL**: `{{base_url}}/calendar/events/upcoming`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Optional):
```
limit=10
```

**Example URL**:
```
http://localhost:8082/api/v1/calendar/events/upcoming?limit=10
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "events": [
      {
        "id": 1,
        "title": "Task Title",
        "start": "2025-01-25 10:00:00",
        "end": "2025-01-25 10:00:00",
        "event_type": "task",
        "status": "pending"
      }
    ],
    "count": 1,
    "period": {
      "start": "2025-01-20 10:00:00",
      "end": "2025-02-19 10:00:00"
    }
  },
  "message": "Upcoming events retrieved successfully"
}
```

---

### 5. Lấy Events Quá Hạn

**Method**: `GET`  
**URL**: `{{base_url}}/calendar/events/overdue`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Example URL**:
```
http://localhost:8082/api/v1/calendar/events/overdue
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "events": [
      {
        "id": 1,
        "title": "Task Title",
        "start": "2025-01-15 10:00:00",
        "end": "2025-01-15 10:00:00",
        "event_type": "task",
        "status": "pending"
      }
    ],
    "count": 1
  },
  "message": "Overdue events retrieved successfully"
}
```

---

### 6. Đếm Events Theo Status

**Method**: `GET`  
**URL**: `{{base_url}}/calendar/events/count-by-status`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Example URL**:
```
http://localhost:8082/api/v1/calendar/events/count-by-status
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "counts": {
      "total": 100,
      "pending": 20,
      "in_progress": 30,
      "completed": 40,
      "overdue": 5,
      "upcoming": 5
    },
    "total": 100
  },
  "message": "Events count retrieved successfully"
}
```

---

### 7. Lấy Events Theo Loại (Priority)

**Method**: `GET`  
**URL**: `{{base_url}}/calendar/events/by-type`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Required):
```
type=high
```

**Các giá trị có thể**: `low`, `medium`, `high`, `urgent`

**Example URL**:
```
http://localhost:8082/api/v1/calendar/events/by-type?type=high
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "type": "high",
    "events": [
      {
        "id": 1,
        "title": "Task Title",
        "priority": "high",
        "status": "pending"
      }
    ],
    "count": 1
  },
  "message": "Events retrieved successfully"
}
```

**Error Response** (422 Unprocessable Entity):
```json
{
  "success": false,
  "message": "Type parameter is required"
}
```

---

### 8. Lấy Recurring Events

**Method**: `GET`  
**URL**: `{{base_url}}/calendar/events/recurring`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Example URL**:
```
http://localhost:8082/api/v1/calendar/events/recurring
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "events": [],
    "count": 0
  },
  "message": "Recurring events retrieved successfully"
}
```

---

### 9. Lấy Reminders

**Method**: `GET`  
**URL**: `{{base_url}}/calendar/reminders`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Example URL**:
```
http://localhost:8082/api/v1/calendar/reminders
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "reminders": [],
    "count": 0
  },
  "message": "Reminders retrieved successfully"
}
```

---

### 10. Tạo Reminder

**Method**: `POST`  
**URL**: `{{base_url}}/calendar/reminders`  
**Headers**: 
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** (JSON):
```json
{
  "title": "Reminder Title",
  "remind_at": "2025-01-25 10:00:00",
  "user_id": 1,
  "user_type": "admin"
}
```

**Example URL**:
```
http://localhost:8082/api/v1/calendar/reminders
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "reminder": {
      "id": 1234,
      "title": "Reminder Title",
      "remind_at": "2025-01-25 10:00:00",
      "user_id": 1,
      "user_type": "admin",
      "created_at": "2025-01-20 10:00:00"
    },
    "success": true
  },
  "message": "Reminder set successfully"
}
```

---

## 👨‍🏫 LECTURER CALENDAR APIs

### 1. Lấy Events Của Lecturer

**Method**: `GET`  
**URL**: `{{base_url}}/lecturer-calendar/events`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Optional):
```
page=1
per_page=15
status=pending
priority=high
date_from=2025-01-01
date_to=2025-01-31
search=task title
```

**Example URL**:
```
http://localhost:8082/api/v1/lecturer-calendar/events?page=1&per_page=15&status=pending
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Lecturer events retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Task Title",
      "description": "Task Description",
      "start": "2025-01-20 10:00:00",
      "end": "2025-01-20 10:00:00",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high",
      "creator": {
        "id": 1,
        "type": "lecturer",
        "name": "Lecturer Name"
      },
      "receivers": [
        {
          "id": 2,
          "type": "student",
          "name": "Student Name"
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4,
    "from": 1,
    "to": 15
  }
}
```

---

### 2. Tạo Event Mới (Standalone Calendar Event)

**Method**: `POST`  
**URL**: `{{base_url}}/lecturer-calendar/events`  
**Headers**: 
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** (JSON):
```json
{
  "title": "Meeting with Students",
  "description": "Discuss project progress",
  "start_time": "2025-01-25 14:00:00",
  "end_time": "2025-01-25 16:00:00",
  "event_type": "event"
}
```

**Example URL**:
```
http://localhost:8082/api/v1/lecturer-calendar/events
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Event created successfully",
  "data": {
    "id": 1,
    "title": "Meeting with Students",
    "description": "Discuss project progress",
    "start_time": "2025-01-25 14:00:00",
    "end_time": "2025-01-25 16:00:00",
    "event_type": "event",
    "task_id": null,
    "creator_id": 1,
    "creator_type": "lecturer",
    "created_at": "2025-01-20 10:00:00"
  }
}
```

---

### 3. Cập Nhật Event

**Method**: `PUT`  
**URL**: `{{base_url}}/lecturer-calendar/events/{event_id}`  
**Headers**: 
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** (JSON):
```json
{
  "title": "Updated Meeting Title",
  "description": "Updated description",
  "start_time": "2025-01-25 15:00:00",
  "end_time": "2025-01-25 17:00:00"
}
```

**Example URL**:
```
http://localhost:8082/api/v1/lecturer-calendar/events/1
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Event updated successfully",
  "data": {
    "id": 1,
    "title": "Updated Meeting Title",
    "description": "Updated description",
    "start_time": "2025-01-25 15:00:00",
    "end_time": "2025-01-25 17:00:00",
    "updated_at": "2025-01-20 11:00:00"
  }
}
```

**Error Response** (403 Forbidden):
```json
{
  "success": false,
  "message": "Access denied"
}
```

---

### 4. Xóa Event

**Method**: `DELETE`  
**URL**: `{{base_url}}/lecturer-calendar/events/{event_id}`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Example URL**:
```
http://localhost:8082/api/v1/lecturer-calendar/events/1
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Event deleted successfully"
}
```

**Error Response** (404 Not Found):
```json
{
  "success": false,
  "message": "Event not found"
}
```

---

### 5. Lấy Events Theo Ngày

**Method**: `GET`  
**URL**: `{{base_url}}/lecturer-calendar/events/by-date`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Required):
```
date=2025-01-20
```

**Example URL**:
```
http://localhost:8082/api/v1/lecturer-calendar/events/by-date?date=2025-01-20
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Events by date retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Task Title",
      "start": "2025-01-20 10:00:00",
      "end": "2025-01-20 10:00:00",
      "event_type": "task",
      "status": "pending"
    }
  ]
}
```

---

### 6. Lấy Events Theo Khoảng Thời Gian

**Method**: `GET`  
**URL**: `{{base_url}}/lecturer-calendar/events/by-range`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Required):
```
start=2025-01-01
end=2025-01-31
```

**Example URL**:
```
http://localhost:8082/api/v1/lecturer-calendar/events/by-range?start=2025-01-01&end=2025-01-31
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Events by range retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Task Title",
      "start": "2025-01-20 10:00:00",
      "end": "2025-01-20 10:00:00",
      "event_type": "task"
    }
  ]
}
```

---

### 7. Lấy Events Sắp Tới

**Method**: `GET`  
**URL**: `{{base_url}}/lecturer-calendar/events/upcoming`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Optional):
```
limit=10
```

**Example URL**:
```
http://localhost:8082/api/v1/lecturer-calendar/events/upcoming?limit=10
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Upcoming events retrieved successfully",
  "data": {
    "events": [
      {
        "id": 1,
        "title": "Task Title",
        "start": "2025-01-25 10:00:00",
        "end": "2025-01-25 10:00:00",
        "event_type": "task"
      }
    ],
    "count": 1
  }
}
```

---

### 8. Lấy Events Quá Hạn

**Method**: `GET`  
**URL**: `{{base_url}}/lecturer-calendar/events/overdue`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Example URL**:
```
http://localhost:8082/api/v1/lecturer-calendar/events/overdue
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Overdue events retrieved successfully",
  "data": {
    "events": [
      {
        "id": 1,
        "title": "Task Title",
        "start": "2025-01-15 10:00:00",
        "end": "2025-01-15 10:00:00",
        "event_type": "task",
        "status": "pending"
      }
    ],
    "count": 1
  }
}
```

---

### 9. Đếm Events Theo Status

**Method**: `GET`  
**URL**: `{{base_url}}/lecturer-calendar/events/count-by-status`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Example URL**:
```
http://localhost:8082/api/v1/lecturer-calendar/events/count-by-status
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Events count by status retrieved successfully",
  "data": {
    "counts": {
      "total": 50,
      "pending": 10,
      "in_progress": 15,
      "completed": 20,
      "overdue": 3,
      "upcoming": 2
    },
    "total": 50
  }
}
```

---

## 👨‍🎓 STUDENT CALENDAR APIs

### 1. Lấy Events Của Student

**Method**: `GET`  
**URL**: `{{base_url}}/student-calendar/events`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Optional):
```
page=1
per_page=15
status=pending
priority=high
date_from=2025-01-01
date_to=2025-01-31
search=task title
```

**Example URL**:
```
http://localhost:8082/api/v1/student-calendar/events?page=1&per_page=15
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Student events retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Task Title",
      "description": "Task Description",
      "start": "2025-01-20 10:00:00",
      "end": "2025-01-20 10:00:00",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high",
      "creator": {
        "id": 1,
        "type": "lecturer",
        "name": "Lecturer Name"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 30,
    "last_page": 2,
    "from": 1,
    "to": 15
  }
}
```

---

### 2. Lấy Events Theo Ngày

**Method**: `GET`  
**URL**: `{{base_url}}/student-calendar/events/by-date`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Required):
```
date=2025-01-20
```

**Example URL**:
```
http://localhost:8082/api/v1/student-calendar/events/by-date?date=2025-01-20
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Events by date retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Task Title",
      "start": "2025-01-20 10:00:00",
      "end": "2025-01-20 10:00:00",
      "event_type": "task",
      "status": "pending"
    }
  ]
}
```

---

### 3. Lấy Events Theo Khoảng Thời Gian

**Method**: `GET`  
**URL**: `{{base_url}}/student-calendar/events/by-range`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Required - Lưu ý: dùng `start_date`/`end_date` chứ không phải `start`/`end`):
```
start_date=2025-01-01
end_date=2025-01-31
```

**Example URL**:
```
http://localhost:8082/api/v1/student-calendar/events/by-range?start_date=2025-01-01&end_date=2025-01-31
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Events by range retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Task Title",
      "start": "2025-01-20 10:00:00",
      "end": "2025-01-20 10:00:00",
      "event_type": "task"
    }
  ]
}
```

---

### 4. Lấy Events Sắp Tới

**Method**: `GET`  
**URL**: `{{base_url}}/student-calendar/events/upcoming`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Optional):
```
limit=10
```

**Example URL**:
```
http://localhost:8082/api/v1/student-calendar/events/upcoming?limit=10
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Upcoming events retrieved successfully",
  "data": {
    "events": [
      {
        "id": 1,
        "title": "Task Title",
        "start": "2025-01-25 10:00:00",
        "end": "2025-01-25 10:00:00",
        "event_type": "task"
      }
    ],
    "count": 1
  }
}
```

---

### 5. Lấy Events Quá Hạn

**Method**: `GET`  
**URL**: `{{base_url}}/student-calendar/events/overdue`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Example URL**:
```
http://localhost:8082/api/v1/student-calendar/events/overdue
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Overdue events retrieved successfully",
  "data": {
    "events": [
      {
        "id": 1,
        "title": "Task Title",
        "start": "2025-01-15 10:00:00",
        "end": "2025-01-15 10:00:00",
        "event_type": "task",
        "status": "pending"
      }
    ],
    "count": 1
  }
}
```

---

### 6. Đếm Events Theo Status

**Method**: `GET`  
**URL**: `{{base_url}}/student-calendar/events/count-by-status`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Example URL**:
```
http://localhost:8082/api/v1/student-calendar/events/count-by-status
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Events count by status retrieved successfully",
  "data": {
    "counts": {
      "total": 30,
      "pending": 5,
      "in_progress": 10,
      "completed": 12,
      "overdue": 2,
      "upcoming": 1
    },
    "total": 30
  }
}
```

---

### 7. Lấy Reminders

**Method**: `GET`  
**URL**: `{{base_url}}/student-calendar/reminders`  
**Headers**: 
```
Authorization: Bearer {token}
```

**Query Params** (Optional):
```
page=1
per_page=15
status=pending
type=email
```

**Example URL**:
```
http://localhost:8082/api/v1/student-calendar/reminders?page=1&per_page=15
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Student reminders retrieved successfully",
  "data": [
    {
      "id": 1,
      "task_id": 1,
      "user_id": 2,
      "user_type": "student",
      "reminder_type": "email",
      "reminder_time": "2025-01-25 09:00:00",
      "message": "Reminder: Task deadline is approaching",
      "status": "pending",
      "created_at": "2025-01-20 10:00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 5,
    "last_page": 1,
    "from": 1,
    "to": 5
  }
}
```

---

### 8. Tạo Reminder

**Method**: `POST`  
**URL**: `{{base_url}}/student-calendar/setReminder`  
**Headers**: 
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** (JSON):
```json
{
  "task_id": 1,
  "reminder_type": "email",
  "reminder_time": "2025-01-25 09:00:00",
  "message": "Reminder: Task deadline is approaching"
}
```

**Example URL**:
```
http://localhost:8082/api/v1/student-calendar/setReminder
```

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Reminder set successfully",
  "data": {
    "id": 1,
    "task_id": 1,
    "user_id": 2,
    "user_type": "student",
    "reminder_type": "email",
    "reminder_time": "2025-01-25 09:00:00",
    "message": "Reminder: Task deadline is approaching",
    "status": "pending",
    "created_at": "2025-01-20 10:00:00"
  }
}
```

---

## ⚠️ Lưu Ý Quan Trọng

### 1. **Authentication**
- Tất cả endpoints đều yêu cầu JWT token
- Token phải được gửi trong header: `Authorization: Bearer {token}`
- Token hết hạn sẽ trả về 401 Unauthorized

### 2. **Date Format**
- Format chuẩn: `Y-m-d` (ví dụ: `2025-01-20`)
- Format datetime: `Y-m-d H:i:s` (ví dụ: `2025-01-20 10:00:00`)

### 3. **Query Parameters**
- **Admin/Lecturer**: Dùng `start`/`end` cho date range
- **Student**: Dùng `start_date`/`end_date` cho date range
- Tất cả query params đều optional trừ khi ghi chú là Required

### 4. **Response Format**
- Tất cả responses đều có format:
  ```json
  {
    "success": true/false,
    "message": "Message text",
    "data": { ... }
  }
  ```

### 5. **Error Responses**
- **401 Unauthorized**: Token không hợp lệ hoặc thiếu
- **403 Forbidden**: Không có quyền truy cập
- **404 Not Found**: Resource không tồn tại
- **422 Unprocessable Entity**: Validation error
- **500 Internal Server Error**: Server error

### 6. **Pagination**
- Mặc định: `page=1`, `per_page=15`
- Response bao gồm metadata pagination

### 7. **Status Values**
- Task status: `pending`, `in_progress`, `completed`
- Priority: `low`, `medium`, `high`, `urgent`
- Reminder type: `email`, `push`, `sms`, `in_app`
- Reminder status: `pending`, `sent`, `failed`, `cancelled`

---

## 🧪 Test Checklist

### Admin APIs
- [ ] GET `/calendar/events` - Lấy tất cả events
- [ ] GET `/calendar/events/by-date?date=2025-01-20`
- [ ] GET `/calendar/events/by-range?start=2025-01-01&end=2025-01-31`
- [ ] GET `/calendar/events/upcoming?limit=10`
- [ ] GET `/calendar/events/overdue`
- [ ] GET `/calendar/events/count-by-status`
- [ ] GET `/calendar/events/by-type?type=high`
- [ ] GET `/calendar/events/recurring`
- [ ] GET `/calendar/reminders`
- [ ] POST `/calendar/reminders`

### Lecturer APIs
- [ ] GET `/lecturer-calendar/events?page=1&per_page=15`
- [ ] POST `/lecturer-calendar/events` - Tạo event
- [ ] PUT `/lecturer-calendar/events/{id}` - Cập nhật event
- [ ] DELETE `/lecturer-calendar/events/{id}` - Xóa event
- [ ] GET `/lecturer-calendar/events/by-date?date=2025-01-20`
- [ ] GET `/lecturer-calendar/events/by-range?start=2025-01-01&end=2025-01-31`
- [ ] GET `/lecturer-calendar/events/upcoming?limit=10`
- [ ] GET `/lecturer-calendar/events/overdue`
- [ ] GET `/lecturer-calendar/events/count-by-status`

### Student APIs
- [ ] GET `/student-calendar/events?page=1&per_page=15`
- [ ] GET `/student-calendar/events/by-date?date=2025-01-20`
- [ ] GET `/student-calendar/events/by-range?start_date=2025-01-01&end_date=2025-01-31`
- [ ] GET `/student-calendar/events/upcoming?limit=10`
- [ ] GET `/student-calendar/events/overdue`
- [ ] GET `/student-calendar/events/count-by-status`
- [ ] GET `/student-calendar/reminders`
- [ ] POST `/student-calendar/setReminder`

---

**Last Updated**: 2025-01-20  
**Version**: 2.0.0


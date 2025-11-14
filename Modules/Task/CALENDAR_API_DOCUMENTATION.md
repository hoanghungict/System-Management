# 📅 Calendar API Documentation

## 📋 Tổng Quan

Tài liệu này liệt kê **chính xác** tất cả API endpoints cho Calendar Module của cả 3 roles:
- **Admin** (`/api/v1/calendar`)
- **Lecturer** (`/api/v1/lecturer-calendar`)
- **Student** (`/api/v1/student-calendar`)

**Base URL:** `http://your-domain.com/api/v1`

**Authentication:** Tất cả endpoints yêu cầu JWT token trong header:
```
Authorization: Bearer {token}
```

---

## 👨‍💼 ADMIN CALENDAR APIs

### Base URL: `/api/v1/calendar`

**Middleware:** `jwt`, `admin`

---

### 1. **GET** `/api/v1/calendar/events`

Lấy tất cả events trong hệ thống (có pagination)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Số trang |
| `per_page` | integer | No | 15 | Số items per page |

**Example Request:**
```
GET /api/v1/calendar/events?page=1&per_page=20
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "All calendar events retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "title": "Assignment 1",
        "description": "Complete the assignment",
        "start": "2025-02-15 23:59:59",
        "end": "2025-02-15 23:59:59",
        "start_time": "2025-02-15 23:59:59",
        "end_time": "2025-02-15 23:59:59",
        "event_type": "task",
        "task_id": 1,
        "status": "pending",
        "priority": "high",
        "creator": {
          "id": 10,
          "type": "lecturer",
          "name": "Dr. Smith"
        },
        "receivers": [
          {
            "id": 20,
            "type": "student",
            "name": "John Doe"
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 50,
      "last_page": 3,
      "from": 1,
      "to": 20
    }
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Error retrieving all events: {error_message}"
}
```

---

### 2. **GET** `/api/v1/calendar/events/by-date`

Lấy events theo ngày cụ thể

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `date` | string | No | Today | Ngày cần lấy (format: `Y-m-d`, ví dụ: `2025-02-15`) |

**Example Request:**
```
GET /api/v1/calendar/events/by-date?date=2025-02-15
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events retrieved successfully",
  "data": {
    "date": "2025-02-15",
    "events": [
      {
        "id": 1,
        "title": "Assignment 1",
        "start": "2025-02-15 23:59:59",
        "end": "2025-02-15 23:59:59",
        "event_type": "task",
        "task_id": 1,
        "status": "pending",
        "priority": "high"
      }
    ],
    "count": 1
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Error retrieving events: {error_message}"
}
```

---

### 3. **GET** `/api/v1/calendar/events/by-range`

Lấy events theo khoảng thời gian

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `start` | string | No | Today | Ngày/giờ bắt đầu (format: `Y-m-d` hoặc `Y-m-d H:i:s`) |
| `end` | string | No | +30 days | Ngày/giờ kết thúc (format: `Y-m-d` hoặc `Y-m-d H:i:s`) |
| `start_date` | string | No | - | Alternative: Ngày bắt đầu (hỗ trợ cả 2 format) |
| `end_date` | string | No | - | Alternative: Ngày kết thúc (hỗ trợ cả 2 format) |

**Example Request:**
```
GET /api/v1/calendar/events/by-range?start=2025-11-01&end=2025-11-30
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events retrieved successfully",
  "data": {
    "start_date": "2025-11-01 00:00:00",
    "end_date": "2025-11-30 23:59:59",
    "events": [
      {
        "id": 1,
        "title": "Assignment 1",
        "start": "2025-11-15 23:59:59",
        "end": "2025-11-15 23:59:59",
        "event_type": "task",
        "task_id": 1,
        "status": "pending",
        "priority": "high"
      }
    ],
    "count": 1
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Error retrieving events: {error_message}"
}
```

---

### 4. **GET** `/api/v1/calendar/events/upcoming`

Lấy events sắp tới (trong 30 ngày)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | No | 10 | Số lượng events tối đa |

**Example Request:**
```
GET /api/v1/calendar/events/upcoming?limit=20
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Upcoming events retrieved successfully",
  "data": {
    "events": [
      {
        "id": 1,
        "title": "Assignment 1",
        "start": "2025-11-20 23:59:59",
        "end": "2025-11-20 23:59:59",
        "event_type": "task",
        "task_id": 1,
        "status": "pending",
        "priority": "high"
      }
    ],
    "count": 1,
    "period": {
      "start": "2025-01-20 10:00:00",
      "end": "2025-02-19 10:00:00"
    }
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Error retrieving upcoming events: {error_message}"
}
```

---

### 5. **GET** `/api/v1/calendar/events/overdue`

Lấy events quá hạn

**Query Parameters:** Không có

**Example Request:**
```
GET /api/v1/calendar/events/overdue
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Overdue events retrieved successfully",
  "data": {
    "events": [
      {
        "id": 1,
        "title": "Assignment 1",
        "start": "2025-01-15 23:59:59",
        "end": "2025-01-15 23:59:59",
        "event_type": "task",
        "task_id": 1,
        "status": "pending",
        "priority": "high"
      }
    ],
    "count": 1
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Error retrieving overdue events: {error_message}"
}
```

---

### 6. **GET** `/api/v1/calendar/events/count-by-status`

Đếm events theo trạng thái

**Query Parameters:** Không có

**Example Request:**
```
GET /api/v1/calendar/events/count-by-status
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events count retrieved successfully",
  "data": {
    "counts": {
      "total": 50,
      "pending": 20,
      "in_progress": 15,
      "completed": 10,
      "overdue": 5,
      "upcoming": 30
    },
    "total": 50
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Error retrieving events count: {error_message}"
}
```

---

### 7. **GET** `/api/v1/calendar/events/by-type`

Lấy events theo loại/priority

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `type` | string | **Yes** | - | Priority type: `low`, `medium`, `high`, `urgent` |

**Example Request:**
```
GET /api/v1/calendar/events/by-type?type=high
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events retrieved successfully",
  "data": {
    "type": "high",
    "events": [
      {
        "id": 1,
        "title": "Assignment 1",
        "priority": "high",
        "start_time": "2025-02-15 23:59:59",
        "end_time": "2025-02-15 23:59:59",
        "event_type": "task",
        "task_id": 1,
        "status": "pending"
      }
    ],
    "count": 1
  }
}
```

**Error Response:**
- `422 Unprocessable Entity` - Thiếu parameter `type`
- `500 Internal Server Error` - Lỗi server

```json
{
  "success": false,
  "message": "Type parameter is required"
}
```

---

### 8. **GET** `/api/v1/calendar/events/recurring`

Lấy recurring events (tạm thời mock, sẽ implement sau)

**Query Parameters:** Không có

**Example Request:**
```
GET /api/v1/calendar/events/recurring
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Recurring events retrieved successfully",
  "data": {
    "events": [],
    "count": 0
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Error retrieving recurring events: {error_message}"
}
```

---

### 9. **GET** `/api/v1/calendar/reminders`

Lấy reminders (tạm thời mock)

**Query Parameters:** Không có

**Example Request:**
```
GET /api/v1/calendar/reminders
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Reminders retrieved successfully",
  "data": {
    "reminders": [],
    "count": 0
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Error retrieving reminders: {error_message}"
}
```

---

### 10. **POST** `/api/v1/calendar/reminders`

Tạo reminder mới

**Request Body:**
```json
{
  "title": "Reminder for Meeting",
  "remind_at": "2025-02-20 14:00:00",
  "task_id": 1
}
```

**Example Request:**
```bash
POST /api/v1/calendar/reminders
Content-Type: application/json

{
  "title": "Reminder for Meeting",
  "remind_at": "2025-02-20 14:00:00",
  "task_id": 1
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Reminder set successfully",
  "data": {
    "reminder": {
      "id": 100,
      "title": "Reminder for Meeting",
      "remind_at": "2025-02-20 14:00:00",
      "user_id": null,
      "user_type": null,
      "created_at": "2025-01-20 10:00:00"
    },
    "success": true
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Error setting reminder: {error_message}"
}
```

---

## 👨‍🏫 LECTURER CALENDAR APIs

### Base URL: `/api/v1/lecturer-calendar`

**Middleware:** `jwt`, `lecturer`

**Lưu ý:** Lecturer có thể xem:
- Tasks họ tạo (`creator_id = lecturer_id AND creator_type = 'lecturer'`)
- Tasks được assign cho họ (có trong `receivers`)

---

### 1. **GET** `/api/v1/lecturer-calendar/events`

Lấy danh sách events của lecturer (có pagination và filters)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Số trang |
| `per_page` | integer | No | 15 | Số items per page |
| `status` | string | No | - | Filter theo status: `pending`, `in_progress`, `completed`, `overdue` |
| `priority` | string | No | - | Filter theo priority: `low`, `medium`, `high`, `urgent` |
| `date_from` | string | No | - | Ngày bắt đầu (format: `Y-m-d`) |
| `date_to` | string | No | - | Ngày kết thúc (format: `Y-m-d`) |
| `search` | string | No | - | Tìm kiếm trong title/description |

**Example Request:**
```
GET /api/v1/lecturer-calendar/events?page=1&per_page=20&status=pending&priority=high
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Lecturer events retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "description": "Complete the assignment",
      "start": "2025-02-15 23:59:59",
      "end": "2025-02-15 23:59:59",
      "start_time": "2025-02-15 23:59:59",
      "end_time": "2025-02-15 23:59:59",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high",
      "class_id": 5,
      "creator": {
        "id": 10,
        "type": "lecturer"
      },
      "receivers": [
        {
          "id": 20,
          "type": "student"
        }
      ],
      "files_count": 2,
      "submissions_count": 5
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3,
    "from": 1,
    "to": 20
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve lecturer events: {error_message}"
}
```

---

### 2. **GET** `/api/v1/lecturer-calendar/events/by-date`

Lấy events theo ngày cụ thể

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `date` | string | **Yes** | - | Ngày cần lấy (format: `Y-m-d`, ví dụ: `2025-02-15`) |

**Example Request:**
```
GET /api/v1/lecturer-calendar/events/by-date?date=2025-02-15
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events by date retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "start": "2025-02-15 23:59:59",
      "end": "2025-02-15 23:59:59",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high"
    }
  ]
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve events by date: {error_message}"
}
```

---

### 3. **GET** `/api/v1/lecturer-calendar/events/by-range`

Lấy events theo khoảng thời gian

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `start` | string | **Yes** | - | Ngày/giờ bắt đầu (format: `Y-m-d` hoặc `Y-m-d H:i:s`) |
| `end` | string | **Yes** | - | Ngày/giờ kết thúc (format: `Y-m-d` hoặc `Y-m-d H:i:s`) |

**Example Request:**
```
GET /api/v1/lecturer-calendar/events/by-range?start=2025-02-01&end=2025-02-28
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events by range retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "start": "2025-02-15 23:59:59",
      "end": "2025-02-15 23:59:59",
      "event_type": "task",
      "task_id": 1
    }
  ]
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve events by range: {error_message}"
}
```

---

### 4. **GET** `/api/v1/lecturer-calendar/events/upcoming`

Lấy events sắp tới (trong 30 ngày)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | No | 10 | Số lượng events tối đa |

**Example Request:**
```
GET /api/v1/lecturer-calendar/events/upcoming?limit=20
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Upcoming events retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "start": "2025-02-20 23:59:59",
      "end": "2025-02-20 23:59:59",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high"
    }
  ]
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve upcoming events: {error_message}"
}
```

---

### 5. **GET** `/api/v1/lecturer-calendar/events/overdue`

Lấy events quá hạn

**Query Parameters:** Không có

**Example Request:**
```
GET /api/v1/lecturer-calendar/events/overdue
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Overdue events retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "start": "2025-01-15 23:59:59",
      "end": "2025-01-15 23:59:59",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high"
    }
  ]
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve overdue events: {error_message}"
}
```

---

### 6. **GET** `/api/v1/lecturer-calendar/events/count-by-status`

Đếm events theo trạng thái

**Query Parameters:** Không có

**Example Request:**
```
GET /api/v1/lecturer-calendar/events/count-by-status
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events count by status retrieved successfully",
  "data": {
    "total": 50,
    "pending": 20,
    "in_progress": 15,
    "completed": 10,
    "overdue": 5,
    "upcoming": 30
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve events count by status: {error_message}"
}
```

---

### 7. **GET** `/api/v1/lecturer-calendar/reminders`

Lấy reminders của lecturer (tạm thời mock)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Số trang |
| `per_page` | integer | No | 15 | Số items per page |

**Example Request:**
```
GET /api/v1/lecturer-calendar/reminders
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Reminders retrieved successfully",
  "data": {
    "reminders": [],
    "count": 0
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve reminders: {error_message}"
}
```

---

### 8. **POST** `/api/v1/lecturer-calendar/reminders`

Tạo reminder mới

**Request Body:**
```json
{
  "title": "Reminder for Meeting",
  "remind_at": "2025-02-20 14:00:00",
  "task_id": 1
}
```

**Request Body Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | **Yes** | Tiêu đề reminder |
| `remind_at` | string | **Yes** | Thời gian nhắc (format: `Y-m-d H:i:s`) |
| `task_id` | integer | No | ID của task liên quan |

**Example Request:**
```bash
POST /api/v1/lecturer-calendar/reminders
Content-Type: application/json

{
  "title": "Reminder for Meeting",
  "remind_at": "2025-02-20 14:00:00",
  "task_id": 1
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Reminder set successfully",
  "data": {
    "reminder": {
      "id": 100,
      "title": "Reminder for Meeting",
      "remind_at": "2025-02-20 14:00:00",
      "user_id": 10,
      "user_type": "lecturer",
      "created_at": "2025-01-20 10:00:00"
    },
    "success": true
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to set reminder: {error_message}"
}
```

---

### 9. **POST** `/api/v1/lecturer-calendar/events`

Tạo calendar event mới (không phải task)

**Request Body:**
```json
{
  "title": "Team Meeting",
  "description": "Weekly team meeting",
  "start_time": "2025-02-20 14:00:00",
  "end_time": "2025-02-20 15:00:00",
  "event_type": "event"
}
```

**Request Body Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `title` | string | **Yes** | - | Tiêu đề event |
| `description` | string | No | `""` | Mô tả event |
| `start_time` | string | **Yes** | - | Thời gian bắt đầu (format: `Y-m-d H:i:s`) |
| `end_time` | string | **Yes** | - | Thời gian kết thúc (format: `Y-m-d H:i:s`) |
| `event_type` | string | No | `"event"` | Loại event: `event`, `task`, `reminder` |
| `task_id` | integer | No | `null` | ID của task liên quan (nếu có) |

**Example Request:**
```bash
POST /api/v1/lecturer-calendar/events
Content-Type: application/json

{
  "title": "Team Meeting",
  "description": "Weekly team meeting",
  "start_time": "2025-02-20 14:00:00",
  "end_time": "2025-02-20 15:00:00",
  "event_type": "event"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Event created successfully",
  "data": {
    "id": 100,
    "title": "Team Meeting",
    "description": "Weekly team meeting",
    "start_time": "2025-02-20 14:00:00",
    "end_time": "2025-02-20 15:00:00",
    "event_type": "event",
    "task_id": null,
    "status": "scheduled",
    "priority": "medium",
    "creator": {
      "id": 10,
      "type": "lecturer"
    }
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to create event: {error_message}"
}
```

---

### 10. **PUT** `/api/v1/lecturer-calendar/events/{eventId}`

Cập nhật calendar event

**URL Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `eventId` | integer | **Yes** | ID của event cần cập nhật |

**Request Body:**
```json
{
  "title": "Updated Team Meeting",
  "description": "Updated description",
  "start_time": "2025-02-20 15:00:00",
  "end_time": "2025-02-20 16:00:00"
}
```

**Request Body Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | No | Tiêu đề event |
| `description` | string | No | Mô tả event |
| `start_time` | string | No | Thời gian bắt đầu (format: `Y-m-d H:i:s`) |
| `end_time` | string | No | Thời gian kết thúc (format: `Y-m-d H:i:s`) |
| `event_type` | string | No | Loại event |

**Example Request:**
```bash
PUT /api/v1/lecturer-calendar/events/100
Content-Type: application/json

{
  "title": "Updated Team Meeting",
  "description": "Updated description",
  "start_time": "2025-02-20 15:00:00",
  "end_time": "2025-02-20 16:00:00"
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Event updated successfully",
  "data": {
    "id": 100,
    "title": "Updated Team Meeting",
    "description": "Updated description",
    "start_time": "2025-02-20 15:00:00",
    "end_time": "2025-02-20 16:00:00",
    "event_type": "event",
    "status": "scheduled",
    "priority": "medium"
  }
}
```

**Error Responses:**
- `403 Forbidden` - Không có quyền cập nhật event này
- `404 Not Found` - Event không tồn tại
- `500 Internal Server Error` - Lỗi server

```json
{
  "success": false,
  "message": "Failed to update event: {error_message}"
}
```

---

### 11. **DELETE** `/api/v1/lecturer-calendar/events/{eventId}`

Xóa calendar event

**URL Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `eventId` | integer | **Yes** | ID của event cần xóa |

**Example Request:**
```
DELETE /api/v1/lecturer-calendar/events/100
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Event deleted successfully"
}
```

**Error Responses:**
- `403 Forbidden` - Không có quyền xóa event này
- `404 Not Found` - Event không tồn tại
- `500 Internal Server Error` - Lỗi server

```json
{
  "success": false,
  "message": "Failed to delete event: {error_message}"
}
```

---

## 👨‍🎓 STUDENT CALENDAR APIs

### Base URL: `/api/v1/student-calendar`

**Middleware:** `jwt`, `student`

**Lưu ý:** Student chỉ xem:
- Tasks được assign cho họ (có trong `receivers` với `receiver_id = student_id AND receiver_type = 'student'`)

---

### 1. **GET** `/api/v1/student-calendar/events`

Lấy danh sách events của student (có pagination và filters)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Số trang |
| `per_page` | integer | No | 15 | Số items per page |
| `status` | string | No | - | Filter theo status: `pending`, `in_progress`, `completed`, `overdue` |
| `priority` | string | No | - | Filter theo priority: `low`, `medium`, `high`, `urgent` |
| `date_from` | string | No | - | Ngày bắt đầu (format: `Y-m-d`) |
| `date_to` | string | No | - | Ngày kết thúc (format: `Y-m-d`) |
| `search` | string | No | - | Tìm kiếm trong title/description |

**Example Request:**
```
GET /api/v1/student-calendar/events?page=1&per_page=20&status=pending
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Student events retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "description": "Complete the assignment",
      "start": "2025-02-15 23:59:59",
      "end": "2025-02-15 23:59:59",
      "start_time": "2025-02-15 23:59:59",
      "end_time": "2025-02-15 23:59:59",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high",
      "class_id": 5,
      "creator": {
        "id": 10,
        "type": "lecturer"
      },
      "receivers": [
        {
          "id": 20,
          "type": "student"
        }
      ],
      "files_count": 2,
      "submissions_count": 5
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 30,
    "last_page": 2,
    "from": 1,
    "to": 20
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve student events: {error_message}"
}
```

---

### 2. **GET** `/api/v1/student-calendar/events/by-date`

Lấy events theo ngày cụ thể

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `date` | string | **Yes** | - | Ngày cần lấy (format: `Y-m-d`, ví dụ: `2025-02-15`) |

**Example Request:**
```
GET /api/v1/student-calendar/events/by-date?date=2025-02-15
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events by date retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "start": "2025-02-15 23:59:59",
      "end": "2025-02-15 23:59:59",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high"
    }
  ]
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve events by date: {error_message}"
}
```

---

### 3. **GET** `/api/v1/student-calendar/events/by-range`

Lấy events theo khoảng thời gian

**⚠️ LƯU Ý:** Student endpoint sử dụng `start_date` và `end_date` (khác với Lecturer dùng `start` và `end`)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `start_date` | string | **Yes** | - | Ngày/giờ bắt đầu (format: `Y-m-d` hoặc `Y-m-d H:i:s`) |
| `end_date` | string | **Yes** | - | Ngày/giờ kết thúc (format: `Y-m-d` hoặc `Y-m-d H:i:s`) |

**Example Request:**
```
GET /api/v1/student-calendar/events/by-range?start_date=2025-02-01&end_date=2025-02-28
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events by range retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "start": "2025-02-15 23:59:59",
      "end": "2025-02-15 23:59:59",
      "event_type": "task",
      "task_id": 1
    }
  ]
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve events by range: {error_message}"
}
```

---

### 4. **GET** `/api/v1/student-calendar/events/upcoming`

Lấy events sắp tới (trong 30 ngày)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | No | 10 | Số lượng events tối đa |

**Example Request:**
```
GET /api/v1/student-calendar/events/upcoming?limit=20
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Upcoming events retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "start": "2025-02-20 23:59:59",
      "end": "2025-02-20 23:59:59",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high"
    }
  ]
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve upcoming events: {error_message}"
}
```

---

### 5. **GET** `/api/v1/student-calendar/events/overdue`

Lấy events quá hạn

**Query Parameters:** Không có

**Example Request:**
```
GET /api/v1/student-calendar/events/overdue
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Overdue events retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Assignment 1",
      "start": "2025-01-15 23:59:59",
      "end": "2025-01-15 23:59:59",
      "event_type": "task",
      "task_id": 1,
      "status": "pending",
      "priority": "high"
    }
  ]
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve overdue events: {error_message}"
}
```

---

### 6. **GET** `/api/v1/student-calendar/events/count-by-status`

Đếm events theo trạng thái

**Query Parameters:** Không có

**Example Request:**
```
GET /api/v1/student-calendar/events/count-by-status
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Events count by status retrieved successfully",
  "data": {
    "total": 30,
    "pending": 15,
    "in_progress": 5,
    "completed": 8,
    "overdue": 2,
    "upcoming": 18
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve events count by status: {error_message}"
}
```

---

### 7. **GET** `/api/v1/student-calendar/reminders`

Lấy reminders của student (có pagination)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Số trang |
| `per_page` | integer | No | 15 | Số items per page |

**Example Request:**
```
GET /api/v1/student-calendar/reminders
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Student reminders retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Reminder 1",
      "remind_at": "2025-02-14 09:00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 5,
    "last_page": 1
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to retrieve student reminders: {error_message}"
}
```

---

### 8. **POST** `/api/v1/student-calendar/setReminder`

Tạo reminder mới

**⚠️ LƯU Ý:** Student endpoint là `setReminder` (khác với Lecturer là `reminders`)

**Request Body:**
```json
{
  "title": "Reminder for Assignment 1",
  "remind_at": "2025-02-14 09:00:00",
  "task_id": 1
}
```

**Request Body Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | **Yes** | Tiêu đề reminder |
| `remind_at` | string | **Yes** | Thời gian nhắc (format: `Y-m-d H:i:s`) |
| `task_id` | integer | No | ID của task liên quan |

**Example Request:**
```bash
POST /api/v1/student-calendar/setReminder
Content-Type: application/json

{
  "title": "Reminder for Assignment 1",
  "remind_at": "2025-02-14 09:00:00",
  "task_id": 1
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Reminder set successfully",
  "data": {
    "id": 100,
    "title": "Reminder for Assignment 1",
    "remind_at": "2025-02-14 09:00:00",
    "user_id": 20,
    "user_type": "student",
    "created_at": "2025-01-20 10:00:00"
  }
}
```

**Error Response:** `500 Internal Server Error`
```json
{
  "success": false,
  "message": "Failed to set reminder: {error_message}"
}
```

---

## 📊 Tổng Hợp Endpoints

### Admin (10 endpoints)
1. `GET /api/v1/calendar/events` - Lấy tất cả events (có pagination)
2. `GET /api/v1/calendar/events/by-date` - Lấy events theo ngày
3. `GET /api/v1/calendar/events/by-range` - Lấy events theo khoảng thời gian
4. `GET /api/v1/calendar/events/upcoming` - Lấy events sắp tới
5. `GET /api/v1/calendar/events/overdue` - Lấy events quá hạn
6. `GET /api/v1/calendar/events/count-by-status` - Đếm events theo status
7. `GET /api/v1/calendar/events/by-type` - Lấy events theo type
8. `GET /api/v1/calendar/events/recurring` - Lấy recurring events
9. `GET /api/v1/calendar/reminders` - Lấy reminders
10. `POST /api/v1/calendar/reminders` - Tạo reminder

### Lecturer (11 endpoints)
1. `GET /api/v1/lecturer-calendar/events` - Lấy events (với filters)
2. `GET /api/v1/lecturer-calendar/events/by-date` - Lấy events theo ngày
3. `GET /api/v1/lecturer-calendar/events/by-range` - Lấy events theo khoảng
4. `GET /api/v1/lecturer-calendar/events/upcoming` - Lấy events sắp tới
5. `GET /api/v1/lecturer-calendar/events/overdue` - Lấy events quá hạn
6. `GET /api/v1/lecturer-calendar/events/count-by-status` - Đếm events theo status
7. `GET /api/v1/lecturer-calendar/reminders` - Lấy reminders
8. `POST /api/v1/lecturer-calendar/reminders` - Tạo reminder
9. `POST /api/v1/lecturer-calendar/events` - Tạo event
10. `PUT /api/v1/lecturer-calendar/events/{eventId}` - Cập nhật event
11. `DELETE /api/v1/lecturer-calendar/events/{eventId}` - Xóa event

### Student (8 endpoints)
1. `GET /api/v1/student-calendar/events` - Lấy events (với filters)
2. `GET /api/v1/student-calendar/events/by-date` - Lấy events theo ngày
3. `GET /api/v1/student-calendar/events/by-range` - Lấy events theo khoảng
4. `GET /api/v1/student-calendar/events/upcoming` - Lấy events sắp tới
5. `GET /api/v1/student-calendar/events/overdue` - Lấy events quá hạn
6. `GET /api/v1/student-calendar/events/count-by-status` - Đếm events theo status
7. `GET /api/v1/student-calendar/reminders` - Lấy reminders
8. `POST /api/v1/student-calendar/setReminder` - Tạo reminder

**Tổng cộng: 29 endpoints**

---

## 🔑 Điểm Quan Trọng

### 1. **Date Format**
- **Query Parameters**: `Y-m-d` format (e.g., `2025-02-15`)
- **Request Body**: `Y-m-d H:i:s` format (e.g., `2025-02-15 14:30:00`)
- **Response**: ISO datetime strings hoặc `Y-m-d H:i:s` format

### 2. **Query Parameters Khác Nhau**

**Lecturer:**
- `by-range`: `start` và `end`
- Reminder: `POST /reminders`

**Student:**
- `by-range`: `start_date` và `end_date` ⚠️
- Reminder: `POST /setReminder` ⚠️

### 3. **Authentication**
Tất cả endpoints yêu cầu JWT token:
```
Authorization: Bearer {your_jwt_token}
```

### 4. **Error Handling**
Tất cả endpoints trả về format:
```json
{
  "success": false,
  "message": "Error message here"
}
```

Status codes:
- `200 OK` - Success
- `201 Created` - Created successfully
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - No permission
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server error

### 5. **Pagination**
Khi có pagination, response sẽ có thêm:
```json
{
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

## 📝 Notes

1. **Lecturer** có thể tạo/sửa/xóa calendar events (không phải tasks)
2. **Student** chỉ có thể xem events và tạo reminders
3. **Admin** có thể xem tất cả events trong hệ thống
4. Tất cả user IDs được lấy tự động từ JWT token, không cần truyền trong request

---

**Last Updated:** 2025-01-20  
**Version:** 2.0.0  
**Documentation Status:** ✅ Complete


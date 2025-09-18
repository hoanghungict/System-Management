# Single Event Publishing API Usage Guide

## Tổng quan

API duy nhất để publish tất cả các business events. Business logic được xử lý trong Handler tương ứng.

## API Endpoint

**Endpoint:** `POST /api/v1/events/publish`

**Mô tả:** Publish event lên Kafka, Handler sẽ xử lý business logic

## Request Body

```json
{
    "event_type": "task.assigned",
    "data": {
        "user_id": 123,
        "user_type": "student",
        "user_name": "Nguyễn Văn A",
        "task_name": "Làm bài tập Laravel",
        "task_description": "Viết API cho module Notifications",
        "assigner_name": "Thầy Nguyễn Văn B",
        "assigner_id": 456,
        "assigner_type": "lecturer",
        "deadline": "2024-01-20 23:59:00",
        "task_url": "https://system.com/tasks/123"
    },
    "priority": "medium",
    "key": "task_123_20240115"
}
```

## Response

```json
{
    "success": true,
    "message": "Event published successfully",
    "data": {
        "event_type": "task.assigned",
        "event_id": "task_123_20240115",
        "timestamp": "2024-01-15T10:30:00.000000Z"
    }
}
```

## Các Event Types được hỗ trợ

### 1. Task Events

```json
{
    "event_type": "task.assigned",
    "data": {
        "user_id": 123,
        "user_type": "student",
        "user_name": "Nguyễn Văn A",
        "task_name": "Làm bài tập Laravel",
        "task_description": "Viết API cho module Notifications",
        "assigner_name": "Thầy Nguyễn Văn B",
        "assigner_id": 456,
        "assigner_type": "lecturer",
        "deadline": "2024-01-20 23:59:00",
        "task_url": "https://system.com/tasks/123"
    }
}
```

```json
{
    "event_type": "task.completed",
    "data": {
        "task_id": "task_123",
        "user_id": 123,
        "completion_notes": "Hoàn thành đúng hạn",
        "completion_time": "2024-01-15T10:30:00Z"
    }
}
```

```json
{
    "event_type": "task.updated",
    "data": {
        "task_id": "task_123",
        "user_id": 123,
        "updates": {
            "deadline": "2024-01-25 23:59:00",
            "priority": "high"
        },
        "updated_by": "Thầy Nguyễn Văn B"
    }
}
```

### 2. User Events

```json
{
    "event_type": "student_account_created",
    "data": {
        "user_id": 123,
        "name": "Nguyễn Văn A",
        "email": "nguyenvana@example.com",
        "username": "nguyenvana",
        "password": "hashed_password"
    }
}
```

```json
{
    "event_type": "lecturer_account_created",
    "data": {
        "user_id": 456,
        "name": "Thầy Nguyễn Văn B",
        "email": "nguyenvanb@example.com",
        "username": "nguyenvanb",
        "password": "hashed_password"
    }
}
```

```json
{
    "event_type": "user.password_reset",
    "data": {
        "user_id": 123,
        "user_type": "student",
        "email": "nguyenvana@example.com",
        "reset_code": "ABC12345"
    }
}
```

### 3. System Events

```json
{
    "event_type": "system.maintenance",
    "data": {
        "start_time": "2024-01-20 02:00:00",
        "end_time": "2024-01-20 06:00:00",
        "reason": "Scheduled maintenance",
        "affected_services": ["notifications", "auth", "tasks"]
    }
}
```

## Cách sử dụng

### 1. Test với cURL

```bash
# Giao việc
curl -X POST http://localhost:8000/api/v1/events/publish \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "task.assigned",
    "data": {
      "user_id": 123,
      "user_type": "student",
      "user_name": "Nguyễn Văn A",
      "task_name": "Làm bài tập Laravel",
      "assigner_name": "Thầy Nguyễn Văn B"
    },
    "priority": "medium"
  }'

# Đăng ký user mới
curl -X POST http://localhost:8000/api/v1/events/publish \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "student_account_created",
    "data": {
      "user_id": 123,
      "name": "Nguyễn Văn A",
      "email": "nguyenvana@example.com",
      "username": "nguyenvana"
    }
  }'

# Bảo trì hệ thống
curl -X POST http://localhost:8000/api/v1/events/publish \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "system.maintenance",
    "data": {
      "start_time": "2024-01-20 02:00:00",
      "end_time": "2024-01-20 06:00:00",
      "reason": "Scheduled maintenance"
    }
  }'
```

### 2. Sử dụng trong code PHP

```php
use Illuminate\Support\Facades\Http;

// Giao việc
$response = Http::post('http://notifications-service/api/v1/events/publish', [
    'event_type' => 'task.assigned',
    'data' => [
        'user_id' => 123,
        'user_type' => 'student',
        'user_name' => 'Nguyễn Văn A',
        'task_name' => 'Làm bài tập Laravel',
        'assigner_name' => 'Thầy Nguyễn Văn B'
    ],
    'priority' => 'medium'
]);

// Đăng ký user
$response = Http::post('http://notifications-service/api/v1/events/publish', [
    'event_type' => 'student_account_created',
    'data' => [
        'user_id' => 123,
        'name' => 'Nguyễn Văn A',
        'email' => 'nguyenvana@example.com',
        'username' => 'nguyenvana'
    ]
]);
```

### 3. Sử dụng trong JavaScript/Node.js

```javascript
const axios = require("axios");

// Giao việc
const assignTask = async () => {
    try {
        const response = await axios.post(
            "http://notifications-service/api/v1/events/publish",
            {
                event_type: "task.assigned",
                data: {
                    user_id: 123,
                    user_type: "student",
                    user_name: "Nguyễn Văn A",
                    task_name: "Làm bài tập Laravel",
                    assigner_name: "Thầy Nguyễn Văn B",
                },
                priority: "medium",
            }
        );

        console.log("Success:", response.data);
    } catch (error) {
        console.error("Error:", error.response.data);
    }
};

// Đăng ký user
const registerUser = async () => {
    try {
        const response = await axios.post(
            "http://notifications-service/api/v1/events/publish",
            {
                event_type: "student_account_created",
                data: {
                    user_id: 123,
                    name: "Nguyễn Văn A",
                    email: "nguyenvana@example.com",
                    username: "nguyenvana",
                },
            }
        );

        console.log("Success:", response.data);
    } catch (error) {
        console.error("Error:", error.response.data);
    }
};
```

## Luồng xử lý

1. **External Service** → Gọi API `/api/v1/events/publish`
2. **API** → Validate request data
3. **API** → Publish event lên Kafka
4. **Kafka Consumer** → Nhận event từ Kafka
5. **Handler** → Xử lý business logic và gửi notification

## Handler Mapping

| Event Type                 | Handler Class                   | Mô tả                    |
| -------------------------- | ------------------------------- | ------------------------ |
| `task.assigned`            | `TaskAssignedHandler`           | Xử lý giao việc          |
| `task.completed`           | `TaskCompletedHandler`          | Xử lý hoàn thành việc    |
| `task.updated`             | `TaskUpdatedHandler`            | Xử lý cập nhật việc      |
| `student_account_created`  | `StudentAccountCreatedHandler`  | Xử lý đăng ký sinh viên  |
| `lecturer_account_created` | `LecturerAccountCreatedHandler` | Xử lý đăng ký giảng viên |
| `user.password_reset`      | `PasswordResetHandler`          | Xử lý reset mật khẩu     |
| `system.maintenance`       | `SystemMaintenanceHandler`      | Xử lý thông báo bảo trì  |

## Error Handling

### Validation Errors (400)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "event_type": ["The event type field is required."],
        "data": ["The data field is required."]
    }
}
```

### Kafka Errors (500)

```json
{
    "success": false,
    "error": "Failed to send message to Kafka: Connection timeout"
}
```

## Lợi ích

### ✅ **Đơn giản**

-   Chỉ 1 API endpoint
-   Dễ sử dụng và maintain

### ✅ **Linh hoạt**

-   Dễ thêm event types mới
-   Handler có thể thay đổi logic độc lập

### ✅ **Scalable**

-   Dễ scale theo từng event type
-   Handler có thể chạy trên service riêng

### ✅ **Maintainable**

-   Business logic tập trung ở Handler
-   API chỉ lo publish data

## Security

-   API endpoint không yêu cầu authentication (public)
-   Có thể thêm rate limiting nếu cần
-   Validate tất cả input data
-   Log tất cả requests để audit

## Monitoring

-   Check logs: `tail -f storage/logs/laravel.log | grep Event`
-   Monitor Kafka topics: `kafka-topics.sh --list --bootstrap-server localhost:9092`
-   Check consumer lag: `kafka-consumer-groups.sh --bootstrap-server localhost:9092 --group notifications-consumer --describe`

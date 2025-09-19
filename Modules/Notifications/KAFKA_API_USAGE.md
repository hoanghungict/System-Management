# Kafka Producer API Usage Guide

## Tổng quan

API Kafka Producer cho phép các service bên ngoài gửi message lên Kafka topics thông qua HTTP requests.

## API Endpoints

### 1. Generic Kafka Producer

**Endpoint:** `POST /api/v1/kafka/produce`

**Mô tả:** Gửi message JSON lên bất kỳ Kafka topic nào

**Request Body:**

```json
{
    "topic": "your.topic.name",
    "data": {
        "key1": "value1",
        "key2": "value2"
    },
    "key": "optional-partition-key",
    "headers": {
        "header1": "value1"
    }
}
```

**Response:**

```json
{
    "success": true,
    "message": "Message sent to Kafka successfully",
    "topic": "your.topic.name"
}
```

### 2. Task Assignment Producer

**Endpoint:** `POST /api/v1/kafka/produce/task-assigned`

**Mô tả:** Gửi message task assignment lên topic `task.assigned`

**Request Body:**

```json
{
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
```

**Response:**

```json
{
    "success": true,
    "message": "Task assignment message sent to Kafka successfully",
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
        "task_url": "https://system.com/tasks/123",
        "timestamp": "2024-01-15T10:30:00.000000Z"
    }
}
```

## Cách sử dụng

### 1. Test với cURL

```bash
# Test generic producer
curl -X POST http://localhost:8000/api/v1/kafka/produce \
  -H "Content-Type: application/json" \
  -d '{
    "topic": "test.topic",
    "data": {
      "message": "Hello Kafka!",
      "timestamp": "2024-01-15T10:30:00Z"
    },
    "key": "test-key"
  }'

# Test task assignment producer
curl -X POST http://localhost:8000/api/v1/kafka/produce/task-assigned \
  -H "Content-Type: application/json" \
  -d '{
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
  }'
```

### 2. Sử dụng trong code PHP

```php
// Sử dụng HTTP client
use Illuminate\Support\Facades\Http;

$response = Http::post('http://notifications-service/api/v1/kafka/produce/task-assigned', [
    'user_id' => 123,
    'user_type' => 'student',
    'user_name' => 'Nguyễn Văn A',
    'task_name' => 'Làm bài tập Laravel',
    'task_description' => 'Viết API cho module Notifications',
    'assigner_name' => 'Thầy Nguyễn Văn B',
    'assigner_id' => 456,
    'assigner_type' => 'lecturer',
    'deadline' => '2024-01-20 23:59:00',
    'task_url' => 'https://system.com/tasks/123'
]);

if ($response->successful()) {
    $result = $response->json();
    echo "Message sent: " . $result['message'];
} else {
    echo "Error: " . $response->body();
}
```

### 3. Sử dụng trong JavaScript/Node.js

```javascript
const axios = require("axios");

const sendTaskAssignment = async () => {
    try {
        const response = await axios.post(
            "http://notifications-service/api/v1/kafka/produce/task-assigned",
            {
                user_id: 123,
                user_type: "student",
                user_name: "Nguyễn Văn A",
                task_name: "Làm bài tập Laravel",
                task_description: "Viết API cho module Notifications",
                assigner_name: "Thầy Nguyễn Văn B",
                assigner_id: 456,
                assigner_type: "lecturer",
                deadline: "2024-01-20 23:59:00",
                task_url: "https://system.com/tasks/123",
            }
        );

        console.log("Success:", response.data);
    } catch (error) {
        console.error("Error:", error.response.data);
    }
};

sendTaskAssignment();
```

## Luồng xử lý

1. **Service bên ngoài** → Gọi API endpoint
2. **NotificationsController** → Validate request data
3. **KafkaProducerService** → Gửi message lên Kafka
4. **KafkaConsumerService** → Nhận message từ Kafka
5. **TaskAssignedHandler** → Xử lý message và gửi notification

## Error Handling

### Validation Errors (400)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "user_id": ["The user id field is required."],
        "user_type": ["The selected user type is invalid."]
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

## Security

-   API endpoints không yêu cầu authentication (public)
-   Có thể thêm rate limiting nếu cần
-   Validate tất cả input data
-   Log tất cả requests để audit

## Monitoring

-   Check logs: `tail -f storage/logs/laravel.log | grep Kafka`
-   Monitor Kafka topics: `kafka-topics.sh --list --bootstrap-server localhost:9092`
-   Check consumer lag: `kafka-consumer-groups.sh --bootstrap-server localhost:9092 --group notifications-consumer --describe`

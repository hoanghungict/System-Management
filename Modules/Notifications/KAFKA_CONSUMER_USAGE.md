# Kafka Consumer Usage Guide

## Tổng quan

KafkaConsumer là service để nhận và xử lý các event từ Kafka message broker. Service này được tích hợp với hệ thống notification để tự động gửi thông báo khi có event mới.

## Cấu hình

### 1. Cấu hình Kafka (config/kafka.php)

```php
return [
    'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
    'group_id' => env('KAFKA_GROUP_ID', 'notifications-consumer'),
    'topics' => [
        'user_registered' => 'user.registered',
        'task_assigned' => 'task.assigned',
    ],
];
```

### 2. Cấu hình Event Handlers (config/events.php)

```php
return [
    'handlers' => [
        'task.assigned' => Modules\Notifications\app\Handlers\TaskAssignedHandler::class,
        'user.registered' => Modules\Notifications\app\Handlers\UserRegisteredHandler::class,
    ],
];
```

## Sử dụng

### 1. Chạy Consumer qua Artisan Command

```bash
php artisan kafka:consume
```

### 2. Chạy Consumer trực tiếp

```php
use Modules\Notifications\app\Services\KafkaService\KafkaConsumer;

$consumer = new KafkaConsumer();
$consumer->handle();
```

### 3. Test Consumer

```bash
cd Modules/Notifications
php test_kafka_consumer.php
```

## Cách hoạt động

1. **Khởi tạo**: Consumer kết nối đến Kafka broker và subscribe các topics được cấu hình
2. **Nhận message**: Khi có message mới, consumer sẽ decode JSON payload
3. **Tìm handler**: Dựa vào topic name, tìm handler tương ứng (hỗ trợ wildcard pattern)
4. **Xử lý event**: Gọi method `handle()` của handler để xử lý event
5. **Gửi notification**: Handler sẽ tạo và gửi notification thông qua hệ thống notification

## Event Handler Pattern

Tất cả event handlers phải implement interface `NotificationEventHandler`:

```php
<?php

namespace Modules\Notifications\app\Handlers;

use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;

class TaskAssignedHandler implements NotificationEventHandler
{
    public function handle(string $channel, array $data): void
    {
        // Xử lý event task.assigned
        // $channel = 'task.assigned'
        // $data = payload từ Kafka message

        // Ví dụ: gửi notification
        broadcast(new UserNotificationPushed(
            $data['user_id'],
            $data['user_type'] ?? 'user',
            "Bạn vừa được giao công việc: {$data['task_name']}",
            $data
        ));
    }
}
```

## Message Format

Kafka message phải có format JSON:

```json
{
    "user_id": 123,
    "user_type": "student",
    "task_name": "Làm bài tập Laravel",
    "due_date": "2024-01-15",
    "additional_data": "..."
}
```

## Logging

Consumer sẽ log tất cả hoạt động:

-   **INFO**: Consumer start/stop, message received, handler executed
-   **WARNING**: Invalid payload, no handler found
-   **ERROR**: Connection errors, handler execution errors

Xem logs tại: `storage/logs/laravel.log`

## Error Handling

-   **Connection errors**: Consumer sẽ throw exception và dừng
-   **Message decode errors**: Log warning và skip message
-   **Handler errors**: Log error nhưng tiếp tục xử lý message khác
-   **Graceful shutdown**: Hỗ trợ SIGTERM và SIGINT signals

## Monitoring

Để monitor consumer:

1. Check logs: `tail -f storage/logs/laravel.log | grep Kafka`
2. Check consumer group: `kafka-consumer-groups.sh --bootstrap-server localhost:9092 --list`
3. Check topic lag: `kafka-consumer-groups.sh --bootstrap-server localhost:9092 --group notifications-consumer --describe`

## Troubleshooting

### Consumer không nhận được message

-   Kiểm tra Kafka broker có chạy không
-   Kiểm tra topic có tồn tại không
-   Kiểm tra consumer group configuration

### Handler không được gọi

-   Kiểm tra topic name có match với pattern trong config không
-   Kiểm tra handler class có tồn tại không
-   Kiểm tra handler có implement đúng interface không

### Message decode lỗi

-   Kiểm tra message format có đúng JSON không
-   Kiểm tra payload structure

## Production Deployment

1. **Supervisor**: Sử dụng supervisor để auto-restart consumer
2. **Multiple instances**: Có thể chạy nhiều consumer instances để scale
3. **Health checks**: Implement health check endpoint
4. **Metrics**: Collect metrics về message processing rate, error rate

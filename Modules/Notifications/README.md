# Module Notifications

Module thông báo đa kênh cho hệ thống quản lý giáo dục, hỗ trợ email, push notification, SMS và in-app notification.

## 🚀 Tính Năng

-   **Đa kênh thông báo**: Email, Push, SMS, In-app
-   **Template system**: Hỗ trợ template động với biến
-   **Queue processing**: Xử lý bất đồng bộ qua Redis
-   **Real-time**: Push notification qua Redis pub/sub
-   **Scheduling**: Lên lịch gửi thông báo
-   **Bulk sending**: Gửi hàng loạt
-   **Microservice ready**: API cho external services
-   **Multi-user types**: Hỗ trợ student, lecturer, admin

## 🏗️ Kiến Trúc

```
Controller → Service → Repository → Model
```

### **Layers:**

-   **Controller**: Xử lý HTTP requests, validation
-   **Service**: Business logic, orchestration
-   **Repository**: Data access, database operations
-   **Model**: Eloquent models với relationships

## 📁 Cấu Trúc Thư Mục

```
Modules/Notifications/
├── app/
│   ├── Http/Controllers/
│   │   └── NotificationsController.php
│   ├── Models/
│   │   ├── Notification.php
│   │   ├── NotificationTemplate.php
│   │   └── UserNotification.php
│   ├── Repositories/
│   │   ├── Interfaces/
│   │   │   └── NotificationRepositoryInterface.php
│   │   └── NotificationRepository/
│   │       └── NotificationRepository.php
│   ├── Services/
│   │   ├── NotificationService/
│   │   │   └── NotificationService.php
│   │   ├── EmailService/
│   │   │   └── EmailService.php
│   │   ├── PushService/
│   │   │   └── PushService.php
│   │   └── SmsService/
│   │       └── SmsService.php
│   └── Jobs/
│       └── SendEmailNotificationJob.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── README.md
```

## 🗄️ Database Schema

### **notification_templates**

-   `name`: Tên template (unique)
-   `title`: Tiêu đề thông báo
-   `subject`: Subject email
-   `email_template`: Template email
-   `sms_template`: Template SMS
-   `push_template`: Template push
-   `in_app_template`: Template in-app
-   `channels`: JSON array các kênh hỗ trợ
-   `priority`: Độ ưu tiên (low, medium, high, critical)
-   `category`: Danh mục (task, library, system, user)
-   `is_active`: Trạng thái hoạt động

### **notifications**

-   `title`: Tiêu đề
-   `content`: Nội dung
-   `type`: Loại thông báo
-   `priority`: Độ ưu tiên
-   `data`: JSON data động
-   `template_id`: ID template
-   `sender_id`: ID người gửi
-   `sender_type`: Loại người gửi
-   `scheduled_at`: Thời gian lên lịch
-   `status`: Trạng thái (pending, processing, sent, failed)

### **user_notifications**

-   `user_id`: ID user
-   `user_type`: Loại user (student, lecturer, admin)
-   `notification_id`: ID notification
-   `is_read`: Đã đọc chưa
-   `email_sent`: Email đã gửi
-   `push_sent`: Push đã gửi
-   `sms_sent`: SMS đã gửi

## 🔌 API Endpoints

### **Public API (External Services)**

```
POST /api/v1/notifications/send          # Gửi thông báo đơn lẻ
POST /api/v1/notifications/send-bulk     # Gửi thông báo hàng loạt
POST /api/v1/notifications/schedule      # Lên lịch gửi
GET  /api/v1/notifications/templates     # Lấy danh sách templates
GET  /api/v1/notifications/status/{id}   # Lấy trạng thái
```

### **Internal API (Authenticated)**

```
GET  /api/v1/internal/notifications/user     # Lấy thông báo của user
POST /api/v1/internal/notifications/mark-read # Đánh dấu đã đọc
```

### **Health Check**

```
GET /health
```

## 📧 Sử Dụng

### **1. Gửi thông báo đơn lẻ**

```php
// Trong service khác
$notificationService = app(NotificationService::class);

$result = $notificationService->sendNotification(
    'task_assigned',                    // Template name
    [                                   // Recipients
        [
            'user_id' => 123,
            'user_type' => 'student',
            'channels' => ['email', 'push']
        ]
    ],
    [                                   // Dynamic data
        'user_name' => 'Nguyễn Văn A',
        'task_title' => 'Làm bài tập',
        'deadline' => '2024-01-20'
    ]
);
```

### **2. Gửi thông báo hàng loạt**

```php
$recipients = [
    ['user_id' => 1, 'user_type' => 'student'],
    ['user_id' => 2, 'user_type' => 'student'],
    ['user_id' => 3, 'user_type' => 'lecturer']
];

$result = $notificationService->sendBulkNotification(
    'system_maintenance',
    $recipients,
    [
        'start_time' => '2024-01-20 22:00',
        'end_time' => '2024-01-21 06:00'
    ]
);
```

### **3. Lên lịch gửi**

```php
$scheduledAt = new DateTime('2024-01-20 09:00:00');

$result = $notificationService->scheduleNotification(
    'task_reminder',
    [['user_id' => 123, 'user_type' => 'student']],
    ['task_title' => 'Bài tập cuối kỳ', 'deadline' => '2024-01-25'],
    $scheduledAt
);
```

## 🔧 Cài Đặt

### **1. Chạy migrations**

```bash
php artisan module:migrate Notifications
```

### **2. Chạy seeders**

```bash
php artisan module:seed Notifications
```

### **3. Cấu hình queue (Redis)**

```bash
# Trong .env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Chạy queue worker
php artisan queue:work --queue=emails,default
```

## 📱 Template Variables

### **Task Templates**

-   `{{user_name}}`: Tên user
-   `{{task_title}}`: Tiêu đề công việc
-   `{{deadline}}`: Hạn hoàn thành
-   `{{completed_by}}`: Người hoàn thành

### **Library Templates**

-   `{{book_title}}`: Tên sách
-   `{{return_date}}`: Ngày trả

### **System Templates**

-   `{{start_time}}`: Thời gian bắt đầu
-   `{{end_time}}`: Thời gian kết thúc
-   `{{version}}`: Phiên bản
-   `{{new_features}}`: Tính năng mới

### **User Templates**

-   `{{reset_code}}`: Mã đặt lại mật khẩu

## 🚀 Queue Processing

### **Email Queue**

```bash
# Chạy worker cho email
php artisan queue:work --queue=emails

# Hoặc chạy tất cả queues
php artisan queue:work
```

### **Failed Jobs**

```bash
# Xem failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Clear failed jobs
php artisan queue:flush
```

## 📊 Monitoring

### **Queue Status**

```bash
# Kiểm tra queue status
php artisan queue:monitor

# Xem queue sizes
php artisan queue:size
```

### **Logs**

```bash
# Xem notification logs
tail -f storage/logs/laravel.log | grep "Notification"
```

## 🛡️ Error Handling

### **Retry Logic**

-   Email jobs: 3 lần retry
-   Timeout: 60 giây
-   Failed jobs được log và có thể retry manual

### **Fallback**

-   Nếu email fail → Log error
-   Nếu push fail → Log error
-   Nếu SMS fail → Log error
-   In-app notification luôn được tạo

## 🔒 Security

### **API Security**

-   Public endpoints: Rate limiting
-   Internal endpoints: JWT authentication
-   Input validation: Tất cả inputs được validate
-   SQL injection: Sử dụng Eloquent ORM

### **Data Privacy**

-   User data được hash
-   Sensitive data không log
-   Audit trail cho tất cả operations

## ⚡ Performance

### **Optimizations**

-   Database indexing trên các trường quan trọng
-   Queue processing cho email/SMS
-   Redis pub/sub cho real-time
-   Chunk processing cho bulk operations

### **Scaling**

-   Horizontal scaling với multiple queue workers
-   Redis clustering cho high availability
-   Database connection pooling

## 🔄 Integration

### **External Services**

```php
// Gọi từ service khác
$response = Http::post('http://notifications-service/api/v1/notifications/send', [
    'template' => 'task_assigned',
    'recipients' => [...],
    'data' => [...]
]);
```

### **Event-Driven**

```php
// Trong Event Listener
event(new TaskAssigned($task, $assignee));

// Trong NotificationService
public function handleTaskAssigned(TaskAssigned $event)
{
    $this->sendNotification('task_assigned', [
        ['user_id' => $event->assignee->id, 'user_type' => 'student']
    ], [
        'user_name' => $event->assignee->name,
        'task_title' => $event->task->title,
        'deadline' => $event->task->deadline
    ]);
}
```

## 🧪 Testing

### **Unit Tests**

```bash
# Chạy tests
php artisan test --filter=Notifications

# Chạy specific test
php artisan test --filter=NotificationServiceTest
```

### **Integration Tests**

```bash
# Test API endpoints
php artisan test --filter=NotificationsApiTest
```

## 📝 Changelog

### **v1.0.0**

-   ✅ Basic notification system
-   ✅ Multi-channel support
-   ✅ Template system
-   ✅ Queue processing
-   ✅ API endpoints
-   ✅ Database schema
-   ✅ Basic documentation

## 🤝 Contributing

1. Fork project
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 License

MIT License - see LICENSE file for details

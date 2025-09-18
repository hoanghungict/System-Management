# 🚀 Email System - Task Module

## 📋 Tổng quan

Hệ thống email cho module Task được xây dựng tuân theo **Clean Architecture** với các layer rõ ràng và separation of concerns. Hệ thống hỗ trợ gửi email báo cáo, thông báo, template và hàng loạt với đầy đủ tính năng monitoring và logging.

## 🏗️ Kiến trúc

### **Clean Architecture Layers**

```
┌─────────────────────────────────────┐
│           Presentation Layer         │
│  ┌─────────────────────────────────┐ │
│  │      EmailController            │ │
│  │  (HTTP Requests/Responses)      │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────┐
│         Business Logic Layer         │
│  ┌─────────────────────────────────┐ │
│  │      EmailService               │ │
│  │  (Business Rules & Logic)       │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────┐
│         Data Access Layer            │
│  ┌─────────────────────────────────┐ │
│  │   EmailRepository               │ │
│  │  (Data Access Operations)       │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────┐
│         Infrastructure Layer        │
│  ┌─────────────────────────────────┐ │
│  │      Laravel Mail              │ │
│  │  (External Infrastructure)     │ │
│  └─────────────────────────────────┘ │
└─────────────────────────────────────┘
```

## 📁 Cấu trúc thư mục

```
Modules/Task/
├── app/
│   ├── DTOs/
│   │   └── EmailReportDTO.php           # Data Transfer Object
│   ├── Events/
│   │   ├── EmailSentEvent.php           # Email sent event
│   │   └── EmailFailedEvent.php         # Email failed event
│   ├── Http/
│   │   └── Controllers/
│   │       └── Email/
│   │           └── EmailController.php   # Presentation layer
│   ├── Jobs/
│   │   └── SendEmailJob.php             # Background job
│   ├── Listeners/
│   │   └── EmailEventListener.php      # Event listener
│   ├── Repositories/
│   │   ├── Interfaces/
│   │   │   └── EmailRepositoryInterface.php
│   │   └── EmailRepository.php          # Data access layer
│   ├── Services/
│   │   ├── Interfaces/
│   │   │   └── EmailServiceInterface.php
│   │   └── EmailService.php             # Business logic layer
│   └── Providers/
│       └── EmailServiceProvider.php     # Service provider
├── config/
│   └── email.php                        # Configuration
└── README_EMAIL.md                      # Documentation
```

## 🔧 Cài đặt và Cấu hình

### 1. Environment Variables

Thêm các biến môi trường vào file `.env`:

```env
# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Task Management System"

# Task Module Email Settings
EMAIL_MAX_RECIPIENTS=50
EMAIL_MAX_ATTACHMENTS=10
EMAIL_MAX_ATTACHMENT_SIZE=10485760
EMAIL_RETRY_ATTEMPTS=3
EMAIL_RETRY_DELAY=300
EMAIL_QUEUE_TIMEOUT=120
EMAIL_MONITORING_ENABLED=true
EMAIL_SUCCESS_RATE_THRESHOLD=95
EMAIL_ALERT_ON_FAILURE=true
EMAIL_LOGGING_ENABLED=true
EMAIL_LOG_RETENTION_DAYS=30
EMAIL_QUEUE_NAME=emails
EMAIL_BATCH_SIZE=100
EMAIL_DELAY_BETWEEN_BATCHES=60
```

### 2. Service Provider Registration

Đăng ký `EmailServiceProvider` trong `config/app.php`:

```php
'providers' => [
    // ...
    Modules\Task\app\Providers\EmailServiceProvider::class,
],
```

### 3. Queue Configuration

Cấu hình queue trong `config/queue.php`:

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

## 🚀 Sử dụng

### 1. Gửi Email Báo cáo

```php
use Modules\Task\app\Services\Interfaces\EmailServiceInterface;
use Modules\Task\app\DTOs\EmailReportDTO;

class ReportController
{
    public function sendReport(EmailServiceInterface $emailService)
    {
        $emailDTO = new EmailReportDTO(
            recipients: ['user@example.com', 'admin@example.com'],
            subject: 'Báo cáo hàng ngày',
            content: 'Nội dung báo cáo...',
            reportData: [
                'total_tasks' => 100,
                'completed_tasks' => 85,
                'completion_rate' => 85
            ],
            template: 'emails.reports.daily'
        );

        $sent = $emailService->sendReportEmail($emailDTO);
        
        return response()->json(['success' => $sent]);
    }
}
```

### 2. Gửi Email Thông báo

```php
$emailService->sendNotificationEmail(
    'user@example.com',
    'Task được giao',
    'Bạn đã được giao task mới: Tên task'
);
```

### 3. Gửi Email với Template

```php
$emailService->sendTemplateEmail(
    'user@example.com',
    'emails.notifications.task_created',
    [
        'subject' => 'Task mới được tạo',
        'task_title' => 'Tên task',
        'task_description' => 'Mô tả task'
    ]
);
```

### 4. Gửi Email Hàng loạt

```php
$emailService->sendBulkEmail(
    ['user1@example.com', 'user2@example.com', 'user3@example.com'],
    'Thông báo quan trọng',
    'Nội dung thông báo...'
);
```

## 📡 API Endpoints

### 1. Gửi Email Báo cáo

```http
POST /api/v1/email/send-report
Content-Type: application/json

{
    "recipients": ["user@example.com", "admin@example.com"],
    "subject": "Báo cáo hàng ngày",
    "content": "Nội dung báo cáo...",
    "report_data": {
        "total_tasks": 100,
        "completed_tasks": 85,
        "completion_rate": 85
    },
    "template": "emails.reports.daily",
    "attachments": [
        {
            "path": "/path/to/file.pdf",
            "name": "report.pdf"
        }
    ]
}
```

### 2. Gửi Email Thông báo

```http
POST /api/v1/email/send-notification
Content-Type: application/json

{
    "to": "user@example.com",
    "subject": "Task được giao",
    "content": "Bạn đã được giao task mới",
    "attachments": []
}
```

### 3. Gửi Email với Template

```http
POST /api/v1/email/send-template
Content-Type: application/json

{
    "to": "user@example.com",
    "template": "emails.notifications.task_created",
    "data": {
        "subject": "Task mới được tạo",
        "task_title": "Tên task",
        "task_description": "Mô tả task"
    }
}
```

### 4. Gửi Email Hàng loạt

```http
POST /api/v1/email/send-bulk
Content-Type: application/json

{
    "recipients": ["user1@example.com", "user2@example.com"],
    "subject": "Thông báo quan trọng",
    "content": "Nội dung thông báo..."
}
```

### 5. Kiểm tra Kết nối

```http
GET /api/v1/email/test-connection
```

## 📊 Monitoring và Logging

### 1. Email Metrics

Hệ thống tự động track các metrics:

- **Daily Metrics**: `email_metrics:daily:2024-01-01`
- **Hourly Metrics**: `email_metrics:hourly:2024-01-01-10`
- **Template Metrics**: `email_metrics:template:emails.reports.daily`

### 2. Metrics Fields

```php
[
    'sent_count' => 150,        // Số email gửi thành công
    'failed_count' => 5,        // Số email thất bại
    'total_count' => 155,       // Tổng số email
    'success_rate' => 96.77     // Tỷ lệ thành công (%)
]
```

### 3. Email Logs

Logs được lưu trong bảng `email_logs`:

```sql
CREATE TABLE email_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipients JSON NOT NULL,
    subject VARCHAR(255) NOT NULL,
    template VARCHAR(255),
    success BOOLEAN NOT NULL,
    error TEXT NULL,
    sent_at TIMESTAMP NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 🔄 Event-Driven Architecture

### 1. Email Events

```php
// Email sent successfully
EmailSentEvent::dispatch($emailDTO);

// Email failed
EmailFailedEvent::dispatch($emailDTO, $errorMessage);
```

### 2. Event Listeners

```php
class EmailEventListener
{
    public function handleEmailSent(EmailSentEvent $event)
    {
        // Track metrics
        // Send notifications
        // Log activities
    }

    public function handleEmailFailed(EmailFailedEvent $event)
    {
        // Track failures
        // Send alerts
        // Retry logic
    }
}
```

## 🎯 Background Processing

### 1. Queue Jobs

```php
// Dispatch email job
SendEmailJob::dispatch($emailDTO)
    ->onQueue('emails')
    ->delay(now()->addSeconds(5));
```

### 2. Job Configuration

```php
class SendEmailJob implements ShouldQueue
{
    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 3;
}
```

### 3. Retry Logic

```php
public function retryAfter(\Throwable $exception): void
{
    $this->release(now()->addMinutes(pow(2, $this->attempts())));
}
```

## 🛡️ Security và Validation

### 1. Input Validation

```php
$validator = Validator::make($request->all(), [
    'recipients' => 'required|array|min:1',
    'recipients.*' => 'required|string|email',
    'subject' => 'required|string|max:255',
    'content' => 'required|string',
    'attachments' => 'array',
    'attachments.*.path' => 'string',
    'attachments.*.name' => 'string'
]);
```

### 2. Security Measures

- **Email Validation**: Kiểm tra format email hợp lệ
- **File Validation**: Kiểm tra file attachments
- **Rate Limiting**: Giới hạn số lượng email gửi
- **Size Limits**: Giới hạn kích thước file
- **Recipient Limits**: Giới hạn số người nhận

## 📈 Performance Optimization

### 1. Queue Processing

- **Background Processing**: Gửi email không block main thread
- **Batch Processing**: Xử lý nhiều email cùng lúc
- **Retry Mechanism**: Tự động retry khi thất bại

### 2. Caching

- **Template Caching**: Cache email templates
- **Metrics Caching**: Cache email metrics
- **Connection Pooling**: Tái sử dụng connections

### 3. Monitoring

- **Success Rate Tracking**: Theo dõi tỷ lệ thành công
- **Performance Metrics**: Theo dõi thời gian gửi
- **Error Tracking**: Theo dõi lỗi và exceptions

## 🧪 Testing

### 1. Unit Tests

```php
class EmailServiceTest extends TestCase
{
    public function test_send_report_email()
    {
        $emailService = $this->app->make(EmailServiceInterface::class);
        $emailDTO = new EmailReportDTO(/* ... */);
        
        $result = $emailService->sendReportEmail($emailDTO);
        
        $this->assertTrue($result);
    }
}
```

### 2. Integration Tests

```php
class EmailControllerTest extends TestCase
{
    public function test_send_report_email_endpoint()
    {
        $response = $this->postJson('/api/v1/email/send-report', [
            'recipients' => ['test@example.com'],
            'subject' => 'Test Subject',
            'content' => 'Test Content'
        ]);
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
    }
}
```

## 🔧 Maintenance

### 1. Cleanup Old Logs

```php
// Xóa logs cũ hơn 30 ngày
$emailRepository->cleanOldEmailLogs(30);
```

### 2. Monitor Queue

```bash
# Kiểm tra queue status
php artisan queue:work --queue=emails

# Monitor failed jobs
php artisan queue:failed
```

### 3. Health Checks

```php
// Kiểm tra kết nối email
$emailService->testConnection();
```

## 📚 Best Practices

### 1. Email Content

- **Subject Lines**: Ngắn gọn, rõ ràng
- **Content**: Cấu trúc rõ ràng, dễ đọc
- **Attachments**: Tối ưu kích thước file
- **Templates**: Sử dụng templates cho consistency

### 2. Error Handling

- **Graceful Degradation**: Xử lý lỗi không crash system
- **Retry Logic**: Tự động retry với exponential backoff
- **Error Logging**: Log đầy đủ thông tin lỗi
- **User Feedback**: Thông báo rõ ràng cho user

### 3. Performance

- **Queue Processing**: Sử dụng queue cho background processing
- **Batch Operations**: Xử lý nhiều email cùng lúc
- **Caching**: Cache templates và configurations
- **Monitoring**: Theo dõi performance metrics

## 🚨 Troubleshooting

### 1. Common Issues

**Email không gửi được:**
- Kiểm tra cấu hình SMTP
- Kiểm tra credentials
- Kiểm tra firewall/network

**Queue jobs fail:**
- Kiểm tra queue worker
- Kiểm tra Redis connection
- Kiểm tra job logs

**Performance issues:**
- Tăng số lượng queue workers
- Optimize email content
- Sử dụng batch processing

### 2. Debug Commands

```bash
# Test email connection
php artisan tinker
>>> app(EmailServiceInterface::class)->testConnection()

# Check queue status
php artisan queue:work --queue=emails --verbose

# Clear failed jobs
php artisan queue:flush
```

## 📄 License

Hệ thống email này được phát triển theo Clean Architecture principles và tuân thủ Laravel best practices.

---

**Lưu ý**: Đảm bảo cấu hình email server đúng cách và test kỹ trước khi deploy production.

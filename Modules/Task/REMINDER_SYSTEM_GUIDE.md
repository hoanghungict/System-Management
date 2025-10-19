# 🔔 Hệ thống Thông báo Task - Hướng dẫn Sử dụng

## 📚 Tổng quan

Hệ thống thông báo Task được tích hợp hoàn toàn với Notifications Module, sử dụng kiến trúc event-driven với Kafka để gửi thông báo đa kênh (Email, Push, SMS, In-app) cho người dùng về tất cả các sự kiện liên quan đến tasks:

- **Task Events**: Tạo, cập nhật, giao, nộp, chấm điểm
- **Reminder Events**: Nhắc nhở deadline, quá hạn
- **Multi-Channel**: Email, Push, SMS, In-app notifications

## 🏗️ Kiến trúc Hệ thống

```
Task Events (Create/Update/Assign/Submit/Grade)
                    ↓
Event Dispatching → Queue Jobs → Kafka Events
                    ↓
Notification Handlers → Multi-Channel Notifications

Reminder System:
Task Creation → Automatic Reminders → Database Storage
                    ↓
Scheduler (Cron) → Process Reminders → Kafka Events
                    ↓
Reminder Handlers → Multi-Channel Notifications
```

## 📁 Cấu trúc Files đã thêm

### **1. Task Module Files**

#### **Models & Database:**
- `Modules/Task/app/Models/Reminder.php` - Model quản lý reminders
- `Modules/Task/database/migrations/2025_01_20_120000_create_reminders_table.php` - Migration tạo bảng reminders

#### **Services & Repositories:**
- `Modules/Task/app/Services/ReminderService.php` - Service chính xử lý reminders
- `Modules/Task/app/Repositories/Interfaces/ReminderRepositoryInterface.php` - Interface repository
- `Modules/Task/app/Repositories/ReminderRepository.php` - Implementation repository

#### **Controllers & Requests:**
- `Modules/Task/app/Http/Controllers/Reminder/ReminderController.php` - API endpoints
- `Modules/Task/app/Http/Requests/ReminderRequest.php` - Validation requests

#### **Jobs & Commands:**
- `Modules/Task/app/Jobs/SendReminderNotificationJob.php` - Background job gửi notifications
- `Modules/Task/app/Jobs/SendTaskCreatedNotificationJob.php` - Job gửi thông báo task tạo
- `Modules/Task/app/Jobs/SendTaskUpdatedNotificationJob.php` - Job gửi thông báo task cập nhật
- `Modules/Task/app/Jobs/SendTaskAssignedNotificationJob.php` - Job gửi thông báo task giao
- `Modules/Task/app/Jobs/SendTaskSubmittedNotificationJob.php` - Job gửi thông báo task nộp
- `Modules/Task/app/Jobs/SendTaskGradedNotificationJob.php` - Job gửi thông báo task chấm điểm
- `Modules/Task/app/Console/Commands/ProcessRemindersCommand.php` - Command xử lý reminders

#### **Events:**
- `Modules/Task/app/Events/TaskCreated.php` - Event khi task được tạo
- `Modules/Task/app/Events/TaskUpdated.php` - Event khi task được cập nhật
- `Modules/Task/app/Events/TaskAssigned.php` - Event khi task được giao
- `Modules/Task/app/Events/TaskSubmitted.php` - Event khi task được nộp
- `Modules/Task/app/Events/TaskGraded.php` - Event khi task được chấm điểm

#### **Integration:**
- `Modules/Task/app/Providers/TaskServiceProvider.php` - Đăng ký services và commands
- `Modules/Task/routes/RouteConfig.php` - Thêm reminder routes

### **2. Notifications Module Files**

#### **Task Event Handlers:**
- `Modules/Notifications/app/Handlers/TaskHandle/TaskCreatedHandler.php` - Xử lý thông báo task tạo
- `Modules/Notifications/app/Handlers/TaskHandle/TaskUpdatedHandler.php` - Xử lý thông báo task cập nhật
- `Modules/Notifications/app/Handlers/TaskHandle/TaskAssignedHandler.php` - Xử lý thông báo task giao
- `Modules/Notifications/app/Handlers/TaskHandle/TaskSubmittedHandler.php` - Xử lý thông báo task nộp
- `Modules/Notifications/app/Handlers/TaskHandle/TaskGradedHandler.php` - Xử lý thông báo task chấm điểm

#### **Reminder Event Handlers:**
- `Modules/Notifications/app/Handlers/ReminderHandle/TaskDeadlineReminderHandler.php` - Xử lý nhắc nhở deadline
- `Modules/Notifications/app/Handlers/ReminderHandle/TaskOverdueHandler.php` - Xử lý thông báo quá hạn

#### **Email Templates:**
- `Modules/Notifications/resources/views/emails/task_created.blade.php` - Template thông báo task mới
- `Modules/Notifications/resources/views/emails/task_updated.blade.php` - Template thông báo task cập nhật
- `Modules/Notifications/resources/views/emails/task_assigned.blade.php` - Template thông báo task được giao
- `Modules/Notifications/resources/views/emails/task_submitted.blade.php` - Template thông báo task được nộp
- `Modules/Notifications/resources/views/emails/task_graded.blade.php` - Template thông báo task được chấm điểm

#### **Configuration:**
- `Modules/Notifications/config/kafka_handle.php` - Đăng ký tất cả task và reminder handlers

## 🚀 Tính năng Chính

### **1. Task Event Notifications**
Hệ thống tự động gửi thông báo khi có sự kiện liên quan đến tasks:

#### **Task Created**
- Khi task được tạo, gửi thông báo cho tất cả receivers
- Template: `task_created.blade.php`
- Kafka Topic: `task.created`

#### **Task Updated**
- Khi task được cập nhật, gửi thông báo về những thay đổi
- Template: `task_updated.blade.php`
- Kafka Topic: `task.updated`

#### **Task Assigned**
- Khi task được giao cho user cụ thể
- Template: `task_assigned.blade.php`
- Kafka Topic: `task.assigned`

#### **Task Submitted**
- Khi student nộp bài, gửi thông báo cho lecturer
- Template: `task_submitted.blade.php`
- Kafka Topic: `task.submitted`

#### **Task Graded**
- Khi lecturer chấm điểm, gửi thông báo cho student
- Template: `task_graded.blade.php`
- Kafka Topic: `task.graded`

### **2. Automatic Reminders**
Khi tạo task mới, hệ thống tự động tạo reminders:
- **1 tuần trước deadline** - Email reminder
- **1 ngày trước deadline** - Email reminder  
- **1 giờ trước deadline** - Push notification

### **3. Manual Reminders**
Người dùng có thể tạo reminders tùy chỉnh:
- **Custom timing** - Tự đặt thời gian nhắc nhở
- **Multiple types** - Email, Push, SMS, In-app
- **Custom messages** - Tin nhắn tùy chỉnh

### **3. Multi-Channel Notifications**
Hỗ trợ gửi thông báo qua nhiều kênh:
- **Email** - Gửi email với template đẹp
- **Push** - Push notification real-time
- **SMS** - Tin nhắn SMS (nếu có cấu hình)
- **In-app** - Thông báo trong ứng dụng

### **4. Smart Priority System**
Tự động điều chỉnh độ ưu tiên dựa trên thời gian:
- **Critical** - ≤ 1 giờ trước deadline
- **High** - ≤ 24 giờ trước deadline
- **Medium** - ≤ 72 giờ trước deadline
- **Low** - > 72 giờ trước deadline

## 🔧 Hướng dẫn Sử dụng

### **1. API Endpoints**

#### **Tạo Reminder:**
```http
POST /api/task/reminders
Content-Type: application/json
Authorization: Bearer {token}

{
    "task_id": 123,
    "reminder_type": "email",
    "reminder_time": "2024-12-31 10:00:00",
    "message": "Nhắc nhở deadline sắp tới"
}
```

#### **Xem Reminders:**
```http
GET /api/task/reminders?status=pending&reminder_type=email
Authorization: Bearer {token}
```

#### **Cập nhật Reminder:**
```http
PUT /api/task/reminders/{id}
Content-Type: application/json
Authorization: Bearer {token}

{
    "reminder_time": "2024-12-31 11:00:00",
    "message": "Cập nhật nhắc nhở"
}
```

#### **Xóa Reminder:**
```http
DELETE /api/task/reminders/{id}
Authorization: Bearer {token}
```

### **2. Console Commands**

#### **Xử lý Reminders (Development):**
```bash
# Chạy thử không gửi notification thực tế
php artisan reminders:process --dry-run

# Xử lý reminders thực tế
php artisan reminders:process

# Giới hạn số reminders xử lý
php artisan reminders:process --limit=50
```

#### **Xử lý Reminders (Production):**
```bash
# Chạy trong Docker container
docker-compose exec app php artisan reminders:process

# Schedule trong crontab (mỗi phút)
* * * * * cd /path/to/project && php artisan reminders:process
```

### **3. Laravel Scheduler (Khuyến nghị)**

Thêm vào `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Xử lý reminders mỗi phút
    $schedule->command('reminders:process')
             ->everyMinute()
             ->withoutOverlapping();
}
```

Sau đó chạy:
```bash
php artisan schedule:work
```

## 📊 Database Schema

### **Bảng `reminders`:**
```sql
CREATE TABLE reminders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    user_type ENUM('student', 'lecturer', 'admin') NOT NULL,
    reminder_type ENUM('email', 'push', 'sms', 'in_app') NOT NULL,
    reminder_time DATETIME NOT NULL,
    message TEXT NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    sent_at DATETIME NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (task_id) REFERENCES task(id) ON DELETE CASCADE,
    INDEX idx_user (user_id, user_type),
    INDEX idx_task_status (task_id, status),
    INDEX idx_reminder_time_status (reminder_time, status)
);
```

## 🔔 Event Flow

### **1. Tạo Task:**
```
Task Created → ReminderService.createAutomaticReminders() → 
3 Reminders Created (1 week, 1 day, 1 hour before deadline)
```

### **2. Xử lý Reminders:**
```
Scheduler Runs → ProcessRemindersCommand → 
Find Due Reminders → Publish Kafka Events → 
Handlers Process → Send Notifications
```

### **3. Kafka Events:**
- `reminder.task.deadline` - Nhắc nhở deadline sắp tới
- `reminder.task.overdue` - Thông báo task quá hạn

## 📝 Template System

### **Template Variables:**
```php
$templateData = [
    'user_name' => 'John Doe',
    'task_name' => 'Complete Assignment',
    'task_description' => 'Write a report about...',
    'deadline' => '2024-12-31 23:59:59',
    'time_until_deadline' => '2 ngày 5 giờ',
    'reminder_time' => '2024-12-29 10:00:00',
    'task_url' => 'https://app.com/tasks/123',
    'app_name' => 'Hệ thống quản lý giáo dục',
    'year' => '2024'
];
```

### **Template Examples:**
```
Email Subject: "Nhắc nhở deadline: {{task_name}}"
Email Body: "Chào {{user_name}}, bạn có task '{{task_name}}' sắp đến hạn trong {{time_until_deadline}}."

Push: "{{task_name}}: Còn {{time_until_deadline}} đến deadline"
SMS: "Nhắc nhở: {{task_name}} sắp đến hạn"
```

## 🛠️ Configuration

### **1. Environment Variables:**
```env
# Kafka Configuration
KAFKA_BROKERS=localhost:9092
KAFKA_GROUP_ID=notifications-consumer

# Notification Configuration
NOTIFICATION_QUEUE=notifications
NOTIFICATION_RETRY_ATTEMPTS=3
NOTIFICATION_TIMEOUT=60
```

### **2. Service Provider Registration:**
```php
// Modules/Task/app/Providers/TaskServiceProvider.php
$this->app->bind(ReminderRepositoryInterface::class, ReminderRepository::class);
$this->app->bind(ReminderService::class, ReminderService::class);
```

### **3. Kafka Handler Configuration:**
```php
// Modules/Notifications/config/kafka_handle.php
'handlers' => [
    'reminder.task.deadline' => TaskDeadlineReminderHandler::class,
    'reminder.task.overdue' => TaskOverdueHandler::class,
]
```

## 📊 Monitoring & Logging

### **1. Logs:**
- **ReminderService** - Tạo và gửi reminders
- **Handlers** - Xử lý events và notifications
- **Commands** - Tiến độ xử lý reminders

### **2. Database Tracking:**
- **reminders.status** - Trạng thái reminder (pending/sent/failed)
- **reminders.sent_at** - Thời gian gửi thành công
- **notifications table** - Records notifications đã gửi

### **3. Metrics:**
```bash
# Xem số reminders pending
php artisan tinker
>>> Reminder::where('status', 'pending')->count()

# Xem reminders trong 24h tới
>>> Reminder::where('reminder_time', '<=', now()->addDay())->count()
```

## ⚠️ Troubleshooting

### **1. Reminders không được gửi:**
```bash
# Kiểm tra Kafka consumer
php artisan kafka:consume

# Kiểm tra queue worker
php artisan queue:work

# Kiểm tra scheduler
php artisan schedule:list
```

### **2. Lỗi thường gặp:**
- **Missing user_id** - Kiểm tra task có assigned_to_id không
- **Kafka connection failed** - Kiểm tra KAFKA_BROKERS
- **Template not found** - Kiểm tra notification templates

### **3. Debug Commands:**
```bash
# Test reminder processing
php artisan reminders:process --dry-run --limit=5

# Xem logs
tail -f storage/logs/laravel.log | grep ReminderService

# Test Kafka events
php artisan kafka:produce reminder.task.deadline '{"user_id": 123, "task_name": "Test Task"}'
```

## 🎯 Best Practices

### **1. Performance:**
- Sử dụng `--limit` để xử lý reminders theo batch
- Chạy scheduler mỗi phút, không phải mỗi giây
- Monitor memory usage khi xử lý nhiều reminders

### **2. Reliability:**
- Sử dụng `withoutOverlapping()` cho scheduler
- Implement retry mechanism cho failed reminders
- Monitor và alert khi có lỗi

### **3. User Experience:**
- Không spam users với quá nhiều reminders
- Sử dụng priority phù hợp
- Cung cấp cách unsubscribe reminders

## 📈 Future Enhancements

### **1. Planned Features:**
- **Smart Reminders** - AI-powered timing suggestions
- **Bulk Operations** - Tạo/xóa nhiều reminders cùng lúc
- **Reminder Templates** - Pre-defined reminder templates
- **Analytics** - Thống kê hiệu quả reminders

### **2. Integration:**
- **Calendar Integration** - Sync với Google Calendar
- **Mobile App** - Push notifications cho mobile
- **Webhook Support** - External system integration

---

## 📞 Support

Nếu có vấn đề hoặc cần hỗ trợ, vui lòng:
1. Kiểm tra logs trong `storage/logs/laravel.log`
2. Chạy `php artisan reminders:process --dry-run` để test
3. Kiểm tra Kafka và Queue workers đang chạy
4. Liên hệ team development để được hỗ trợ

**Hệ thống nhắc nhở Task đã sẵn sàng sử dụng! 🚀**

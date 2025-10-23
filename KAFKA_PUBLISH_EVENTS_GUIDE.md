# 📨 Kafka Events Publishing Guide for External Services

## 🎯 Mục đích

File này hướng dẫn **các services BÊN NGOÀI** (hoặc modules mới) cách publish events lên Kafka để trigger notifications.

## 📡 Base Information

**Kafka Broker:** `localhost:9092` (configured in `.env`)

**Available Topics:** Xem bảng dưới

**Service Class:** `KafkaProducerService`

**Location:** `Modules/Notifications/app/Services/KafkaService/KafkaProducerService.php`

---

# 📋 Table of Contents

1. [How to Publish Events](#1-how-to-publish-events)
2. [Available Topics & Payloads](#2-available-topics--payloads)
3. [Code Examples](#3-code-examples)
4. [Testing Events](#4-testing-events)

---

# 1. How to Publish Events

## Method 1: Using KafkaProducerService (Recommended)

```php
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;

// Inject vào service/controller
protected $kafkaProducer;

public function __construct(KafkaProducerService $kafkaProducer)
{
    $this->kafkaProducer = $kafkaProducer;
}

// Publish event
$this->kafkaProducer->send('topic.name', [
    'field1' => 'value1',
    'field2' => 'value2'
]);
```

## Method 2: Using REST API

```bash
POST http://localhost:8000/api/v1/events/publish
Content-Type: application/json

{
  "topic": "topic.name",
  "payload": {
    "field1": "value1",
    "field2": "value2"
  },
  "priority": "medium",
  "key": "unique_event_key"
}
```

## Method 3: Using Kafka Console Producer (Testing)

```bash
kafka-console-producer --broker-list localhost:9092 --topic topic.name

# Then type JSON payload:
{"field1":"value1","field2":"value2"}
```

---

# 2. Available Topics & Payloads

## 2.1. Student Registration Event 👨‍🎓

### Topic: `student.registered`

**Handler:** `RegisterStudentHandle`

**Template:** `student_account_created`

**Khi nào dùng:**

-   Khi tạo student account mới
-   Sau khi insert vào table `student` và `student_account`

**Required Fields:**

```json
{
    "user_id": 1, // [REQUIRED] ID của student vừa tạo
    "name": "string", // [REQUIRED] Tên đầy đủ của student
    "user_name": "string", // [REQUIRED] Username (sv_SV001)
    "password": "string" // [REQUIRED] Password mặc định (123456)
}
```

**Optional Fields:**

```json
{
    "sender_id": 1, // ID người tạo (admin)
    "sender_type": "admin" // Loại người tạo
}
```

**Complete Example:**

```php
// Location: Modules/Auth/app/Services/AuthUserService/StudentService.php
// Line: ~76-82

$this->kafkaProducer->send('student.registered', [
    'user_id' => $student->id,
    'name' => $dataStudent->full_name ?? "Unknown",
    'user_name' => $username ?? "Unknown",
    'password' => $password
]);
```

**What Happens:**

1. Handler nhận event
2. Prepare template data với app_name, year, logo_url, banner_url
3. Send notification qua 3 channels:
    - Email: Gửi thông tin username/password
    - Push: Real-time notification
    - In-app: Lưu vào database

**Expected Notification:**

-   Title: "Tài khoản đã được tạo"
-   Content: "Tài khoản của bạn đã được tạo. Username: sv_SV001, Password: 123456"

---

## 2.2. Lecturer Registration Event 👨‍🏫

### Topic: `lecturer.registered`

**Handler:** `RegisterLecturerHandle`

**Template:** `lecturer_account_created`

**Khi nào dùng:**

-   Khi tạo lecturer account mới
-   Sau khi insert vào table `lecturer` và `lecturer_account`

**Required Fields:**

```json
{
    "user_id": 1, // [REQUIRED] ID của lecturer vừa tạo
    "name": "string", // [REQUIRED] Tên đầy đủ của lecturer
    "user_name": "string", // [REQUIRED] Username (gv_GV001)
    "password": "string" // [REQUIRED] Password mặc định (123456)
}
```

**Complete Example:**

```php
// Location: Modules/Auth/app/Services/AuthUserService/LecturerService.php
// Line: ~77-82

$this->producerService->send('lecturer.registered', [
    'user_id' => $lecturer->id,
    'name' => $dataLecturer->full_name ?? "Unknown",
    'user_name' => $username ?? "Unknown",
    'password' => $password
]);
```

**What Happens:**

1. Handler nhận event
2. Prepare template data tương tự student
3. Send notification qua email, push, in-app
4. Lecturer nhận thông tin tài khoản

---

## 2.3. Task Created Event 📝

### Topic: `task.created`

**Handler:** `TaskCreatedHandler`

**Template:** `task_created`

**Khi nào dùng:**

-   Sau khi tạo task mới thành công
-   Gửi cho TẤT CẢ receivers của task

**Required Fields:**

```json
{
    "task_id": 1, // [REQUIRED] ID của task
    "receiver_id": 1, // [REQUIRED] ID người nhận
    "receiver_type": "student" // [REQUIRED] Loại người nhận
}
```

**Optional Fields:**

```json
{
    "task_title": "string", // Tiêu đề task (default: "New Task")
    "task_description": "string", // Mô tả task
    "deadline": "2024-01-20 23:59:59", // Deadline
    "priority": "high", // Độ ưu tiên (low/medium/high/urgent)
    "creator_name": "string", // Tên người tạo
    "receiver_name": "string", // Tên người nhận
    "task_url": "string" // Link đến task
}
```

**Complete Example:**

```php
// Location: Modules/Task/app/Jobs/SendTaskCreatedNotificationJob.php
// Triggered by: event(new TaskCreated($task))

// Handler expects:
[
    'task_id' => 1,
    'task_title' => 'Bài tập tuần 1',
    'task_description' => 'Làm bài tập về nhà',
    'deadline' => '2024-01-20 23:59:59',
    'priority' => 'high',
    'creator_name' => 'Trần Thị B',
    'receiver_id' => 1,
    'receiver_type' => 'student',
    'receiver_name' => 'Nguyễn Văn A',
    'task_url' => 'http://localhost:3000/tasks/1'
]
```

**What Happens:**

1. Handler validates required fields (task_id, receiver_id, receiver_type)
2. Prepare template data
3. Send notification với priority based on task priority
4. Receiver nhận notification qua email, push, in-app

---

## 2.4. Task Updated Event ✏️

### Topic: `task.updated`

**Handler:** `TaskUpdatedHandler`

**Template:** `task_updated`

**Khi nào dùng:**

-   Sau khi update task
-   Chỉ gửi nếu có changes

**Required Fields:**

```json
{
    "task_id": 1, // [REQUIRED] ID của task
    "receiver_id": 1, // [REQUIRED] ID người nhận
    "receiver_type": "student" // [REQUIRED] Loại người nhận
}
```

**Optional Fields:**

```json
{
    "task_title": "string", // Tiêu đề task
    "changes": {
        // Object chứa các thay đổi
        "deadline": {
            "old": "2024-01-20 23:59:59",
            "new": "2024-01-25 23:59:59"
        },
        "priority": {
            "old": "medium",
            "new": "high"
        }
    },
    "updater_name": "string", // Người cập nhật
    "receiver_name": "string", // Tên người nhận
    "task_url": "string" // Link đến task
}
```

**Complete Example:**

```php
// Location: Modules/Task/app/Jobs/SendTaskUpdatedNotificationJob.php

[
    'task_id' => 1,
    'task_title' => 'Bài tập tuần 1',
    'changes' => [
        'deadline' => [
            'old' => '2024-01-20 23:59:59',
            'new' => '2024-01-25 23:59:59'
        ],
        'priority' => [
            'old' => 'medium',
            'new' => 'high'
        ]
    ],
    'updater_name' => 'Trần Thị B',
    'receiver_id' => 1,
    'receiver_type' => 'student',
    'task_url' => 'http://localhost:3000/tasks/1'
]
```

**What Happens:**

1. Handler validates required fields
2. Format changes thành human-readable text:
    - "Deadline: 2024-01-20 → 2024-01-25"
    - "Priority: medium → high"
3. Priority được set cao nếu thay đổi deadline/priority/status
4. Send notification với change summary

---

## 2.5. Task Assigned Event 🎯

### Topic: `task.assigned`

**Handler:** `TaskAssignedHandler`

**Template:** `task_assigned`

**Khi nào dùng:**

-   Khi gán task cho người mới
-   Khi reassign task

**Required Fields:**

```json
{
    "user_id": 1 // [REQUIRED] ID của user được gán
}
```

**Optional Fields:**

```json
{
    "name": "string", // Tên người được gán
    "user_name": "string", // Username
    "user_email": "string", // Email
    "user_type": "student", // Loại user (default: student)
    "task_name": "string" // Tên task
}
```

**Complete Example:**

```php
// Location: Modules/Task/app/Services/TaskService.php
// Line: ~122-127

$this->kafkaProducer->send('task.assigned', [
    'user_id' => $task->creator_id,
    'name' => $task->creator_name ?? "Unknown",
    'user_name' => $task->creator_name ?? "Unknown",
    'user_email' => $task->creator_email ?? 'no-email@example.com'
]);
```

**What Happens:**

1. Handler nhận event
2. Determine receiver info
3. Send notification về task assignment
4. Priority based on task priority và deadline

---

## 2.6. Task Submitted Event 📤

### Topic: `task.submitted`

**Handler:** `TaskSubmittedHandler`

**Template:** `task_submitted`

**Khi nào dùng:**

-   Khi student submit task
-   Gửi notification cho lecturer (creator của task)

**Required Fields:**

```json
{
    "task_id": 1, // [REQUIRED] ID của task
    "creator_id": 1, // [REQUIRED] ID của task creator (lecturer)
    "creator_type": "lecturer" // [REQUIRED] Loại creator
}
```

**Optional Fields:**

```json
{
    "task_title": "string", // Tiêu đề task
    "submitter_name": "string", // Tên người submit (student)
    "submission_content": "string", // Nội dung submission
    "submitted_at": "datetime", // Thời gian submit
    "creator_name": "string", // Tên lecturer
    "task_url": "string", // Link đến task
    "submission_url": "string", // Link đến submission
    "is_late": false, // Submit muộn hay không
    "days_late": 0 // Số ngày muộn
}
```

**Complete Example:**

```php
// Khi student submit task
$this->kafkaProducer->send('task.submitted', [
    'task_id' => 1,
    'task_title' => 'Bài tập tuần 1',
    'creator_id' => 1,
    'creator_type' => 'lecturer',
    'creator_name' => 'Trần Thị B',
    'submitter_name' => 'Nguyễn Văn A',
    'submission_content' => 'Đã hoàn thành bài tập',
    'submitted_at' => now()->format('Y-m-d H:i:s'),
    'is_late' => false,
    'days_late' => 0,
    'task_url' => 'http://localhost:3000/tasks/1',
    'submission_url' => 'http://localhost:3000/tasks/1/submissions/1'
]);
```

**What Happens:**

1. Handler validates task_id, creator_id, creator_type
2. Prepare template data
3. Priority = 'high' nếu submit muộn
4. Send notification cho lecturer (creator)
5. Lecturer nhận thông báo: "Sinh viên {name} đã nộp bài"

---

## 2.7. Task Graded Event 📊

### Topic: `task.graded`

**Handler:** `TaskGradedHandler`

**Template:** `task_graded`

**Khi nào dùng:**

-   Khi lecturer chấm điểm task
-   Gửi notification cho student

**Required Fields:**

```json
{
    "task_id": 1, // [REQUIRED] ID của task
    "student_id": 1, // [REQUIRED] ID của student
    "student_type": "student" // [REQUIRED] Loại (default: student)
}
```

**Optional Fields:**

```json
{
    "task_title": "string", // Tiêu đề task
    "grade": 9.5, // Điểm số
    "max_grade": 10, // Điểm tối đa
    "feedback": "string", // Nhận xét
    "grader_name": "string", // Tên người chấm
    "student_name": "string", // Tên student
    "graded_at": "datetime", // Thời gian chấm
    "task_url": "string", // Link đến task
    "grade_url": "string" // Link đến kết quả
}
```

**Complete Example:**

```php
// Khi lecturer chấm điểm
$this->kafkaProducer->send('task.graded', [
    'task_id' => 1,
    'task_title' => 'Bài tập tuần 1',
    'student_id' => 1,
    'student_type' => 'student',
    'student_name' => 'Nguyễn Văn A',
    'grade' => 9.5,
    'max_grade' => 10,
    'feedback' => 'Làm tốt lắm! Keep it up!',
    'grader_name' => 'Trần Thị B',
    'graded_at' => now()->format('Y-m-d H:i:s'),
    'task_url' => 'http://localhost:3000/tasks/1',
    'grade_url' => 'http://localhost:3000/tasks/1/grade'
]);
```

**What Happens:**

1. Handler validates required fields
2. Calculate grade percentage: (9.5/10) \* 100 = 95%
3. Determine grade status: "Excellent" (>= 90%)
4. Add grade emoji: 🎉
5. Priority = 'high' nếu grade >= 95% hoặc < 50%
6. Send notification cho student
7. Student nhận: "Bài tập đã được chấm điểm: 9.5/10 (95%) - Excellent 🎉"

**Grade Status Mapping:**

```php
>= 90%: "Excellent" 🎉
>= 80%: "Very Good" 😊
>= 70%: "Good" 👍
>= 60%: "Satisfactory" 👌
>= 50%: "Pass" ✅
< 50%:  "Fail" ❌
```

---

## 2.8. Task Deadline Reminder ⏰

### Topic: `reminder.task.deadline`

**Handler:** `TaskDeadlineReminderHandler`

**Template:** `task_deadline_reminder`

**Khi nào dùng:**

-   Scheduled job để nhắc deadline
-   24h trước deadline
-   1h trước deadline

**Required Fields:**

```json
{
    "user_id": 1, // [REQUIRED] ID của user cần nhắc
    "task_id": 1, // [REQUIRED] ID của task
    "reminder_time": "string" // [REQUIRED] Thời điểm nhắc (24h, 1h, etc.)
}
```

**Optional Fields:**

```json
{
    "user_type": "student", // Loại user (default: student)
    "user_name": "string", // Tên user
    "task_name": "string", // Tên task
    "task_description": "string", // Mô tả
    "deadline": "datetime", // Deadline
    "task_url": "string", // Link đến task
    "reminder_type": "email" // Loại reminder (email/push/sms/in_app)
}
```

**Complete Example:**

```php
// Auto-generated by cron job
$this->kafkaProducer->send('reminder.task.deadline', [
    'user_id' => 1,
    'user_type' => 'student',
    'user_name' => 'Nguyễn Văn A',
    'task_id' => 1,
    'task_name' => 'Bài tập tuần 1',
    'task_description' => 'Làm bài tập về nhà',
    'deadline' => '2024-01-20 23:59:59',
    'reminder_time' => '24h',
    'reminder_type' => 'email',
    'task_url' => 'http://localhost:3000/tasks/1'
]);
```

**What Happens:**

1. Handler calculates time until deadline
2. Priority based on urgency:
    - <= 1h: "critical"
    - <= 24h: "high"
    - <= 72h: "medium"
    - else: "low"
3. Channels based on reminder_type:
    - 'email': [email, in_app]
    - 'push': [push, in_app]
    - default: [email, push, in_app]
4. Send notification: "Nhắc nhở: Task {name} sẽ deadline sau {time}"

---

## 2.9. Task Overdue Reminder ⚠️

### Topic: `reminder.task.overdue`

**Handler:** `TaskOverdueHandler`

**Template:** `task_overdue`

**Khi nào dùng:**

-   Khi task đã quá deadline nhưng chưa complete
-   Gửi cho cả student và lecturer

**Required Fields:**

```json
{
    "user_id": 1, // [REQUIRED] ID của user
    "task_id": 1 // [REQUIRED] ID của task
}
```

**Optional Fields:**

```json
{
    "user_type": "student", // Loại user
    "user_name": "string", // Tên user
    "task_name": "string", // Tên task
    "deadline": "datetime", // Deadline đã quá
    "days_overdue": 3, // Số ngày quá hạn
    "task_url": "string" // Link đến task
}
```

**Complete Example:**

```php
$this->kafkaProducer->send('reminder.task.overdue', [
    'user_id' => 1,
    'user_type' => 'student',
    'user_name' => 'Nguyễn Văn A',
    'task_id' => 1,
    'task_name' => 'Bài tập tuần 1',
    'deadline' => '2024-01-20 23:59:59',
    'days_overdue' => 3,
    'task_url' => 'http://localhost:3000/tasks/1'
]);
```

**What Happens:**

1. Priority = 'critical' (vì đã quá hạn)
2. Send notification urgent
3. Content: "Task {name} đã quá hạn {days} ngày!"

---

# 3. Code Examples

## Example 1: Publish from New Service

```php
<?php

namespace App\Services;

use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;
use Illuminate\Support\Facades\Log;

class YourNewService
{
    protected $kafkaProducer;

    public function __construct(KafkaProducerService $kafkaProducer)
    {
        $this->kafkaProducer = $kafkaProducer;
    }

    public function createSomething($data)
    {
        // Your business logic
        $entity = Entity::create($data);

        try {
            // Publish event to Kafka
            $this->kafkaProducer->send('student.registered', [
                'user_id' => $entity->id,
                'name' => $entity->name,
                'user_name' => $entity->username,
                'password' => '123456'
            ]);

            Log::info('Event published successfully', [
                'topic' => 'student.registered',
                'entity_id' => $entity->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to publish event', [
                'error' => $e->getMessage(),
                'entity_id' => $entity->id
            ]);
            // Don't throw - continue even if Kafka fails
        }

        return $entity;
    }
}
```

---

## Example 2: Publish Multiple Events

```php
// Khi có nhiều receivers cho task
foreach ($task->receivers as $receiver) {
    $this->kafkaProducer->send('task.created', [
        'task_id' => $task->id,
        'task_title' => $task->title,
        'task_description' => $task->description,
        'deadline' => $task->deadline,
        'priority' => $task->priority,
        'creator_name' => $creator->full_name,
        'receiver_id' => $receiver->receiver_id,
        'receiver_type' => $receiver->receiver_type,
        'receiver_name' => $receiver->name,
        'task_url' => "http://localhost:3000/tasks/{$task->id}"
    ]);
}
```

---

## Example 3: Using REST API from External Service

```php
// Nếu service không có access vào KafkaProducerService
// Có thể dùng HTTP API

$httpClient = new \GuzzleHttp\Client();

$response = $httpClient->post('http://localhost:8000/api/v1/events/publish', [
    'json' => [
        'topic' => 'student.registered',
        'payload' => [
            'user_id' => 1,
            'name' => 'Nguyễn Văn A',
            'user_name' => 'sv_SV001',
            'password' => '123456'
        ],
        'priority' => 'medium'
    ]
]);

$result = json_decode($response->getBody(), true);
// {"success": true, "message": "Event published successfully", ...}
```

---

## Example 4: Batch Publishing

```php
// Send multiple events at once
$events = [
    [
        'payload' => ['user_id' => 1, 'name' => 'User 1', ...],
        'key' => 'student_1'
    ],
    [
        'payload' => ['user_id' => 2, 'name' => 'User 2', ...],
        'key' => 'student_2'
    ]
];

$this->kafkaProducer->sendBatch('student.registered', $events);
```

---

# 4. Testing Events

## 4.1. Using Kafka Console Producer

```bash
# Connect to Kafka container
docker exec -it kafka bash

# Produce event
kafka-console-producer --broker-list localhost:9092 --topic student.registered

# Type JSON (one line):
{"user_id":999,"name":"Test Student","user_name":"sv_TEST","password":"123456"}

# Press Ctrl+C to exit
```

## 4.2. Using REST API (Postman/cURL)

```bash
curl -X POST http://localhost:8000/api/v1/events/publish \
  -H "Content-Type: application/json" \
  -d '{
    "topic": "student.registered",
    "payload": {
      "user_id": 999,
      "name": "Test Student",
      "user_name": "sv_TEST",
      "password": "123456"
    }
  }'
```

## 4.3. Check Kafka Consumer Logs

```bash
# View consumer logs
docker logs -f laravel_app

# Or in Laravel
tail -f storage/logs/laravel.log | grep "RegisterStudentHandle"
```

## 4.4. Verify Notification in Database

```sql
-- Check notifications table
SELECT * FROM notifications ORDER BY id DESC LIMIT 5;

-- Check user_notifications
SELECT * FROM user_notifications WHERE user_id = 999 ORDER BY id DESC;
```

---

# 5. Handler Registration

## Thêm Handler Mới

**Step 1:** Tạo Handler Class

```php
<?php

namespace Modules\Notifications\app\Handlers\YourHandle;

use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Illuminate\Support\Facades\Log;

class YourNewHandler implements NotificationEventHandler
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(string $channel, array $data): void
    {
        Log::info('YourNewHandler: Start', ['data' => $data]);

        // Validate required fields
        if (!isset($data['user_id'])) {
            Log::warning('Missing user_id');
            return;
        }

        // Prepare template data
        $templateData = [
            'field1' => $data['field1'] ?? 'default',
            'field2' => $data['field2'] ?? 'default'
        ];

        // Send notification
        $this->notificationService->sendNotification(
            'your_template_name',
            [['user_id' => $data['user_id'], 'user_type' => 'student']],
            $templateData,
            ['priority' => 'medium']
        );
    }
}
```

**Step 2:** Register trong Config

```php
// Modules/Notifications/config/kafka_handle.php

'handlers' => [
    'your.topic.name' => Modules\Notifications\app\Handlers\YourHandle\YourNewHandler::class,
]
```

**Step 3:** Publish Event

```php
$this->kafkaProducer->send('your.topic.name', [
    'user_id' => 1,
    'field1' => 'value1',
    'field2' => 'value2'
]);
```

---

# 6. Best Practices

## ✅ DO's:

1. **Always validate required fields** trong handler

```php
if (!isset($data['user_id'])) {
    Log::warning('Missing user_id');
    return; // Don't throw, just return
}
```

2. **Use try-catch** để catch exceptions

```php
try {
    $this->notificationService->sendNotification(...);
} catch (\Exception $e) {
    Log::error('Failed to send', ['error' => $e->getMessage()]);
}
```

3. **Log đầy đủ** để debug

```php
Log::info('Handler started', ['data' => $data]);
Log::info('Notification sent', ['notification_id' => $result['notification_id']]);
```

4. **Provide defaults** cho optional fields

```php
$taskTitle = $data['task_title'] ?? 'New Task';
$priority = $data['priority'] ?? 'medium';
```

5. **Use proper types**

```php
$userId = (int) $data['user_id'];
$taskId = (int) $data['task_id'];
```

## ❌ DON'Ts:

1. **Đừng throw exception** trong handler - sẽ crash consumer

```php
// BAD:
if (!isset($data['user_id'])) {
    throw new \Exception('Missing user_id');
}

// GOOD:
if (!isset($data['user_id'])) {
    Log::warning('Missing user_id');
    return;
}
```

2. **Đừng quên log** - rất khó debug nếu không có logs

3. **Đừng gửi event với payload quá lớn** - giới hạn ~1MB

4. **Đừng expect synchronous response** - Kafka là async

---

# 7. Troubleshooting

## Problem: Event không được consume

**Check:**

```bash
# 1. Kafka consumer có đang chạy?
php artisan notifications:subscribe

# 2. Topic có tồn tại?
kafka-topics --list --bootstrap-server localhost:9092

# 3. Handler có được register?
# Check: Modules/Notifications/config/kafka_handle.php

# 4. Check logs
tail -f storage/logs/laravel.log
```

## Problem: Notification không được gửi

**Check:**

```bash
# 1. Template có tồn tại?
SELECT * FROM notification_templates WHERE name = 'template_name';

# 2. User có tồn tại?
SELECT * FROM student WHERE id = ?;

# 3. Email queue có chạy?
php artisan queue:work

# 4. WebSocket có connect?
# Check Laravel Broadcasting
```

## Problem: Duplicate notifications

**Solution:** Use unique event key

```php
$this->kafkaProducer->send('topic.name', $payload,
    "unique_key_{$entity->id}_{$timestamp}"
);
```

---

# 8. Topic Naming Convention

## Pattern:

```
{entity}.{action}
```

**Examples:**

-   `student.registered` - Student được đăng ký
-   `student.updated` - Student được cập nhật
-   `task.created` - Task được tạo
-   `task.assigned` - Task được gán
-   `reminder.task.deadline` - Nhắc deadline

## Wildcard Pattern:

```php
'patterns' => [
    'task.*',      // Match all: task.created, task.updated, task.assigned
    'student.*',   // Match all: student.registered, student.updated
    'lecturer.*'   // Match all: lecturer.registered, lecturer.updated
]
```

---

# 9. Quick Reference Table

| Topic                    | Required Fields                     | Template                 | Receiver       | Priority               |
| ------------------------ | ----------------------------------- | ------------------------ | -------------- | ---------------------- |
| `student.registered`     | user_id, name, user_name, password  | student_account_created  | New student    | medium                 |
| `lecturer.registered`    | user_id, name, user_name, password  | lecturer_account_created | New lecturer   | medium                 |
| `task.created`           | task_id, receiver_id, receiver_type | task_created             | Task receivers | Based on task priority |
| `task.updated`           | task_id, receiver_id, receiver_type | task_updated             | Task receivers | Based on changes       |
| `task.assigned`          | user_id                             | task_assigned            | Assigned user  | Based on task          |
| `task.submitted`         | task_id, creator_id, creator_type   | task_submitted           | Task creator   | High if late           |
| `task.graded`            | task_id, student_id, student_type   | task_graded              | Student        | High if extreme grade  |
| `reminder.task.deadline` | user_id, task_id, reminder_time     | task_deadline_reminder   | Task receiver  | Based on urgency       |
| `reminder.task.overdue`  | user_id, task_id                    | task_overdue             | Task receiver  | critical               |

---

# 10. Common Payload Patterns

## User Payload:

```json
{
    "user_id": 1,
    "user_type": "student",
    "user_name": "Nguyễn Văn A"
}
```

## Task Payload:

```json
{
    "task_id": 1,
    "task_title": "string",
    "task_description": "string",
    "deadline": "datetime",
    "priority": "high"
}
```

## Receiver Payload:

```json
{
    "receiver_id": 1,
    "receiver_type": "student",
    "receiver_name": "Nguyễn Văn A"
}
```

## Creator Payload:

```json
{
    "creator_id": 1,
    "creator_type": "lecturer",
    "creator_name": "Trần Thị B"
}
```

---

# 11. Integration Checklist

Khi tạo service mới cần gửi notifications:

-   [ ] Inject `KafkaProducerService` vào constructor
-   [ ] Xác định topic name theo convention
-   [ ] Chuẩn bị payload với đầy đủ required fields
-   [ ] Add optional fields để notification rõ ràng hơn
-   [ ] Wrap trong try-catch để handle errors
-   [ ] Log event đã được published
-   [ ] Test event bằng Kafka console hoặc REST API
-   [ ] Verify notification được gửi đến user
-   [ ] Check logs để debug nếu cần

---

# 12. Support

**Notification Module Location:** `Modules/Notifications/`

**Handler Config:** `Modules/Notifications/config/kafka_handle.php`

**Service Location:** `Modules/Notifications/app/Services/`

**Consumer Command:** `php artisan notifications:subscribe`

**Logs:** `storage/logs/laravel.log`

---

## Contact

**Questions?** Check logs hoặc thêm handler mới theo hướng dẫn section 5.

**Version:** 1.0.0  
**Last Updated:** 2024-01-15


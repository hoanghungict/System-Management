# 🔔 Notifications Module - Development Rules

## 📚 Overview

The Notifications Module provides a comprehensive multi-channel notification system with event-driven architecture, template system, and Kafka integration.

## 🏗️ Architecture Patterns

### 1. **Event-Driven Architecture**
```
Kafka Event → Handler → NotificationService → Channels (Email/Push/SMS/In-app)
```

### 2. **Clean Architecture Layers**
```
Controller → Service → Repository → Model
    ↓
Handler → NotificationService → Channel Services
```

## 🔧 Core Components

### **1. Event Handlers**
- **Location**: `Modules/Notifications/app/Handlers/`
- **Interface**: `NotificationEventHandler`
- **Pattern**: Implement `handle(string $channel, array $data): void`

```php
class TaskAssignedHandler implements NotificationEventHandler
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(string $channel, array $data): void
    {
        // 1. Validate data
        if (!isset($data['user_id'])) {
            Log::warning('Missing required data', ['data' => $data]);
            return;
        }

        // 2. Prepare template data
        $templateData = $this->prepareTemplateData($data);

        // 3. Send notification
        $result = $this->notificationService->sendNotification(
            'template_name',
            [['user_id' => $data['user_id'], 'user_type' => $data['user_type']]],
            $templateData,
            ['priority' => 'medium']
        );
    }
}
```

### **2. NotificationService**
- **Location**: `Modules/Notifications/app/Services/NotificationService/`
- **Purpose**: Orchestrate notification sending
- **Methods**: `sendNotification()`, `sendBulkNotification()`, `scheduleNotification()`

```php
public function sendNotification(
    string $templateName,
    array $recipients,
    array $data = [],
    array $options = []
): array {
    // 1. Get template
    // 2. Process recipients
    // 3. Create notification record
    // 4. Send via channels
    // 5. Return result
}
```

### **3. Channel Services**
- **EmailService**: `Modules/Notifications/app/Services/EmailService/`
- **PushService**: `Modules/Notifications/app/Services/PushService/`
- **SmsService**: `Modules/Notifications/app/Services/SmsService/`

### **4. Repository Pattern**
- **Interface**: `NotificationRepositoryInterface`
- **Implementation**: `NotificationRepository`
- **Purpose**: Data access abstraction

## 📋 Development Rules

### **1. Handler Development**
```php
// ✅ DO: Follow handler pattern
class MyEventHandler implements NotificationEventHandler
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(string $channel, array $data): void
    {
        // Validate data first
        if (!$this->validateData($data)) {
            Log::warning('Invalid data', ['data' => $data]);
            return;
        }

        // Process event
        $this->processEvent($data);
    }

    private function validateData(array $data): bool
    {
        return isset($data['required_field']);
    }

    private function processEvent(array $data): void
    {
        // Business logic here
    }
}
```

### **2. Template Data Preparation**
```php
// ✅ DO: Prepare clean template data
private function prepareTemplateData(array $kafkaData): array
{
    return [
        'user_name' => $this->ensureString($kafkaData['user_name'] ?? 'User'),
        'task_name' => $this->ensureString($kafkaData['task_name'] ?? 'Task'),
        'deadline' => $this->ensureString($kafkaData['deadline'] ?? ''),
        'app_name' => config('app.name', 'System'),
        'year' => date('Y')
    ];
}

private function ensureString($value): string
{
    if (is_array($value) || is_object($value)) {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    return (string) $value;
}
```

### **3. Error Handling**
```php
// ✅ DO: Comprehensive error handling
try {
    $result = $this->notificationService->sendNotification(...);
    
    if ($result['success']) {
        Log::info('Notification sent successfully', [
            'notification_id' => $result['notification_id'],
            'user_id' => $userId
        ]);
    } else {
        Log::error('Notification failed', [
            'error' => $result['error'],
            'user_id' => $userId
        ]);
    }
} catch (\Exception $e) {
    Log::error('Handler error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'data' => $data
    ]);
}
```

### **4. Kafka Integration**
```php
// ✅ DO: Publish events via Kafka
$this->kafkaProducer->send('event.topic', [
    'user_id' => $userId,
    'user_name' => $userName,
    'data' => $eventData
]);

// ✅ DO: Handle events via handlers
// Handlers are automatically resolved by KafkaRouterService
```

## 🎯 Template System

### **1. Template Variables**
```php
// Template: "Hello {{user_name}}, you have {{task_count}} tasks"
$data = [
    'user_name' => 'John Doe',
    'task_count' => 5
];
// Result: "Hello John Doe, you have 5 tasks"
```

### **2. Multi-Channel Templates**
```php
// Email template
"Subject: {{subject}}\n\nHello {{user_name}},\n\n{{content}}"

// Push template
"{{title}}: {{content}}"

// SMS template
"{{content}}"
```

## 🔧 Configuration

### **1. Kafka Handler Configuration**
```php
// config/kafka_handle.php
return [
    'handlers' => [
        'task.assigned' => TaskAssignedHandler::class,
        'task.completed' => TaskCompletedHandler::class,
        'user.registered' => UserRegisteredHandler::class,
    ]
];
```

### **2. Service Provider Registration**
```php
// NotificationsServiceProvider.php
public function register()
{
    $this->app->bind(EmailServiceInterface::class, EmailService::class);
    $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
}
```

## 📊 API Endpoints

### **1. Publish Event**
```http
POST /api/notifications/publish-event
{
    "topic": "task.assigned",
    "payload": {
        "user_id": 123,
        "task_name": "Complete Assignment"
    },
    "priority": "medium"
}
```

### **2. Send Notification**
```http
POST /api/notifications/send
{
    "template": "task_assigned",
    "recipients": [
        {
            "user_id": 123,
            "user_type": "student",
            "channels": ["email", "push"]
        }
    ],
    "data": {
        "task_name": "Complete Assignment",
        "deadline": "2024-12-31"
    }
}
```

## 🚀 Commands

### **Development Commands**
```bash
# Start Kafka consumer
php artisan kafka:consume

# Publish test event
php artisan kafka:produce task.assigned '{"user_id": 123, "task_name": "Test Task"}'

# Subscribe to events
php artisan notifications:subscribe
```

## ⚠️ Common Pitfalls

### **1. Data Validation**
```php
// ❌ DON'T: Skip validation
public function handle(string $channel, array $data): void
{
    $this->notificationService->sendNotification(...);
}

// ✅ DO: Always validate
public function handle(string $channel, array $data): void
{
    if (!isset($data['user_id'])) {
        Log::warning('Missing user_id', ['data' => $data]);
        return;
    }
    // ... rest of logic
}
```

### **2. Error Handling**
```php
// ❌ DON'T: Ignore errors
$result = $this->notificationService->sendNotification(...);

// ✅ DO: Handle errors properly
try {
    $result = $this->notificationService->sendNotification(...);
    if (!$result['success']) {
        Log::error('Notification failed', ['error' => $result['error']]);
    }
} catch (\Exception $e) {
    Log::error('Handler error', ['error' => $e->getMessage()]);
}
```

### **3. Template Data**
```php
// ❌ DON'T: Pass raw data to templates
$templateData = $kafkaData;

// ✅ DO: Clean and prepare data
$templateData = $this->prepareTemplateData($kafkaData);
```

## 🎯 Best Practices

1. **Always validate input data** in handlers
2. **Use proper logging** for debugging and monitoring
3. **Prepare clean template data** with string conversion
4. **Handle errors gracefully** with try-catch blocks
5. **Use dependency injection** for services
6. **Follow naming conventions** for handlers and templates
7. **Test handlers thoroughly** with various data scenarios
8. **Monitor notification delivery** status
9. **Use appropriate priority levels** for notifications
10. **Document handler requirements** and expected data format

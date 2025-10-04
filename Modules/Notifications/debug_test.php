<?php

/**
 * Debug test script để kiểm tra TaskAssignedHandler
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Modules\Notifications\app\Handlers\TaskHandle\TaskAssignedHandler;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Modules\Notifications\app\Repositories\NotificationRepository\NotificationRepository;
use Modules\Notifications\app\Services\EmailService\EmailService;
use Modules\Notifications\app\Services\PushService\PushService;
use Modules\Notifications\app\Services\SmsService\SmsService;

// Mock data từ Kafka
$kafkaData = [
    'user_id' => 1,
    'user_type' => 'lecturer',
    'user_name' => 'Thầy Test',
    'task_name' => 'Test Task',
    'assigner_name' => 'Admin',
    'task_description' => 'Test description',
    'deadline' => '2024-01-20 23:59:00',
    'task_url' => 'https://example.com/task/1'
];

echo "🧪 Testing TaskAssignedHandler...\n";
echo "📤 Kafka Data:\n";
echo json_encode($kafkaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

try {
    // Tạo mock services (không cần thực sự gửi)
    $notificationRepository = new class {
        public function findTemplateByName($name) {
            return (object)[
                'id' => 1,
                'name' => 'task_assigned',
                'title' => 'Công việc mới: {{{task_name}}}',
                'in_app_template' => 'Xin chào {{{user_name}}}, bạn có công việc mới: {{{task_name}}}',
                'email_template' => '<h1>Công việc mới: {{{task_name}}}</h1><p>Xin chào {{{user_name}}}</p>',
                'push_template' => 'Công việc mới: {{{task_name}}}',
                'category' => 'task',
                'priority' => 'medium',
                'channels' => ['email', 'push', 'in_app']
            ];
        }
        
        public function createNotification($data) {
            return (object)['id' => 1];
        }
        
        public function createUserNotification($data) {
            return (object)['id' => 1];
        }
    };
    
    $emailService = new class {
        public function send($userId, $userType, $content, $subject) {
            echo "📧 Email would be sent to user $userId: $subject\n";
        }
    };
    
    $pushService = new class {
        public function send($userId, $userType, $content) {
            echo "📱 Push notification would be sent to user $userId\n";
        }
    };
    
    $smsService = new class {
        public function send($userId, $userType, $content) {
            echo "📱 SMS would be sent to user $userId\n";
        }
    };
    
    // Tạo NotificationService với mock dependencies
    $notificationService = new NotificationService(
        $notificationRepository,
        $emailService,
        $pushService,
        $smsService
    );
    
    // Tạo TaskAssignedHandler
    $handler = new TaskAssignedHandler($notificationService);
    
    echo "🔄 Processing task assignment...\n";
    
    // Test prepareTemplateData method
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('prepareTemplateData');
    $method->setAccessible(true);
    
    $templateData = $method->invoke($handler, $kafkaData);
    
    echo "📋 Template Data:\n";
    echo json_encode($templateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Test renderTemplate method
    $notificationServiceReflection = new ReflectionClass($notificationService);
    $renderMethod = $notificationServiceReflection->getMethod('renderTemplate');
    $renderMethod->setAccessible(true);
    
    $template = 'Xin chào {{{user_name}}}, bạn có công việc mới: {{{task_name}}}';
    $rendered = $renderMethod->invoke($notificationService, $template, $templateData);
    
    echo "🎨 Rendered Template:\n";
    echo $rendered . "\n\n";
    
    echo "✅ Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Trace: " . $e->getTraceAsString() . "\n";
}


<?php

/**
 * Script test API Notifications
 * 
 * Cách sử dụng:
 * php test_api.php
 * php test_api.php --test=1
 * php test_api.php --all
 */

$baseUrl = 'http://localhost:8000/api/v1/events/publish';

// Test cases
$testCases = [
    [
        'name' => 'Task Assignment - Giao việc cho sinh viên',
        'data' => [
            'topic' => 'task.assigned',
            'payload' => [
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
            ],
            'priority' => 'medium',
            'key' => 'task_123_' . time()
        ]
    ],
    [
        'name' => 'Student Account Created',
        'data' => [
            'topic' => 'student_account_created',
            'payload' => [
                'user_id' => 124,
                'name' => 'Lê Thị D',
                'email' => 'lethid@example.com',
                'username' => 'lethid',
                'password' => 'hashed_password_123',
                'student_code' => 'SV2024001',
                'class_name' => 'CNTT2024A'
            ],
            'priority' => 'medium',
            'key' => 'student_124_' . time()
        ]
    ],
    [
        'name' => 'System Maintenance',
        'data' => [
            'topic' => 'system.maintenance',
            'payload' => [
                'start_time' => '2024-01-20 02:00:00',
                'end_time' => '2024-01-20 06:00:00',
                'reason' => 'Scheduled maintenance - Database optimization',
                'affected_services' => ['notifications', 'auth', 'tasks'],
                'maintenance_type' => 'scheduled'
            ],
            'priority' => 'critical',
            'key' => 'maintenance_' . time()
        ]
    ],
    [
        'name' => 'Password Reset',
        'data' => [
            'topic' => 'user.password_reset',
            'payload' => [
                'user_id' => 123,
                'user_type' => 'student',
                'email' => 'nguyenvana@example.com',
                'reset_code' => 'ABC12345',
                'reset_url' => 'https://system.com/reset-password?code=ABC12345'
            ],
            'priority' => 'high',
            'key' => 'reset_123_' . time()
        ]
    ],
    [
        'name' => 'Task Completed',
        'data' => [
            'topic' => 'task.completed',
            'payload' => [
                'task_id' => 'task_123',
                'user_id' => 123,
                'completion_notes' => 'Hoàn thành đúng hạn, code chạy tốt',
                'completion_time' => date('c')
            ],
            'priority' => 'low',
            'key' => 'task_complete_123_' . time()
        ]
    ],
    [
        'name' => 'Error Test - Missing topic',
        'data' => [
            'payload' => [
                'user_id' => 123,
                'user_name' => 'Test User'
            ],
            'priority' => 'medium'
        ],
        'expect_error' => true
    ]
];

function sendRequest($url, $data) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

function runTest($testCase, $baseUrl) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🧪 TEST: " . $testCase['name'] . "\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "📤 Request Data:\n";
    echo json_encode($testCase['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    $result = sendRequest($baseUrl, $testCase['data']);
    
    echo "📥 Response:\n";
    echo "HTTP Code: " . $result['http_code'] . "\n";
    
    if ($result['error']) {
        echo "❌ cURL Error: " . $result['error'] . "\n";
        return false;
    }
    
    $responseData = json_decode($result['response'], true);
    if ($responseData) {
        echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        if (isset($testCase['expect_error']) && $testCase['expect_error']) {
            if ($result['http_code'] >= 400) {
                echo "✅ Expected error received\n";
                return true;
            } else {
                echo "❌ Expected error but got success\n";
                return false;
            }
        } else {
            if ($result['http_code'] == 200 && isset($responseData['success']) && $responseData['success']) {
                echo "✅ Test passed\n";
                return true;
            } else {
                echo "❌ Test failed\n";
                return false;
            }
        }
    } else {
        echo "Raw response: " . $result['response'] . "\n";
        echo "❌ Invalid JSON response\n";
        return false;
    }
}

// Main execution
echo "🚀 NOTIFICATIONS API TEST SUITE\n";
echo "Base URL: " . $baseUrl . "\n";

$args = getopt('', ['test:', 'all']);

if (isset($args['test'])) {
    $testIndex = (int)$args['test'] - 1;
    if (isset($testCases[$testIndex])) {
        runTest($testCases[$testIndex], $baseUrl);
    } else {
        echo "❌ Test case not found. Available tests: 1-" . count($testCases) . "\n";
    }
} elseif (isset($args['all'])) {
    $passed = 0;
    $total = count($testCases);
    
    foreach ($testCases as $index => $testCase) {
        if (runTest($testCase, $baseUrl)) {
            $passed++;
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 TEST SUMMARY\n";
    echo str_repeat("=", 60) . "\n";
    echo "✅ Passed: $passed/$total\n";
    echo "❌ Failed: " . ($total - $passed) . "/$total\n";
    echo "Success Rate: " . round(($passed / $total) * 100, 2) . "%\n";
} else {
    echo "\nUsage:\n";
    echo "  php test_api.php --test=1     # Run specific test (1-" . count($testCases) . ")\n";
    echo "  php test_api.php --all        # Run all tests\n";
    echo "  php test_api.php              # Show this help\n";
    
    echo "\nAvailable tests:\n";
    foreach ($testCases as $index => $testCase) {
        echo "  " . ($index + 1) . ". " . $testCase['name'] . "\n";
    }
}

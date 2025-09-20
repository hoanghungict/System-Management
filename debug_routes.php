<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

echo "Testing TaskServiceProvider...\n";

try {
    $provider = new Modules\Task\app\Providers\TaskServiceProvider($app);
    echo "TaskServiceProvider created successfully\n";

    $provider->boot();
    echo "TaskServiceProvider booted successfully\n";

    // Test RouteConfig
    $config = Modules\Task\routes\RouteConfig::getAllRoutes();
    echo "RouteConfig loaded: " . (is_array($config) ? "YES" : "NO") . "\n";
    echo "Keys: " . implode(", ", array_keys($config)) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

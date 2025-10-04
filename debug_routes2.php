<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

echo "Testing RouteHelper...\n";

try {
    // Test RouteConfig
    $config = Modules\Task\routes\RouteConfig::getAllRoutes();
    echo "RouteConfig loaded: " . (is_array($config) ? "YES" : "NO") . "\n";
    echo "Keys: " . implode(", ", array_keys($config)) . "\n";

    // Test admin routes
    if (isset($config['admin'])) {
        echo "Admin routes config:\n";
        print_r($config['admin']);
    }

    // Test RouteHelper
    echo "\nTesting RouteHelper::registerRoutesGroup...\n";
    Modules\Task\routes\RouteHelper::registerRoutesGroup($config['admin']);
    echo "RouteHelper executed successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

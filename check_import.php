<?php
// Simple DB query without full Laravel bootstrap
$pdo = new PDO('mysql:host=localhost;dbname=system_management', 'root', '');
$stmt = $pdo->query('SELECT * FROM question_import_logs ORDER BY id DESC LIMIT 1');
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if ($log) {
    echo "=== LATEST IMPORT LOG ===\n";
    foreach ($log as $key => $value) {
        echo "$key: $value\n";
    }
} else {
    echo "No import logs found\n";
}

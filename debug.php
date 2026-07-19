<?php
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain; charset=UTF-8');

echo "=== ENV CHECK ===\n";
echo "TURSO_DATABASE_URL set: " . (getenv('TURSO_DATABASE_URL') ? 'YES' : 'NO - MISSING') . "\n";
echo "TURSO_AUTH_TOKEN set: " . (getenv('TURSO_AUTH_TOKEN') ? 'YES' : 'NO - MISSING') . "\n";
echo "URL used: " . turso_http_url() . "\n\n";

echo "=== CURL CHECK ===\n";
echo "curl extension loaded: " . (extension_loaded('curl') ? 'YES' : 'NO - THIS IS THE PROBLEM') . "\n\n";

echo "=== TABLE CREATE TEST ===\n";
$createResult = turso_execute("CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    plan TEXT,
    price TEXT,
    ip TEXT,
    time TEXT NOT NULL
)");
echo json_encode($createResult, JSON_PRETTY_PRINT) . "\n\n";

echo "=== INSERT TEST ===\n";
$insertResult = turso_execute(
    "INSERT INTO events (type, ip, time) VALUES (?, ?, ?)",
    ['debug_test', '127.0.0.1', date('Y-m-d H:i:s')]
);
echo json_encode($insertResult, JSON_PRETTY_PRINT) . "\n\n";

echo "=== SELECT TEST ===\n";
$rows = turso_query_rows("SELECT * FROM events ORDER BY id DESC LIMIT 5");
echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";

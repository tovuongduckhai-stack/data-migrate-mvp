<?php
// Ghi lại click "đăng ký gói" vào Turso
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

turso_ensure_table();

$plan = $_POST['plan'] ?? 'unknown';
$price = $_POST['price'] ?? '0';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$time = date('Y-m-d H:i:s');

$result = turso_execute(
    "INSERT INTO events (type, plan, price, ip, time) VALUES (?, ?, ?, ?, ?)",
    ['click', $plan, $price, $ip, $time]
);

if (isset($result['error'])) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $result['error']]);
} else {
    echo json_encode(['ok' => true]);
}

<?php
// Ghi lại click "đăng ký gói" vào file text - không cần DB
header('Content-Type: application/json');

$plan = $_POST['plan'] ?? 'unknown';
$price = $_POST['price'] ?? '0';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$time = date('Y-m-d H:i:s');

$logDir = __DIR__ . '/data';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/clicks.log';
$line = json_encode([
    'time'  => $time,
    'plan'  => $plan,
    'price' => $price,
    'ip'    => $ip,
], JSON_UNESCAPED_UNICODE) . "\n";

file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

echo json_encode(['ok' => true]);

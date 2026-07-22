<?php
// Ghi lai click "dang ky goi" thang vao Google Sheet
header('Content-Type: application/json');

define('SHEET_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbwb9OJfKg4-63zb1MGr7-Q4VFZ4RxDeztYFa1TprFREmCVtR5IKot8QGOoYs_aoxEWt/exec');

$plan = $_POST['plan'] ?? 'unknown';
$price = $_POST['price'] ?? '0';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$data = http_build_query([
    'type'  => 'click',
    'plan'  => $plan,
    'price' => $price,
    'ip'    => $ip,
]);

$opts = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $data,
        'timeout' => 5,
    ],
];
$context = stream_context_create($opts);
$result = @file_get_contents(SHEET_WEBHOOK_URL, false, $context);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
} else {
    echo json_encode(['ok' => true]);
}

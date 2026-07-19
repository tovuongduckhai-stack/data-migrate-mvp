<?php
// ============================================================
// Kết nối Turso qua HTTP API (Hrana over HTTP) - không cần driver riêng
// Đọc URL + token từ biến môi trường Render (TURSO_DATABASE_URL, TURSO_AUTH_TOKEN)
// ============================================================

function turso_http_url() {
    $url = getenv('TURSO_DATABASE_URL') ?: '';
    // đổi libsql:// thành https:// vì HTTP API dùng https
    return str_replace('libsql://', 'https://', $url);
}

function turso_token() {
    return getenv('TURSO_AUTH_TOKEN') ?: '';
}

// Gửi 1 hoặc nhiều câu SQL trong 1 request (pipeline)
function turso_execute($sql, $args = []) {
    $baseUrl = turso_http_url();
    $token = turso_token();

    if (!$baseUrl || !$token) {
        return ['error' => 'Missing TURSO_DATABASE_URL or TURSO_AUTH_TOKEN'];
    }

    $formattedArgs = array_map(function ($v) {
        return ['type' => 'text', 'value' => (string)$v];
    }, $args);

    $payload = [
        'requests' => [
            [
                'type' => 'execute',
                'stmt' => [
                    'sql' => $sql,
                    'args' => $formattedArgs,
                ],
            ],
        ],
    ];

    $ch = curl_init($baseUrl . '/v2/pipeline');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['error' => $err];
    }

    $data = json_decode($response, true);
    return $data;
}

// Lấy kết quả SELECT dưới dạng mảng associative dễ dùng
function turso_query_rows($sql, $args = []) {
    $data = turso_execute($sql, $args);
    $rows = [];

    if (empty($data['results'][0]['response']['result'])) {
        return $rows;
    }

    $result = $data['results'][0]['response']['result'];
    $cols = array_map(fn($c) => $c['name'], $result['cols'] ?? []);

    foreach ($result['rows'] ?? [] as $row) {
        $assoc = [];
        foreach ($row as $i => $cell) {
            $val = is_array($cell) ? ($cell['value'] ?? null) : $cell;
            $assoc[$cols[$i] ?? $i] = $val;
        }
        $rows[] = $assoc;
    }

    return $rows;
}

// Tạo bảng nếu chưa có - gọi hàm này ở đầu mỗi file cần dùng DB
function turso_ensure_table() {
    turso_execute("CREATE TABLE IF NOT EXISTS events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL,
        plan TEXT,
        price TEXT,
        ip TEXT,
        time TEXT NOT NULL
    )");
}

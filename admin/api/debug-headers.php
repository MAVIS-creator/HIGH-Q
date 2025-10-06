<?php
// admin/api/debug-headers.php
// Safe debug endpoint to inspect incoming headers, cookies, and POST payload for AJAX troubleshooting.
// This file should be removed after debugging.

// Note: intentionally NOT including auth.php so this endpoint can be used for debugging locally.
header('Content-Type: application/json; charset=utf-8');

$headers = [];
foreach (getallheaders() as $k => $v) {
    $headers[$k] = $v;
}

$body = file_get_contents('php://input');

$response = [
    'ok' => true,
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'headers' => $headers,
    'cookies' => $_COOKIE,
    'post' => $_POST,
    'raw_body' => $body,
    'server' => [
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'remote_user' => $_SERVER['REMOTE_USER'] ?? null,
        'auth_user' => $_SERVER['PHP_AUTH_USER'] ?? null,
        'auth_pw' => isset($_SERVER['PHP_AUTH_PW']) ? '**hidden**' : null,
    ],
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function hq_exam_json(string $status, string $message, $data = null, $errors = []): void
{
    http_response_code($status === 'ok' ? 200 : 400);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'errors' => $errors,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function hq_exam_require_method(string $method): void
{
    if (strcasecmp($_SERVER['REQUEST_METHOD'] ?? '', $method) !== 0) {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed.',
            'data' => null,
            'errors' => [
                'expected_method' => strtoupper($method),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function hq_exam_not_implemented(string $route, string $week = 'Week 2', array $meta = []): void
{
    http_response_code(501);
    echo json_encode([
        'status' => 'error',
        'message' => 'Exam portal endpoint scaffolded but not implemented yet.',
        'data' => [
            'route' => $route,
            'planned_implementation_window' => $week,
            'meta' => $meta,
        ],
        'errors' => [],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

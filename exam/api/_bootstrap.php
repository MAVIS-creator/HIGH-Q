<?php

declare(strict_types=1);

function hq_exam_prepare_session_storage(): void
{
    $configuredPath = (string)ini_get('session.save_path');
    $candidatePath = $configuredPath;

    if ($candidatePath !== '') {
        $parts = explode(';', $candidatePath);
        $candidatePath = trim((string)end($parts));
    }

    $isWritable = $candidatePath !== '' && is_dir($candidatePath) && is_writable($candidatePath);
    if ($isWritable) {
        return;
    }

    $fallbackPath = dirname(__DIR__, 2) . '/storage/framework/sessions/exam';
    if (!is_dir($fallbackPath)) {
        @mkdir($fallbackPath, 0775, true);
    }

    if (is_dir($fallbackPath) && is_writable($fallbackPath)) {
        session_save_path($fallbackPath);
    }
}

if (session_name() !== 'HIGHQEXAMSESSID') {
    session_name('HIGHQEXAMSESSID');
}

hq_exam_prepare_session_storage();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../public/config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const HQ_EXAM_SESSION_KEY = 'hq_exam_student_id';

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

function hq_exam_db(): PDO
{
    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        hq_exam_json('error', 'Exam portal database connection is unavailable.', null, [
            'database' => 'PDO connection was not initialized.',
        ]);
    }

    return $pdo;
}

function hq_exam_request_data(): array
{
    $contentType = strtolower(trim((string)($_SERVER['CONTENT_TYPE'] ?? '')));
    $raw = file_get_contents('php://input') ?: '';

    if (strpos($contentType, 'application/json') !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    if ($raw !== '') {
        parse_str($raw, $parsed);
        return is_array($parsed) ? $parsed : [];
    }

    return [];
}

function hq_exam_value(array $data, string $key): string
{
    return trim((string)($data[$key] ?? ''));
}

function hq_exam_table_exists(PDO $pdo, string $tableName): bool
{
    static $cache = [];
    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    $stmt->execute([$tableName]);
    $cache[$tableName] = (bool)$stmt->fetchColumn();

    return $cache[$tableName];
}

function hq_exam_require_setup(PDO $pdo, array $requiredTables = ['exam_students', 'exam_student_profiles']): void
{
    $missing = [];
    foreach ($requiredTables as $tableName) {
        if (!hq_exam_table_exists($pdo, $tableName)) {
            $missing[] = $tableName;
        }
    }

    if ($missing !== []) {
        http_response_code(503);
        echo json_encode([
            'status' => 'error',
            'message' => 'Exam portal database tables are not ready yet.',
            'data' => [
                'required_tables' => $requiredTables,
            ],
            'errors' => [
                'missing_tables' => $missing,
                'next_step' => 'Run exam/database/2026-05-12-exam-portal-schema.sql before testing auth endpoints.',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function hq_exam_student_summary(PDO $pdo, array $student): array
{
    $profile = [
        'full_name' => null,
        'phone' => null,
        'class_level' => null,
        'school_name' => null,
        'avatar_path' => null,
    ];

    if (hq_exam_table_exists($pdo, 'exam_student_profiles')) {
        $stmt = $pdo->prepare('SELECT full_name, phone, class_level, school_name, avatar_path FROM exam_student_profiles WHERE student_id = ? LIMIT 1');
        $stmt->execute([(int)$student['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($row)) {
            $profile = array_merge($profile, $row);
        }
    }

    $subscription = null;
    if (hq_exam_table_exists($pdo, 'exam_student_subscriptions') && hq_exam_table_exists($pdo, 'exam_subscription_plans')) {
        $stmt = $pdo->prepare(
            'SELECT esp.status, esp.starts_at, esp.expires_at, p.slug AS plan_slug, p.name AS plan_name
             FROM exam_student_subscriptions esp
             INNER JOIN exam_subscription_plans p ON p.id = esp.plan_id
             WHERE esp.student_id = ?
             ORDER BY
                 CASE esp.status
                     WHEN "active" THEN 0
                     WHEN "pending" THEN 1
                     WHEN "expired" THEN 2
                     ELSE 3
                 END,
                 esp.id DESC
             LIMIT 1'
        );
        $stmt->execute([(int)$student['id']]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    return [
        'id' => (int)$student['id'],
        'email' => (string)$student['email'],
        'status' => (string)$student['status'],
        'email_verified' => !empty($student['email_verified_at']),
        'email_verified_at' => $student['email_verified_at'] ?? null,
        'last_login_at' => $student['last_login_at'] ?? null,
        'created_at' => $student['created_at'] ?? null,
        'profile' => $profile,
        'subscription' => $subscription,
    ];
}

function hq_exam_login_student(PDO $pdo, array $student): void
{
    $_SESSION[HQ_EXAM_SESSION_KEY] = (int)$student['id'];
    $_SESSION['hq_exam_student_email'] = (string)$student['email'];
    $_SESSION['hq_exam_last_auth_at'] = time();

    if (hq_exam_table_exists($pdo, 'exam_sessions')) {
        $stmt = $pdo->prepare(
            'INSERT INTO exam_sessions (session_key, student_id, ip_address, user_agent, last_seen_at, created_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                student_id = VALUES(student_id),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                last_seen_at = NOW()'
        );
        $stmt->execute([
            session_id(),
            (int)$student['id'],
            substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }
}

function hq_exam_logout_student(PDO $pdo): void
{
    if (hq_exam_table_exists($pdo, 'exam_sessions') && session_id() !== '') {
        $stmt = $pdo->prepare('DELETE FROM exam_sessions WHERE session_key = ?');
        $stmt->execute([session_id()]);
    }

    unset(
        $_SESSION[HQ_EXAM_SESSION_KEY],
        $_SESSION['hq_exam_student_email'],
        $_SESSION['hq_exam_last_auth_at']
    );
}

function hq_exam_current_student(PDO $pdo): ?array
{
    $studentId = (int)($_SESSION[HQ_EXAM_SESSION_KEY] ?? 0);
    if ($studentId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, email, status, email_verified_at, last_login_at, created_at
         FROM exam_students
         WHERE id = ? AND status = "active"
         LIMIT 1'
    );
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        hq_exam_logout_student($pdo);
        return null;
    }

    if (hq_exam_table_exists($pdo, 'exam_sessions') && session_id() !== '') {
        $stmt = $pdo->prepare('UPDATE exam_sessions SET last_seen_at = NOW() WHERE session_key = ? AND student_id = ?');
        $stmt->execute([session_id(), $studentId]);
    }

    return $student;
}

function hq_exam_require_student(PDO $pdo): array
{
    $student = hq_exam_current_student($pdo);
    if (!$student) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Exam student authentication is required.',
            'data' => [
                'authenticated' => false,
            ],
            'errors' => [
                'auth' => 'Please log in to continue.',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $student;
}

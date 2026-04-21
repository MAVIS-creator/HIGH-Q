<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ai_assistant.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requirePermission('ai_assistant');
} catch (Throwable $e) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$csrf = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verifyToken('ai_assistant_api', (string)$csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$question = trim((string)($_POST['question'] ?? ''));
$context = trim((string)($_POST['context'] ?? ''));

if ($question === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Question is required']);
    exit;
}

if (mb_strlen($question) > 3000) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Question is too long']);
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$roleName = (string)($_SESSION['user']['role_name'] ?? 'Admin');
$allowedSlugs = getRoleMenuPermissions($pdo, $roleId);

// Restrict very sensitive context fields from being sent to external AI providers.
$context = preg_replace('/(password|secret|api[_-]?key|token)\s*[:=].*/i', '[REDACTED]', $context);

try {
    $result = ai_assistant_query($question, $context, $roleName, $allowedSlugs);

    logAction($pdo, $userId, 'ai_assistant_query', [
        'provider' => $result['provider'] ?? null,
        'model' => $result['model'] ?? null,
        'role' => $roleName,
        'allowed_modules' => $allowedSlugs,
    ]);

    echo json_encode([
        'status' => 'ok',
        'answer' => $result['answer'],
        'provider' => $result['provider'] ?? null,
        'model' => $result['model'] ?? null,
        'safety' => [
            'requires_confirmation_for_write' => true,
            'rbac_enforced' => true,
        ],
    ]);
} catch (Throwable $e) {
    logAction($pdo, $userId, 'ai_assistant_error', [
        'message' => $e->getMessage(),
        'role' => $roleName,
    ]);

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'AI assistant is temporarily unavailable. Please try again shortly.',
    ]);
}

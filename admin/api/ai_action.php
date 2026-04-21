<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

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
if (!verifyToken('ai_action_api', (string)$csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$proposal = trim((string)($_POST['proposal'] ?? ''));
$actionType = trim((string)($_POST['action_type'] ?? 'manual_review'));
$context = trim((string)($_POST['context'] ?? ''));
$confirmed = !empty($_POST['confirmed']);

if ($proposal === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Proposal text is required']);
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);

if (!$confirmed) {
    logAction($pdo, $userId, 'ai_action_proposed', [
        'action_type' => $actionType,
        'proposal' => $proposal,
    ]);

    echo json_encode([
        'status' => 'ok',
        'requires_confirmation' => true,
        'message' => 'Review the proposal and confirm to queue it for execution review.',
        'proposal' => $proposal,
        'action_type' => $actionType,
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO ai_action_queue (user_id, action_type, proposal, context, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'queued', NOW(), NOW())");
    $stmt->execute([$userId, $actionType, $proposal, $context]);

    $queueId = (int)$pdo->lastInsertId();

    logAction($pdo, $userId, 'ai_action_queued', [
        'queue_id' => $queueId,
        'action_type' => $actionType,
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => 'ok',
        'message' => 'Proposal queued for review.',
        'queue_id' => $queueId,
        'action_type' => $actionType,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    logAction($pdo, $userId, 'ai_action_queue_error', [
        'message' => $e->getMessage(),
        'action_type' => $actionType,
    ]);

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to queue proposal right now.']);
}
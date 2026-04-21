<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requirePermission('ai_queue');
} catch (Throwable $e) {
    try {
        requirePermission('ai_assistant');
    } catch (Throwable $inner) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$csrf = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verifyToken('ai_action_execute_api', (string)$csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$queueId = (int)($_POST['queue_id'] ?? 0);
$note = trim((string)($_POST['note'] ?? ''));
$userId = (int)($_SESSION['user']['id'] ?? 0);

if ($queueId <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid queue item']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM ai_action_queue WHERE id = ? LIMIT 1');
    $stmt->execute([$queueId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Queue item not found']);
        exit;
    }

    if (($item['status'] ?? '') !== 'approved') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Only approved items can be executed']);
        exit;
    }

    // In this pass we only transition the proposal to executed and log it.
    // Actual business mutations remain a manual/admin-reviewed follow-up step.
    try {
        $upd = $pdo->prepare('UPDATE ai_action_queue SET status = ?, execution_note = ?, executed_by = ?, executed_at = NOW(), updated_at = NOW() WHERE id = ?');
        $upd->execute(['executed', $note !== '' ? $note : null, $userId > 0 ? $userId : null, $queueId]);
    } catch (Throwable $schemaEx) {
        // Backward compatibility before the notes migration is applied.
        $upd = $pdo->prepare('UPDATE ai_action_queue SET status = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute(['executed', $queueId]);
    }

    logAction($pdo, $userId, 'ai_action_executed', [
        'queue_id' => $queueId,
        'action_type' => $item['action_type'] ?? null,
        'note' => $note,
    ]);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Queue item marked as executed.',
        'queue_id' => $queueId,
        'decision' => 'executed',
    ]);
} catch (Throwable $e) {
    logAction($pdo, $userId, 'ai_action_execute_error', [
        'queue_id' => $queueId,
        'message' => $e->getMessage(),
    ]);

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to execute queue item right now.']);
}
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
if (!verifyToken('ai_action_review_api', (string)$csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$queueId = (int)($_POST['queue_id'] ?? 0);
$decision = strtolower(trim((string)($_POST['decision'] ?? '')));
$note = trim((string)($_POST['note'] ?? ''));
$markExecuted = !empty($_POST['mark_executed']);
$userId = (int)($_SESSION['user']['id'] ?? 0);

if ($queueId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid queue item or decision']);
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

    try {
        $upd = $pdo->prepare('UPDATE ai_action_queue SET status = ?, review_note = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?');
        $upd->execute([$decision, $note !== '' ? $note : null, $userId > 0 ? $userId : null, $queueId]);
    } catch (Throwable $schemaEx) {
        // Backward compatibility before the notes migration is applied.
        $upd = $pdo->prepare('UPDATE ai_action_queue SET status = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute([$decision, $queueId]);
    }

    if ($decision === 'approved' && $markExecuted) {
        try {
            $upd2 = $pdo->prepare('UPDATE ai_action_queue SET status = ?, execution_note = ?, executed_by = ?, executed_at = NOW(), updated_at = NOW() WHERE id = ?');
            $upd2->execute(['executed', $note !== '' ? $note : null, $userId > 0 ? $userId : null, $queueId]);
        } catch (Throwable $schemaEx2) {
            $upd2 = $pdo->prepare('UPDATE ai_action_queue SET status = ?, updated_at = NOW() WHERE id = ?');
            $upd2->execute(['executed', $queueId]);
        }
        $decision = 'executed';
    }

    logAction($pdo, $userId, 'ai_action_review_' . $decision, [
        'queue_id' => $queueId,
        'action_type' => $item['action_type'] ?? null,
        'note' => $note,
    ]);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Queue item marked as ' . $decision . '.',
        'queue_id' => $queueId,
        'decision' => $decision,
    ]);
} catch (Throwable $e) {
    logAction($pdo, $userId, 'ai_action_review_error', [
        'queue_id' => $queueId,
        'message' => $e->getMessage(),
    ]);

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to update queue item right now.']);
}

<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$sessionId = session_id() ?: null;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$targetType = $_REQUEST['target_type'] ?? '';
$id = intval($_REQUEST['id'] ?? 0);
$vote = intval($_REQUEST['vote'] ?? 0); // 1 or -1

if (!in_array($targetType, ['question', 'reply'], true) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid target']);
    exit;
}

if ($method !== 'POST' && $method !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Helper to compute score and current user vote
    $scoreQuery = ($targetType === 'question')
        ? 'SELECT COALESCE(SUM(vote),0) FROM forum_votes WHERE question_id = ?'
        : 'SELECT COALESCE(SUM(vote),0) FROM forum_votes WHERE reply_id = ?';

    $userVoteQuery = ($targetType === 'question')
        ? 'SELECT vote FROM forum_votes WHERE question_id = ? AND (session_id = ? OR ip = ?) LIMIT 1'
        : 'SELECT vote FROM forum_votes WHERE reply_id = ? AND (session_id = ? OR ip = ?) LIMIT 1';

    if ($method === 'GET') {
        $stmt = $pdo->prepare($scoreQuery);
        $stmt->execute([$id]);
        $score = (int)$stmt->fetchColumn();

        $uv = $pdo->prepare($userVoteQuery);
        $uv->execute([$id, $sessionId, $ip]);
        $userVote = (int)($uv->fetchColumn() ?: 0);

        echo json_encode(['status' => 'ok', 'score' => $score, 'user_vote' => $userVote]);
        exit;
    }

    if (!in_array($vote, [1, -1], true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid vote value']);
        exit;
    }

    $pdo->beginTransaction();

    // Check existing
    $chk = $pdo->prepare($userVoteQuery);
    $chk->execute([$id, $sessionId, $ip]);
    $existing = $chk->fetchColumn();

    if ($existing !== false && (int)$existing === $vote) {
        // Same vote -> remove (toggle off)
        $delSql = ($targetType === 'question')
            ? 'DELETE FROM forum_votes WHERE question_id = ? AND (session_id = ? OR ip = ?)'
            : 'DELETE FROM forum_votes WHERE reply_id = ? AND (session_id = ? OR ip = ?)';
        $del = $pdo->prepare($delSql);
        $del->execute([$id, $sessionId, $ip]);
        $userVote = 0;
    } else {
        // Upsert vote
        $delSql = ($targetType === 'question')
            ? 'DELETE FROM forum_votes WHERE question_id = ? AND (session_id = ? OR ip = ?)'
            : 'DELETE FROM forum_votes WHERE reply_id = ? AND (session_id = ? OR ip = ?)';
        $del = $pdo->prepare($delSql);
        $del->execute([$id, $sessionId, $ip]);

        $insSql = ($targetType === 'question')
            ? 'INSERT INTO forum_votes (question_id, vote, session_id, ip) VALUES (?, ?, ?, ?)'
            : 'INSERT INTO forum_votes (reply_id, vote, session_id, ip) VALUES (?, ?, ?, ?)';
        $ins = $pdo->prepare($insSql);
        $ins->execute([$id, $vote, $sessionId, $ip]);
        $userVote = $vote;
    }

    $pdo->commit();

    $stmt = $pdo->prepare($scoreQuery);
    $stmt->execute([$id]);
    $score = (int)$stmt->fetchColumn();

    echo json_encode(['status' => 'ok', 'score' => $score, 'user_vote' => $userVote]);
    exit;
} catch (Throwable $e) {
    try { $pdo->rollBack(); } catch (Throwable $_) {}
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}

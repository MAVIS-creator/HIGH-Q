<?php
// public/api/comments.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';

// Expect POST from public comment form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','message'=>'Method not allowed']);
    exit;
}

$postId = intval($_POST['post_id'] ?? 0);
$parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$content = trim($_POST['content'] ?? '');

if (!$postId || $content === '') {
    echo json_encode(['status'=>'error','message'=>'Missing required fields']);
    exit;
}

try {
    // Determine comment moderation setting (default to true if unknown)
    $commentModeration = true;
    try {
        $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
        $ss = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ss && isset($ss['comment_moderation'])) {
            $commentModeration = (bool)$ss['comment_moderation'];
        } else {
            // fallback to legacy settings
            $stmt2 = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
            $stmt2->execute(['system_settings']);
            $val = $stmt2->fetchColumn();
            $j = $val ? json_decode($val, true) : [];
            if (isset($j['content']['comment_moderation'])) $commentModeration = (bool)$j['content']['comment_moderation'];
        }
    } catch (Throwable $e) {
        // ignore and default to moderation on
    }

    $status = $commentModeration ? 'pending' : 'approved';

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, parent_id, user_id, name, email, content, status, created_at) VALUES (?, ?, NULL, ?, ?, ?, ?, NOW())");
    $stmt->execute([$postId, $parentId, $name ?: null, $email ?: null, $content, $status]);
    $id = $pdo->lastInsertId();
    if ($status === 'pending') {
        echo json_encode(['status'=>'ok','message'=>'Comment submitted and awaiting moderation']);
    } else {
        // fetch the inserted row to return minimal data
        $q = $pdo->prepare('SELECT id, post_id, parent_id, name, content, created_at FROM comments WHERE id = ? LIMIT 1');
        $q->execute([$id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['status'=>'ok','message'=>'Comment submitted','comment'=>$row]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB error']);
}

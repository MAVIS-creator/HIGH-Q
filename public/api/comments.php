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
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, parent_id, user_id, name, email, content, status, created_at) VALUES (?, ?, NULL, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$postId, $parentId, $name ?: null, $email ?: null, $content]);
    echo json_encode(['status'=>'ok','message'=>'Comment submitted and awaiting moderation']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB error']);
}

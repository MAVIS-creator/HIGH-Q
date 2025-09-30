<?php
// public/api/forum_reply.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit;
}

$question_id = intval($_POST['question_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$content = trim($_POST['content'] ?? '');
if (!$question_id || $content === '') { echo json_encode(['status'=>'error','message'=>'Missing fields']); exit; }

try {
  // create a replies table if not present is outside scope; assume a forum_replies table exists with question_id, name, content, created_at
  $stmt = $pdo->prepare('INSERT INTO forum_replies (question_id, name, content, created_at) VALUES (?, ?, ?, NOW())');
  $stmt->execute([$question_id, $name ?: 'Anonymous', $content]);
  echo json_encode(['status'=>'ok','id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
  http_response_code(500); echo json_encode(['status'=>'error','message'=>'DB error']);
}

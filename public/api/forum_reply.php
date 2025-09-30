<?php
// public/api/forum_reply.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit;
}

$question_id = intval($_POST['question_id'] ?? 0);
$parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$name = trim($_POST['name'] ?? '');
$content = trim($_POST['content'] ?? '');
if (!$question_id || $content === '') { echo json_encode(['status'=>'error','message'=>'Missing fields']); exit; }

try {
  // insert reply, allowing optional parent_id for nested replies
  if ($parent_id) {
    $stmt = $pdo->prepare('INSERT INTO forum_replies (question_id, parent_id, name, content, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$question_id, $parent_id, $name ?: 'Anonymous', $content]);
  } else {
    $stmt = $pdo->prepare('INSERT INTO forum_replies (question_id, name, content, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$question_id, $name ?: 'Anonymous', $content]);
  }
  $id = $pdo->lastInsertId();
  // fetch the inserted row
  $q = $pdo->prepare('SELECT id, question_id, parent_id, name, content, created_at FROM forum_replies WHERE id = ? LIMIT 1');
  $q->execute([$id]);
  $row = $q->fetch(PDO::FETCH_ASSOC);
  echo json_encode(['status'=>'ok','reply' => $row]);
} catch (Exception $e) {
  http_response_code(500); echo json_encode(['status'=>'error','message'=>'DB error']);
}

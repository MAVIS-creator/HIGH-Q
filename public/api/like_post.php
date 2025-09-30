<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit; }
$postId = intval($_POST['post_id'] ?? 0);
if (!$postId) { echo json_encode(['status'=>'error','message'=>'Missing post_id']); exit; }

try {
  // naive: track by IP in post_likes table if exists
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  try {
    $pdo->beginTransaction();
    // try to insert a like row (ensure table exists)
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_likes (id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, ip VARCHAR(64), created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY post_ip (post_id, ip)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
  } catch (Throwable $e) { /* ignore */ }

  $ins = $pdo->prepare('INSERT IGNORE INTO post_likes (post_id, ip) VALUES (?, ?)');
  $ins->execute([$postId, $ip]);
  $count = $pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = ?');
  $count->execute([$postId]);
  $c = (int)$count->fetchColumn();
  $pdo->commit();
  echo json_encode(['status'=>'ok','count'=>$c]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

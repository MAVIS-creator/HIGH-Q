<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/db.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
if (!$token && !$email) { echo json_encode(['status'=>'error','message'=>'Missing token or email']); exit; }
try {
  if ($token) {
    $stmt = $pdo->prepare('DELETE FROM newsletter_subscribers WHERE unsubscribe_token = ?');
    $stmt->execute([$token]);
  } else {
    $stmt = $pdo->prepare('DELETE FROM newsletter_subscribers WHERE email = ?');
    $stmt->execute([$email]);
  }
  echo json_encode(['status'=>'ok','message'=>'Unsubscribed']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'Failed to unsubscribe']);
}

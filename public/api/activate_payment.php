<?php
// public/api/activate_payment.php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$ref = trim($_GET['ref'] ?? $_POST['ref'] ?? '');
if (!$ref) { echo json_encode(['status'=>'error','message'=>'Missing ref']); exit; }

try {
  $stmt = $pdo->prepare('SELECT id, status, activated_at FROM payments WHERE reference = ? LIMIT 1');
  $stmt->execute([$ref]);
  $p = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$p) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }

  // If already activated, return the existing activated_at
  if (!empty($p['activated_at'])) {
    $ts = strtotime($p['activated_at']);
    echo json_encode(['status'=>'ok','activated_at'=>$p['activated_at'],'activated_ts'=>$ts,'payment_id'=>intval($p['id'])]);
    exit;
  }

  // Otherwise set activated_at = NOW() (idempotent)
  $upd = $pdo->prepare('UPDATE payments SET activated_at = NOW(), updated_at = NOW() WHERE id = ?');
  $upd->execute([ $p['id'] ]);

  // Fetch the updated row to get the canonical timestamp
  $stmt2 = $pdo->prepare('SELECT activated_at FROM payments WHERE id = ? LIMIT 1');
  $stmt2->execute([ $p['id'] ]);
  $row = $stmt2->fetch(PDO::FETCH_ASSOC);
  $activated = $row['activated_at'] ?? null;
  $ts = $activated ? strtotime($activated) : null;

  echo json_encode(['status'=>'ok','activated_at'=>$activated,'activated_ts'=>$ts,'payment_id'=>intval($p['id'])]);
  exit;
} catch (Throwable $e) {
  error_log('activate_payment error: ' . $e->getMessage());
  echo json_encode(['status'=>'error','message'=>'Server error']);
  exit;
}

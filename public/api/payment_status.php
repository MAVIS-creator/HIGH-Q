<?php
// public/api/payment_status.php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$ref = $_GET['ref'] ?? '';
if (!$ref) { echo json_encode(['status'=>'error','message'=>'Missing ref']); exit; }

// Fetch payment including activated_at to allow server-side expiry of the 30-minute window
$stmt = $pdo->prepare('SELECT id, status, reference, receipt_path, activated_at FROM payments WHERE reference = ? LIMIT 1');
$stmt->execute([$ref]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }

// If payment has been activated, enforce the 30-minute payment window server-side
if (!empty($p['activated_at']) && in_array($p['status'], ['pending','sent'])) {
	$activatedTs = strtotime($p['activated_at']);
	if ($activatedTs !== false) {
		$now = time();
		if ($now - $activatedTs > (30 * 60)) {
			// mark expired
			try {
				$upd = $pdo->prepare('UPDATE payments SET status = "expired", updated_at = NOW() WHERE id = ?');
				$upd->execute([$p['id']]);
				$p['status'] = 'expired';
			} catch (Throwable $e) {
				// ignore DB errors but return current status
			}
		}
	}
}

echo json_encode(['status'=>'ok','payment'=>['id'=>$p['id'],'status'=>$p['status'],'reference'=>$p['reference'],'receipt_path'=>$p['receipt_path']]]);
?>

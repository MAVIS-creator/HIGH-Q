<?php
// public/api/payment_status.php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$ref = $_GET['ref'] ?? '';
if (!$ref) { echo json_encode(['status'=>'error','message'=>'Missing ref']); exit; }
$stmt = $pdo->prepare('SELECT id, status, reference, receipt_path, registration_type, activated_at FROM payments WHERE reference = ? LIMIT 1');
$stmt->execute([$ref]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }

// If payment has been activated (user opened link), enforce 30-minute payment window
try {
	if (!empty($p['activated_at']) && $p['status'] === 'pending') {
		$actTs = strtotime($p['activated_at']);
		if ($actTs !== false && time() > ($actTs + (30 * 60))) {
			// mark expired
			$upd = $pdo->prepare('UPDATE payments SET status = "expired", updated_at = NOW() WHERE id = ?');
			$upd->execute([ $p['id'] ]);
			$p['status'] = 'expired';
		}
	}
} catch (Throwable $e) {
	// ignore errors here but log for debugging
	error_log('payment_status enforce window error: ' . $e->getMessage());
}

echo json_encode(['status'=>'ok','payment'=>['id'=>$p['id'],'status'=>$p['status'],'reference'=>$p['reference'],'receipt_path'=>$p['receipt_path'],'registration_type'=>$p['registration_type'] ?? null,'activated_at'=>$p['activated_at'] ?? null]]);
?>

<?php
// public/api/payment_status.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
header('Content-Type: application/json');
$ref = $_GET['ref'] ?? '';
if (!$ref) { echo json_encode(['status'=>'error','message'=>'Missing ref']); exit; }
$stmt = $pdo->prepare('SELECT id, status, reference, receipt_path FROM payments WHERE reference = ? LIMIT 1');
$stmt->execute([$ref]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
// Normalize receipt_path to a public URL when possible
$receipt = $p['receipt_path'] ?? null;
if (!empty($receipt) && function_exists('hq_public_url')) {
	$receipt = hq_public_url($receipt);
}
echo json_encode(['status'=>'ok','payment'=>['id'=>$p['id'],'status'=>$p['status'],'reference'=>$p['reference'],'receipt_path'=>$receipt]]);
?>

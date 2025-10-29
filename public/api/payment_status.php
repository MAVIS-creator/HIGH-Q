<?php
// public/api/payment_status.php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$ref = $_GET['ref'] ?? '';
if (!$ref) { echo json_encode(['status'=>'error','message'=>'Missing ref']); exit; }
$stmt = $pdo->prepare('SELECT id, status, reference, receipt_path, registration_type FROM payments WHERE reference = ? LIMIT 1');
$stmt->execute([$ref]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
echo json_encode(['status'=>'ok','payment'=>['id'=>$p['id'],'status'=>$p['status'],'reference'=>$p['reference'],'receipt_path'=>$p['receipt_path'],'registration_type'=>$p['registration_type'] ?? null]]);
?>

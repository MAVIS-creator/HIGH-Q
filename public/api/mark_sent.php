<?php
// public/api/mark_sent.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit; }
$token = $_POST['_csrf'] ?? '';
if (!verifyToken('signup_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }
$payment_id = intval($_POST['payment_id'] ?? 0);
$payer_name = trim($_POST['payer_name'] ?? '');
$payer_number = trim($_POST['payer_number'] ?? '');
$payer_bank = trim($_POST['payer_bank'] ?? '');
if (!$payment_id || !$payer_name || !$payer_number) { echo json_encode(['status'=>'error','message'=>'Missing fields']); exit; }
try {
    $upd = $pdo->prepare('UPDATE payments SET payer_account_name = ?, payer_account_number = ?, payer_bank_name = ?, status = ?, updated_at = NOW() WHERE id = ?');
    $ok = $upd->execute([$payer_name, $payer_number, $payer_bank, 'sent', $payment_id]);
    if ($ok) {
        echo json_encode(['status'=>'ok','message'=>'Recorded']); exit;
    }
    echo json_encode(['status'=>'error','message'=>'DB error']); exit;
} catch (Throwable $e) { echo json_encode(['status'=>'error','message'=>$e->getMessage()]); exit; }
?>
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
    // verify payment exists
    $stmt = $pdo->prepare('SELECT id, reference, status FROM payments WHERE id = ? LIMIT 1');
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$payment) { echo json_encode(['status'=>'error','message'=>'Payment not found']); exit; }

    // verify matches session (basic anti-tamper)
    if (!empty($_SESSION['last_payment_id']) && intval($_SESSION['last_payment_id']) !== intval($payment_id)) {
        echo json_encode(['status'=>'error','message'=>'Payment does not match your session']); exit;
    }

    // Update payer details and set status to 'sent' only if not already marked sent/confirmed
    if ($payment['status'] === 'sent' || $payment['status'] === 'confirmed') {
        // idempotent: already recorded
        echo json_encode(['status'=>'ok','message'=>'Already recorded','payment'=>['id'=>$payment_id,'status'=>$payment['status']]]); exit;
    }

    $upd = $pdo->prepare('UPDATE payments SET payer_account_name = ?, payer_account_number = ?, payer_bank_name = ?, status = ?, updated_at = NOW() WHERE id = ?');
    $ok = $upd->execute([$payer_name, $payer_number, $payer_bank, 'sent', $payment_id]);
    if ($ok) {
        // log audit
        if (function_exists('logAction')) {
            try { logAction($pdo, 0, 'mark_sent', ['payment_id'=>$payment_id,'payer_name'=>$payer_name,'payer_number'=>$payer_number,'payer_bank'=>$payer_bank,'reference'=>$payment['reference']]); } catch(Throwable $e){}
        }
        echo json_encode(['status'=>'ok','message'=>'Recorded','payment'=>['id'=>$payment_id,'reference'=>$payment['reference'],'status'=>'sent','payer_name'=>$payer_name,'payer_number'=>$payer_number,'payer_bank'=>$payer_bank]]); exit;
    }
    echo json_encode(['status'=>'error','message'=>'DB error']); exit;
} catch (Throwable $e) { echo json_encode(['status'=>'error','message'=>$e->getMessage()]); exit; }
?>
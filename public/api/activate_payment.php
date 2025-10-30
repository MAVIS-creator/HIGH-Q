<?php
// public/api/activate_payment.php
// Record that a user has opened/tapped a payment link (activated_at)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/csrf.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['status'=>'error','message'=>'Invalid method']); exit; }

$ref = $_POST['ref'] ?? '';
$payment_id = intval($_POST['payment_id'] ?? 0);
$token = $_POST['_csrf'] ?? '';

if (!verifyToken('signup_form', $token) && !verifyToken('payments_form', $token)) {
    echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit;
}

if (!$ref && !$payment_id) { echo json_encode(['status'=>'error','message'=>'Missing reference']); exit; }

try {
    if ($payment_id) {
        $stmt = $pdo->prepare('SELECT id, status, activated_at FROM payments WHERE id = ? LIMIT 1');
        $stmt->execute([$payment_id]);
    } else {
        $stmt = $pdo->prepare('SELECT id, status, activated_at FROM payments WHERE reference = ? LIMIT 1');
        $stmt->execute([$ref]);
    }
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) { echo json_encode(['status'=>'error','message'=>'Payment not found']); exit; }

    // only set activated_at for pending payments (do not activate already confirmed/expired)
    if (in_array($p['status'], ['pending','sent'])) {
        if (empty($p['activated_at'])) {
            $u = $pdo->prepare('UPDATE payments SET activated_at = NOW(), updated_at = NOW() WHERE id = ?');
            $ok = $u->execute([$p['id']]);
            if ($ok) {
                echo json_encode(['status'=>'ok','message'=>'activated','activated'=>true]); exit;
            }
        }
        echo json_encode(['status'=>'ok','message'=>'already_active','activated'=>!empty($p['activated_at'])]); exit;
    }

    // If payment already confirmed/expired, return that status
    echo json_encode(['status'=>'ok','message'=>'not_activated','status_value'=>$p['status']]); exit;

} catch (Throwable $e) {
    error_log('activate_payment error: ' . $e->getMessage());
    echo json_encode(['status'=>'error','message'=>'Server error']); exit;
}

?>

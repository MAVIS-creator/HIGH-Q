<?php
// admin/api/resend_payment_link.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if (empty($_SESSION['user'])) { echo json_encode(['status'=>'error','message'=>'Not authenticated']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['status'=>'error','message'=>'Invalid method']); exit; }

$token = $_POST['_csrf'] ?? '';
if (!verifyToken('payments_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }

$paymentId = intval($_POST['payment_id'] ?? 0);
if (!$paymentId) { echo json_encode(['status'=>'error','message'=>'Missing payment id']); exit; }

try {
    $stmt = $pdo->prepare('SELECT id, amount, reference, metadata FROM payments WHERE id = ? LIMIT 1');
    $stmt->execute([$paymentId]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) { echo json_encode(['status'=>'error','message'=>'Not found']); exit; }

    $meta = [];
    if (!empty($p['metadata'])) { $meta = json_decode($p['metadata'], true) ?: []; }
    $emailTo = $meta['email_to'] ?? '';
    $msg = $meta['message'] ?? '';
    if (empty($emailTo) || !filter_var($emailTo, FILTER_VALIDATE_EMAIL)) { echo json_encode(['status'=>'error','message'=>'No valid email on record']); exit; }

    // Use app_url() helper so deployment base path and APP_URL are respected (avoids hardcoded host/path)
    // Prefer friendly/pay route so APP_URL and subfolder installs are consistent
    $link = app_url('pay/' . urlencode($p['reference']));
    $subject = 'Payment link — HIGH Q SOLID ACADEMY';
    $html = '<p>Hi,</p><p>Please use the following secure link to complete your payment of ₦' . number_format($p['amount'],2) . ':</p>';
    $html .= '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
    if (!empty($msg)) $html .= '<p>Message: ' . nl2br(htmlspecialchars($msg)) . '</p>';
    $html .= '<p>Link expires in 2 days.</p><p>Thanks,<br>HIGH Q Solid Academy</p>';

    $sent = false; try { $sent = sendEmail($emailTo, $subject, $html); } catch (Throwable $e) { $sent = false; }

    // update metadata emailed flag and last_resent_by/time
    try {
        $meta['emailed'] = $sent ? true : false;
        $meta['last_resent_by'] = (int)($_SESSION['user']['id'] ?? 0);
        $meta['last_resent_at'] = date('c');
        $upd = $pdo->prepare('UPDATE payments SET metadata = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute([json_encode($meta, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), $paymentId]);
    } catch (Throwable $_) {}

    echo json_encode(['status'=>'ok','emailed'=>$sent]); exit;
} catch (Throwable $e) {
    error_log('resend_payment_link error: ' . $e->getMessage());
    echo json_encode(['status'=>'error','message'=>'Server error']); exit;
}

?>

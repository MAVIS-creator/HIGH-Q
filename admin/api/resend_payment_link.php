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
    $html = '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:640px;margin:0 auto;color:#111827">';
    $html .= '<div style="background:#f59e0b;padding:14px 18px;border-radius:10px 10px 0 0;color:#111827;font-weight:700">HIGH Q SOLID ACADEMY</div>';
    $html .= '<div style="border:1px solid #e5e7eb;border-top:none;padding:18px;border-radius:0 0 10px 10px">';
    $html .= '<p style="margin:0 0 10px">Hello,</p>';
    $html .= '<p style="margin:0 0 12px">Your payment link has been resent. Amount due:</p>';
    $html .= '<p style="margin:0 0 14px;font-weight:700">₦' . number_format($p['amount'],2) . '</p>';
    $html .= '<p style="margin:0 0 14px"><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#dc2626;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600">Open Payment Link</a></p>';
    $html .= '<p style="margin:0 0 10px;font-size:13px;color:#4b5563">If the button does not work, copy this link:</p>';
    $html .= '<p style="margin:0 0 14px"><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
    if (!empty($msg)) {
        $html .= '<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;margin:0 0 14px">';
        $html .= '<p style="margin:0 0 6px;font-weight:600;color:#92400e">Message from admin</p>';
        $html .= '<p style="margin:0;color:#111827">' . nl2br(htmlspecialchars($msg)) . '</p>';
        $html .= '</div>';
    }
    $html .= '<p style="margin:0 0 10px">This link expires in <strong>2 days</strong>.</p>';
    $html .= '<p style="margin:0">Thanks,<br><strong>HIGH Q Solid Academy</strong></p>';
    $html .= '</div></div>';

    $sent = false; try { $sent = sendEmail($emailTo, $subject, $html); } catch (Throwable $e) { $sent = false; }

    // update metadata emailed flag and last_resent_by/time
    try {
        $meta['emailed'] = $sent ? true : false;
        $meta['last_resent_by'] = (int)($_SESSION['user']['id'] ?? 0);
        $meta['last_resent_at'] = date('c');
        $upd = $pdo->prepare('UPDATE payments SET metadata = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute([json_encode($meta, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), $paymentId]);
    } catch (Throwable $_) {}

    try {
        sendAdminChangeNotification(
            $pdo,
            'Payment Link Resent',
            [
                'Payment ID' => $paymentId,
                'Recipient Email' => $emailTo,
                'Reference' => $p['reference'] ?? '',
                'Email Sent' => $sent ? 'Yes' : 'No'
            ],
            (int)($_SESSION['user']['id'] ?? 0)
        );
    } catch (Throwable $_) {}

    echo json_encode(['status'=>'ok','emailed'=>$sent]); exit;
} catch (Throwable $e) {
    error_log('resend_payment_link error: ' . $e->getMessage());
    echo json_encode(['status'=>'error','message'=>'Server error']); exit;
}

?>

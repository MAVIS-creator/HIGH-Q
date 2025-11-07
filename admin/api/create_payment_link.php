<?php
// admin/api/create_payment_link.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if (empty($_SESSION['user'])) { echo json_encode(['status'=>'error','message'=>'Not authenticated']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['status'=>'error','message'=>'Invalid method']); exit; }

$token = $_POST['_csrf'] ?? '';
if (!verifyToken('payments_form', $token)) { echo json_encode(['status'=>'error','message'=>'Invalid CSRF']); exit; }

$amount = floatval(str_replace(',', '', $_POST['amount'] ?? '0'));
$email = trim($_POST['email'] ?? '');
$msg = trim($_POST['message'] ?? '');
if ($amount <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['status'=>'error','message'=>'Invalid input']); exit; }

try {
    // Load payment config to check for surcharge rules
    $payConfig = [];
    try { $payConfig = include __DIR__ . '/../../config/payments.php'; } catch (Throwable $_) { $payConfig = []; }

    // Determine surcharge. Support config keys: 'surcharge' (percent), 'surcharge_percent', 'surcharge_fixed'
    $surchargeAmount = 0.0;
    $surchargeMeta = [];
    if (!empty($payConfig['surcharge_percent'])) {
        $pct = floatval($payConfig['surcharge_percent']);
        $surchargeAmount = $amount * $pct / 100.0;
        $surchargeMeta = ['type'=>'percent','value'=>$pct];
    } elseif (!empty($payConfig['surcharge_fixed'])) {
        $fixed = floatval($payConfig['surcharge_fixed']);
        $surchargeAmount = $fixed;
        $surchargeMeta = ['type'=>'fixed','value'=>$fixed];
    } elseif (isset($payConfig['surcharge']) && is_numeric($payConfig['surcharge'])) {
        // legacy: treat as percent
        $pct = floatval($payConfig['surcharge']);
        $surchargeAmount = $amount * $pct / 100.0;
        $surchargeMeta = ['type'=>'percent','value'=>$pct];
    }

    // Round monetary values to 2 decimals
    $surchargeAmount = round($surchargeAmount, 2);
    $totalAmount = round($amount + $surchargeAmount, 2);

    $ref = 'ADMIN-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $metadata = ['email_to' => $email, 'message' => $msg, 'base_amount' => $amount, 'surcharge' => $surchargeMeta, 'surcharge_amount' => $surchargeAmount];
    $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata) VALUES (NULL, ?, ?, ?, ?, NOW(), ?)');
    // Store the total amount (base + surcharge) in the payments.amount column so downstream pages show the final payable amount
    $ok = $ins->execute([$totalAmount, 'bank', $ref, 'pending', json_encode($metadata, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
    if (!$ok) throw new Exception('DB insert failed');
    $paymentId = $pdo->lastInsertId();
    // build link using app_url() helper so deployment base path is respected
    // Use friendly pay route so APP_URL and subfolder installs are respected
    $link = app_url('pay/' . urlencode($ref));
    $subject = 'Payment link — HIGH Q SOLID ACADEMY';
    // Email should show breakdown: base amount, surcharge, total
    $html = '<p>Hi,</p><p>Please use the following secure link to complete your payment.</p>';
    $html .= '<p>Amount: ₦' . number_format($amount,2) . '</p>';
    if ($surchargeAmount > 0) {
        $html .= '<p>Surcharge (' . htmlspecialchars(strtoupper($surchargeMeta['type'] ?? '')) . '): ₦' . number_format($surchargeAmount,2) . '</p>';
    }
    $html .= '<p><strong>Total payable: ₦' . number_format($totalAmount,2) . '</strong></p>';
    $html .= '<p>Use this secure link to pay: </p>';
    $html .= '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
    if (!empty($msg)) $html .= '<p>Message: ' . nl2br(htmlspecialchars($msg)) . '</p>';
    $html .= '<p>Link expires in 2 days.</p><p>Thanks,<br>HIGH Q Solid Academy</p>';
    $sent = false; try { $sent = sendEmail($email, $subject, $html); } catch (Throwable $e) { $sent = false; }
    // enrich metadata with emailed flag and created_by
    try {
        $metaArr = is_string($metadata) ? json_decode($metadata, true) : (is_array($metadata) ? $metadata : []);
        if (!is_array($metaArr)) $metaArr = [];
        $metaArr['emailed'] = $sent ? true : false;
        $metaArr['created_by'] = (int)($_SESSION['user']['id'] ?? 0);
        $upd = $pdo->prepare('UPDATE payments SET metadata = ? WHERE id = ?');
        $upd->execute([json_encode($metaArr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), $paymentId]);
    } catch (Throwable $_) {}
    // log action
    try { logAction($pdo, (int)($_SESSION['user']['id'] ?? 0), 'create_payment_link', ['payment_id'=>$paymentId,'email'=>$email,'emailed'=>$sent]); } catch(Throwable $_){}

    echo json_encode(['status'=>'ok','payment_id'=>$paymentId,'reference'=>$ref,'link'=>$link,'emailed'=>$sent]); exit;
} catch (Throwable $e) {
    error_log('create_payment_link error: ' . $e->getMessage());
    echo json_encode(['status'=>'error','message'=>'Server error']); exit;
}

?>

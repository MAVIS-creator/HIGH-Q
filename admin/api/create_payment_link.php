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
    $ref = 'ADMIN-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $metadata = ['email_to' => $email, 'message' => $msg];
    $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata) VALUES (NULL, ?, ?, ?, ?, NOW(), ?)');
    $ok = $ins->execute([$amount, 'bank', $ref, 'pending', json_encode($metadata, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
    if (!$ok) throw new Exception('DB insert failed');
    $paymentId = $pdo->lastInsertId();
    // build link
    $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/public/payments_wait.php?ref=' . urlencode($ref);
    $subject = 'Payment link — HIGH Q SOLID ACADEMY';
    $html = '<p>Hi,</p><p>Please use the following secure link to complete your payment of ₦' . number_format($amount,2) . ':</p>';
    $html .= '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
    if (!empty($msg)) $html .= '<p>Message: ' . nl2br(htmlspecialchars($msg)) . '</p>';
    $html .= '<p>Link expires in 2 days.</p><p>Thanks,<br>HIGH Q Solid Academy</p>';
    $sent = false; try { $sent = sendEmail($email, $subject, $html); } catch (Throwable $e) { $sent = false; }
    // log action
    try { logAction($pdo, (int)($_SESSION['user']['id'] ?? 0), 'create_payment_link', ['payment_id'=>$paymentId,'email'=>$email]); } catch(Throwable $_){}

    echo json_encode(['status'=>'ok','payment_id'=>$paymentId,'reference'=>$ref,'link'=>$link,'emailed'=>$sent]); exit;
} catch (Throwable $e) {
    error_log('create_payment_link error: ' . $e->getMessage());
    echo json_encode(['status'=>'error','message'=>'Server error']); exit;
}

?>

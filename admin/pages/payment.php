<?php
// admin/pages/payment.php - small admin UI to create & send payment links
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('payments');
$pageTitle = 'Create Payment Link';
require_once __DIR__ . '/../includes/header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken('payments_form', $token)) {
        $message = 'Invalid CSRF token.';
    } else {
        $amount = floatval(str_replace(',', '', $_POST['amount'] ?? '0'));
        $email = trim($_POST['email'] ?? '');
        $msg = trim($_POST['message'] ?? '');
        if ($amount <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please provide a valid amount and email.';
        } else {
            // create payment record
            $ref = 'ADMIN-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
            $metadata = ['email_to' => $email, 'message' => $msg];
            $ins = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata) VALUES (NULL, ?, ?, ?, ?, NOW(), ?)');
            $ok = $ins->execute([$amount, 'bank', $ref, 'pending', json_encode($metadata, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
            if ($ok) {
                $paymentId = $pdo->lastInsertId();
                // send email with link
                $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/public/payments_wait.php?ref=' . urlencode($ref);
                $subject = 'Payment link — HIGH Q SOLID ACADEMY';
                $html = '<p>Hi,</p><p>Please use the following secure link to complete your payment of ₦' . number_format($amount,2) . ':</p>';
                $html .= '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
                if (!empty($msg)) $html .= '<p>Message: ' . nl2br(htmlspecialchars($msg)) . '</p>';
                $html .= '<p>Link expires in 2 days.</p><p>Thanks,<br>HIGH Q Solid Academy</p>';
                $sent = false;
                try { $sent = sendEmail($email, $subject, $html); } catch (Throwable $e) { $sent = false; }
                if ($sent) {
                    $message = 'Payment link created and emailed to ' . htmlspecialchars($email) . '.';
                    logAction($pdo, (int)($_SESSION['user']['id'] ?? 0), 'create_payment_link', ['payment_id'=>$paymentId,'email'=>$email]);
                } else {
                    $message = 'Payment created but email sending failed. You can copy the link below to send manually.';
                }
            } else {
                $message = 'Failed to create payment record.';
            }
        }
    }
}

?>
<div class="container" style="max-width:760px;margin:18px auto;padding:18px;background:#fff;border-radius:8px;">
    <h1>Create Payment Link</h1>
    <?php if (!empty($message)): ?><div style="padding:10px;border-radius:6px;background:#f7f7f7;border:1px solid #eee;margin-bottom:12px"><?= $message ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= generateToken('payments_form') ?>">
        <div style="margin-bottom:10px;"><label>Amount (NGN)</label><br><input type="text" name="amount" placeholder="e.g. 1080" required style="width:100%;padding:8px;border-radius:6px;border:1px solid #ddd"></div>
        <div style="margin-bottom:10px;"><label>Recipient email</label><br><input type="email" name="email" placeholder="payer@example.com" required style="width:100%;padding:8px;border-radius:6px;border:1px solid #ddd"></div>
        <div style="margin-bottom:10px;"><label>Message (optional)</label><br><textarea name="message" rows="4" style="width:100%;padding:8px;border-radius:6px;border:1px solid #ddd" placeholder="Message to include with the payment link"></textarea></div>
        <div><button class="btn" type="submit">Create & Send Link</button></div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php';

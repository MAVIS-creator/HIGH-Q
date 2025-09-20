<?php
// public/api/payments_webhook.php
// Webhook receiver for Paystack (example). Verifies signature and updates payments and user status.
// Use public-side config and DB
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
$cfg = require __DIR__ . '/../config/payments.php';
$secret = $cfg['paystack']['webhook_secret'] ?? '';

$body = @file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (!$secret || !hash_equals(hash_hmac('sha512', $body, $secret), $signature)) {
    http_response_code(400);
    error_log('Invalid webhook signature');
    exit('Invalid signature');
}

$payload = json_decode($body, true);
$event = $payload['event'] ?? '';

if ($event === 'charge.success' || $event === 'payment.success') {
    $data = $payload['data'] ?? [];
    $reference = $data['reference'] ?? null;
    $amount = ($data['amount'] ?? 0) / 100.0;

    if ($reference) {
        $stmt = $pdo->prepare('SELECT id, amount, student_id FROM payments WHERE reference = ? LIMIT 1');
        $stmt->execute([$reference]);
        $p = $stmt->fetch();
        if ($p) {
            // optional amount check
            if (abs($p['amount'] - $amount) < 0.01) {
                $meta = json_encode($data);
                $upd = $pdo->prepare("UPDATE payments SET status='confirmed', metadata = ?, gateway='paystack', confirmed_at = NOW(), updated_at = NOW() WHERE id = ?");
                $upd->execute([$meta, $p['id']]);

                // activate user
                if (!empty($p['student_id'])) {
                    $act = $pdo->prepare('UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?');
                    $act->execute([$p['student_id']]);

                    // notify user via email if available
                    $u = $pdo->prepare('SELECT email, name FROM users WHERE id = ? LIMIT 1');
                    $u->execute([$p['student_id']]);
                    $user = $u->fetch();
                    if ($user && !empty($user['email'])) {
                        $subject = 'Payment confirmed';
                        $html = "<p>Hi " . htmlspecialchars($user['name']) . ",</p><p>Your payment has been received and your account is now active. Reference: " . htmlspecialchars($reference) . "</p>";
                        // sendEmail comes from public/config/functions.php
                        @sendEmail($user['email'], $subject, $html);
                    }
                }
            } else {
                error_log('Payment amount mismatch for reference: ' . $reference);
            }
        } else {
            error_log('Webhook: no payment found for reference ' . $reference);
        }
    }
}

http_response_code(200);
echo 'OK';

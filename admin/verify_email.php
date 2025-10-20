<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if ($token) {
    try {
        $stmt = $pdo->prepare('SELECT id, email, name, is_active, email_verification_sent_at FROM users WHERE email_verification_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Token expiry: allow 72 hours by default
            $expireHours = getenv('EMAIL_VERIFICATION_EXPIRE_HOURS') ? intval(getenv('EMAIL_VERIFICATION_EXPIRE_HOURS')) : 72;
            $sentAt = $user['email_verification_sent_at'];
            $ok = true;
            if (!empty($sentAt)) {
                $sentTs = strtotime($sentAt);
                if ($sentTs === false || (time() - $sentTs) > ($expireHours * 3600)) {
                    $ok = false;
                }
            }

            if (!$ok) {
                $message = 'Verification token expired. Ask the user to request a new verification email.';
            } else {
                if ((int)$user['is_active'] === 1) {
                    $message = 'Account is already active.';
                    $success = true;
                } else {
                    $update = $pdo->prepare('UPDATE users SET is_active = 1, email_verification_token = NULL, email_verified_at = NOW(), email_verification_sent_at = NULL WHERE id = ?');
                    $update->execute([$user['id']]);
                    $message = 'User email verified and account activated.';
                    $success = true;
                }
            }
        } else {
            $message = 'Invalid verification token.';
        }
    } catch (Throwable $e) {
        $message = 'Error verifying token.';
        error_log('admin/verify_email error: '.$e->getMessage());
    }
} else {
    $message = 'No verification token provided.';
}

// Simple admin-facing page
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="padding:24px;">
  <h1>Email Verification</h1>
  <div class="admin-notice" style="background:<?= $success ? '#e6fff0' : '#fff7e6' ?>;border-left:4px solid <?= $success ? '#3cb371' : '#ffd166' ?>;padding:12px;">
    <?= htmlspecialchars($message) ?>
  </div>
  <p><a href="./index.php">Back to Home</a></p>
</div>

<div class="footer" style="position:fixed;left:0;bottom:0;width:100%;background:#fff;color:#555;padding:10px 0;text-align:center;z-index:1000;box-shadow:0 -2px 12px rgba(0,0,0,0.07);font-size:0.95rem;">
    © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED - Admin Panel
</div>

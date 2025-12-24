<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if ($token) {
    try {
        $stmt = $pdo->prepare('SELECT id, email, name, is_active FROM users WHERE email_verification_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            if ((int)$user['is_active'] === 1) {
                $message = 'Your account is already active. You may log in.';
                $success = true;
            } else {
                $update = $pdo->prepare('UPDATE users SET is_active = 1, email_verification_token = NULL, email_verified_at = NOW() WHERE id = ?');
                $update->execute([$user['id']]);
                $message = 'Thank you! Your email has been verified and your account is now active. You may log in.';
                $success = true;
            }
        } else {
            $message = 'Invalid or expired verification token.';
        }
    } catch (Throwable $e) {
        $message = 'An error occurred while verifying your email.';
        error_log('verify_email error: ' . $e->getMessage());
    }
} else {
    $message = 'Missing verification token.';
}

?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Email Verification</title>
    <?php
        // Ensure functions are loaded
        if (!function_exists('app_url')) {
            require_once __DIR__ . '/config/functions.php';
        }
    ?>
    <link rel="stylesheet" href="<?= app_url('assets/css/public.css') ?>">
    <style>.container{max-width:720px;margin:40px auto;padding:20px}</style>
</head>
<body>
    <div class="container">
        <h1>Email Verification</h1>
        <div class="notice <?= $success ? 'success' : 'error' ?>">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
        <p><a href="/public/login.php">Go to login</a></p>
    </div>
</body>
</html>

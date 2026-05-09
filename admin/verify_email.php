<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;
$userName = '';

if ($token) {
    try {
        $stmt = $pdo->prepare('SELECT id, email, name, is_active, email_verification_sent_at FROM users WHERE email_verification_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userName = $user['name'];
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
                $message = 'Verification link has expired. Please contact an administrator to request a new verification email.';
            } else {
                if ((int)$user['is_active'] === 1) {
                    $message = 'Your account is already verified and active!';
                    $success = true;
                } else {
                    $update = $pdo->prepare('UPDATE users SET is_active = 1, email_verification_token = NULL, email_verified_at = NOW(), email_verification_sent_at = NULL WHERE id = ?');
                    $update->execute([$user['id']]);
                    $message = 'Email verified successfully! Your account is now active.';
                    $success = true;
                }
            }
        } else {
            $message = 'Invalid or already used verification link.';
        }
    } catch (Throwable $e) {
        $message = 'An error occurred while verifying your email.';
        error_log('admin/verify_email error: '.$e->getMessage());
    }
} else {
    $message = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - HIGH Q SOLID ACADEMY</title>
    <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/auth.css">
    <link rel="stylesheet" href="./assets/css/admin-minimal.css">
    <script src="./assets/js/device-capability.js"></script>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <?php if ($success): ?>
                <!-- Success State -->
                <div class="status-icon success">
                    <i class='bx bx-check'></i>
                </div>
                <h1 class="auth-title">Email Verified!</h1>
                <?php if ($userName): ?>
                    <p class="auth-subtitle">Welcome, <strong><?= htmlspecialchars($userName) ?></strong>!</p>
                <?php endif; ?>
                
                <div class="alert alert-success" style="animation: none; margin-top: 24px;">
                    <span class="alert-icon"><i class='bx bx-check-circle'></i></span>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>

                <a href="login.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none; margin-top: 24px;">
                    <i class='bx bx-log-in'></i>&nbsp; Sign In Now
                </a>
            <?php else: ?>
                <!-- Error State -->
                <div class="status-icon error">
                    <i class='bx bx-x'></i>
                </div>
                <h1 class="auth-title">Verification Failed</h1>
                <p class="auth-subtitle">We couldn't verify your email address.</p>
                
                <div class="alert alert-error" style="animation: none; margin-top: 24px;">
                    <span class="alert-icon"><i class='bx bx-error-circle'></i></span>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>

                <div style="margin-top: 24px; display: flex; gap: 12px;">
                    <a href="index.php" class="btn-secondary" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                        <i class='bx bx-home'></i>&nbsp; Home
                    </a>
                    <a href="login.php" class="btn-primary" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                        <i class='bx bx-log-in'></i>&nbsp; Login
                    </a>
                </div>
            <?php endif; ?>

            <!-- Info -->
            <div class="auth-divider">Need assistance?</div>
            
            <div class="auth-features" style="grid-template-columns: 1fr;">
                <div class="feature-item">
                    <div class="feature-icon"><i class='bx bx-support'></i></div>
                    <div>
                        <div class="feature-title">Contact Support</div>
                        <div class="feature-desc">If you're having issues, please reach out to our admin team.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="auth-footer">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED. All rights reserved.
    </footer>
</body>
</html>

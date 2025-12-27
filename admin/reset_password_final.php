<?php
session_start();
require_once './includes/db.php';
require_once './includes/functions.php';
require_once './includes/csrf.php';

$errors = [];
$success = '';
$showForm = true;

// Redirect if no OTP session
if (!isset($_SESSION['reset_otp'])) {
    header('Location: forgot_password.php');
    exit;
}

$csrfToken = generateToken('reset_final_form');
$attemptsLeft = 5 - ($_SESSION['otp_attempts'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken('reset_final_form', $_POST['_csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please refresh the page.";
    } else {
        $otp = trim($_POST['otp'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        $otpSession = $_SESSION['reset_otp'];

        // Check OTP expiry
        if (time() > $otpSession['expires']) {
            $errors[] = "OTP expired. Please request a new one.";
            unset($_SESSION['reset_otp']);
            $showForm = false;
        }

        // OTP verification
        if (empty($errors) && $otp != $otpSession['otp']) {
            if (!isset($_SESSION['otp_attempts'])) $_SESSION['otp_attempts'] = 0;
            $_SESSION['otp_attempts']++;
            if ($_SESSION['otp_attempts'] >= 5) {
                $errors[] = "Too many wrong OTP attempts. Please request a new OTP.";
                unset($_SESSION['reset_otp'], $_SESSION['otp_attempts']);
                $showForm = false;
            } else {
                $attemptsLeft = 5 - $_SESSION['otp_attempts'];
                $errors[] = "Invalid OTP. {$attemptsLeft} attempts remaining.";
            }
        }

        // Password validation
        if (empty($errors)) {
            if ($password !== $confirm) {
                $errors[] = "Passwords do not match.";
            } elseif (strlen($password) < 8) {
                $errors[] = "Password must be at least 8 characters.";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
                $errors[] = "Password must include uppercase, lowercase, and a number.";
            }
        }

        // If no errors, update password
        if (empty($errors) && $showForm) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $otpSession['user_id']]);

            // Send confirmation email
            $stmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt->execute([$otpSession['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                $subject = "Password Reset Successful - HIGH Q SOLID ACADEMY";
                $html = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #22c55e, #4ade80); padding: 30px; text-align: center;'>
                        <h1 style='color: #fff; margin: 0;'>✓ Password Changed</h1>
                    </div>
                    <div style='padding: 30px; background: #fff;'>
                        <p style='font-size: 16px;'>Hello <strong>{$user['name']}</strong>,</p>
                        <p style='font-size: 16px;'>Your admin password has been successfully reset.</p>
                        <p style='font-size: 14px; color: #666;'>If you did not perform this action, please contact support immediately.</p>
                    </div>
                </div>";
                sendEmail($user['email'], $subject, $html);
            }

            // Cleanup
            unset($_SESSION['reset_otp'], $_SESSION['otp_attempts']);

            $success = "Password successfully reset!";
            $showForm = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - HIGH Q SOLID ACADEMY</title>
    <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <?php if ($success): ?>
                <!-- Success State -->
                <div class="status-icon success">
                    <i class='bx bx-check'></i>
                </div>
                <h1 class="auth-title">Password Reset!</h1>
                <p class="auth-subtitle">Your password has been successfully changed.</p>
                
                <a href="login.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none; margin-top: 24px;">
                    <i class='bx bx-log-in'></i>&nbsp; Sign In Now
                </a>
            <?php elseif (!$showForm): ?>
                <!-- Expired/Failed State -->
                <div class="status-icon error">
                    <i class='bx bx-x'></i>
                </div>
                <h1 class="auth-title">Session Expired</h1>
                <p class="auth-subtitle">Your OTP session has expired or too many failed attempts.</p>
                
                <a href="forgot_password.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none; margin-top: 24px;">
                    <i class='bx bx-refresh'></i>&nbsp; Request New OTP
                </a>
            <?php else: ?>
                <!-- Reset Form -->
                <div class="auth-logo">
                    <div class="auth-logo-icon">
                        <i class='bx bx-key'></i>
                    </div>
                </div>

                <h1 class="auth-title">Set New Password</h1>
                <p class="auth-subtitle">Enter the OTP sent to your email and create a new password.</p>

                <!-- Errors -->
                <?php foreach ($errors as $e): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon"><i class='bx bx-error-circle'></i></span>
                        <span><?= htmlspecialchars($e) ?></span>
                    </div>
                <?php endforeach; ?>

                <form method="POST" class="auth-form" id="resetForm">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="form-group">
                        <label class="form-label">Enter OTP</label>
                        <input type="text" name="otp" class="form-input otp-input" placeholder="● ● ● ● ● ●" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code">
                        <small style="color: var(--hq-gray-dark); font-size: 0.8rem;">
                            <?= $attemptsLeft ?> attempts remaining
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-input" placeholder="Create a strong password" required autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                <i class='bx bx-hide'></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-input" placeholder="Confirm your password" required autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                <i class='bx bx-hide'></i>
                            </button>
                        </div>
                    </div>

                    <!-- Password Requirements -->
                    <div class="alert alert-info" style="animation: none;">
                        <span class="alert-icon"><i class='bx bx-info-circle'></i></span>
                        <div style="font-size: 0.85rem;">
                            <strong>Password must include:</strong><br>
                            • At least 8 characters<br>
                            • Uppercase & lowercase letters<br>
                            • At least one number
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class='bx bx-check-shield'></i>&nbsp; Reset Password
                    </button>
                </form>

                <!-- Links -->
                <div class="auth-links">
                    <a href="forgot_password.php" class="auth-link">
                        <i class='bx bx-refresh'></i> Request new OTP
                    </a>
                    <a href="login.php" class="auth-link">
                        <i class='bx bx-arrow-back'></i> Back to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="auth-footer">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED. All rights reserved.
    </footer>

    <script>
        function togglePassword(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bx-hide');
                icon.classList.add('bx-show');
            } else {
                input.type = 'password';
                icon.classList.remove('bx-show');
                icon.classList.add('bx-hide');
            }
        }

        document.getElementById('resetForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('btn-loading');
            btn.disabled = true;
        });

        // OTP input auto-format
        document.querySelector('.otp-input')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    </script>
</body>
</html>

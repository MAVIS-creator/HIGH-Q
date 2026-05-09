<?php
// admin/forgot_password.php
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $token = $_POST['_csrf_token'] ?? '';

    if (!verifyToken('forgot_password_form', $token)) {
        $error = "Invalid CSRF token. Please refresh and try again.";
    } elseif (empty($email)) {
        $error = "Please enter your registered email address.";
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Generate OTP (6-digit)
                $otp = random_int(100000, 999999);

                // Store OTP in session
                $_SESSION['reset_otp'] = [
                    'email' => $email,
                    'otp'   => $otp,
                    'user_id' => $user['id'],
                    'expires' => time() + 300 // 5 mins expiry
                ];

                // Send via PHPMailer
                $subject = "Password Reset OTP - HIGH Q SOLID ACADEMY";
                $html = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #ffd600, #ff4b2b); padding: 30px; text-align: center;'>
                        <h1 style='color: #fff; margin: 0;'>HIGH Q SOLID ACADEMY</h1>
                    </div>
                    <div style='padding: 30px; background: #fff;'>
                        <p style='font-size: 16px;'>Hello <strong>{$user['name']}</strong>,</p>
                        <p style='font-size: 16px;'>You requested to reset your password. Use the OTP below:</p>
                        <div style='background: #f3f4f6; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px;'>
                            <span style='font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #1a73e8;'>{$otp}</span>
                        </div>
                        <p style='font-size: 14px; color: #666;'>This code expires in <strong>5 minutes</strong>.</p>
                        <p style='font-size: 14px; color: #666;'>If you didn't request this, please ignore this email.</p>
                    </div>
                    <div style='background: #0a0a0a; padding: 20px; text-align: center;'>
                        <p style='color: #999; font-size: 12px; margin: 0;'>© " . date('Y') . " HIGH Q SOLID ACADEMY LIMITED</p>
                    </div>
                </div>";

                sendEmail($email, $subject, $html);
                $success = "An OTP has been sent to your email. Please check your inbox.";
            } else {
                // Don't reveal if email exists or not for security
                $success = "If this email is registered, you will receive an OTP shortly.";
            }
        } catch (Exception $e) {
            $error = "Something went wrong. Please try again.";
            error_log('forgot_password error: ' . $e->getMessage());
        }
    }
}

$csrfToken = generateToken('forgot_password_form');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - HIGH Q SOLID ACADEMY</title>
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
            <!-- Logo Icon -->
            <div class="auth-logo">
                <div class="auth-logo-icon">
                    <i class='bx bx-lock-open-alt'></i>
                </div>
            </div>

            <!-- Title -->
            <h1 class="auth-title">Forgot Password?</h1>
            <p class="auth-subtitle">No worries! Enter your email and we'll send you a reset OTP.</p>

            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><i class='bx bx-error-circle'></i></span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Success Alert -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon"><i class='bx bx-check-circle'></i></span>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
                <script>
                    // Redirect to reset page after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'reset_password_final.php';
                    }, 2000);
                </script>
            <?php endif; ?>

            <!-- Form -->
            <?php if (!$success): ?>
            <form method="POST" class="auth-form" id="forgotForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="Enter your registered email" required autocomplete="email">
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class='bx bx-send'></i>&nbsp; Send OTP
                </button>
            </form>
            <?php else: ?>
            <a href="reset_password_final.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none;">
                <i class='bx bx-right-arrow-alt'></i>&nbsp; Continue to Reset
            </a>
            <?php endif; ?>

            <!-- Links -->
            <div class="auth-links">
                <a href="login.php" class="auth-link">
                    <i class='bx bx-arrow-back'></i> Back to Login
                </a>
            </div>

            <!-- Info -->
            <div class="auth-features" style="grid-template-columns: 1fr 1fr;">
                <div class="feature-item">
                    <div class="feature-icon"><i class='bx bx-envelope'></i></div>
                    <div>
                        <div class="feature-title">Check Inbox</div>
                        <div class="feature-desc">& Spam folder</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class='bx bx-time'></i></div>
                    <div>
                        <div class="feature-title">5 Minutes</div>
                        <div class="feature-desc">OTP validity</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="auth-footer">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED. All rights reserved.
    </footer>

    <script>
        document.getElementById('forgotForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('btn-loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>

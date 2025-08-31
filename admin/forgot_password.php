<?php
// admin/forgot_password.php

require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/functions.php'; // contains PHPMailer wrapper
require __DIR__ . '/../vendor/autoload.php';

session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $token = $_POST['_csrf_token'] ?? '';

    // 🔐 Verify CSRF token
    if (!verifyToken('forgot_password_form', $token)) {
        $error = "Invalid CSRF token. Please refresh and try again.";
    } elseif (empty($email)) {
        $error = "Please enter your registered email address.";
    } else {
        try {
            // 🎲 Generate OTP (6-digit)
            $otp = random_int(100000, 999999);

            // Store OTP in session (short expiry)
            $_SESSION['password_reset'] = [
                'email' => $email,
                'otp'   => $otp,
                'expires' => time() + 300 // 5 mins expiry
            ];

            // Send via PHPMailer helper (in functions.php)
            $subject = "Password Reset OTP";
            $html = "<p>Hello,</p>
                     <p>Your OTP for resetting your password is: 
                     <strong>{$otp}</strong></p>
                     <p>This code will expire in 5 minutes.</p>";

            sendEmail($email, $subject, $html);

            $success = "An OTP has been sent to your email. Please check your inbox.";
        } catch (Exception $e) {
            $error = "Something went wrong while sending OTP. Please try again.";
        }
    }
}

// Always prepare CSRF token for form
$csrfToken = generateToken('forgot_password_form');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Forgot Password</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <label for="email">Registered Email</label>
            <input type="email" name="email" id="email" required>

            <button type="submit">Send OTP</button>
        </form>

        <p><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>

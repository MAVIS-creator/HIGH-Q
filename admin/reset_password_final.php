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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
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
        }

        // OTP verification
        if ($otp != $otpSession['otp']) {
            // Track failed attempts
            if (!isset($_SESSION['otp_attempts'])) $_SESSION['otp_attempts'] = 0;
            $_SESSION['otp_attempts']++;
            if ($_SESSION['otp_attempts'] >= 5) {
                $errors[] = "Too many wrong OTP attempts. Request a new OTP.";
                unset($_SESSION['reset_otp'], $_SESSION['otp_attempts']);
            } else {
                $errors[] = "Invalid OTP. Attempts left: " . (5 - $_SESSION['otp_attempts']);
            }
        }

        // Password validation
        if ($password !== $confirm) {
            $errors[] = "Passwords do not match.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/', $password)) {
            $errors[] = "Password must be 12+ chars with upper, lower, number & special char.";
        }

        // If no errors, update password
        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $otpSession['user_id']]);

            // Optionally send confirmation email
            $stmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt->execute([$otpSession['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                $subject = "Password Reset Successful - HIGH Q SOLID ACADEMY";
                $html = "<p>Hello {$user['name']},</p>
                         <p>Your admin password has been successfully reset. If you did not perform this action, contact support immediately.</p>";
                sendEmail($user['email'], $subject, $html);
            }

            // Cleanup
            unset($_SESSION['reset_otp'], $_SESSION['otp_attempts']);

            $success = "Password successfully reset! <a href='login.php'>Login Now</a>";
            $showForm = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Set New Password - HIGH Q SOLID ACADEMY</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root { 
  --hq-yellow: #ffd600;
  --hq-yellow-light: #ffe566;
  --hq-red: #ff4b2b;
  --hq-black: #0a0a0a;
  --hq-gray: #f3f4f6;
  --max-width: 960px;
  --card-width: 760px;
  --radius: 12px;
}
body {
    margin:0;
    font-family:"Poppins", sans-serif;
    background: linear-gradient(135deg, var(--hq-yellow), var(--hq-red));
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    padding:20px;
}
.card {
    background:#fff;
    border-radius:var(--radius);
    box-shadow:0 12px 32px rgba(0,0,0,0.18);
    padding:32px;
    max-width:400px;
    width:100%;
    text-align:center;
}
h2 { margin-bottom: 0.5rem; color: var(--hq-red);}
p { margin-bottom: 1rem; color: var(--hq-black);}
input { width:100%; padding:0.6rem; margin:0.5rem 0 1rem; border-radius:6px; border:1px solid #ccc;}
button { background: var(--hq-red); color:#fff; padding:0.8rem; width:100%; border:none; border-radius:6px; cursor:pointer;}
button:hover { background: var(--hq-yellow); color: var(--hq-black); }
.error { background:#ffe6e6; border-left:4px solid var(--hq-red); color: var(--hq-red); padding:0.5rem; margin-bottom:1rem; text-align:left;}
.success { background:#fff3cd; border-left:4px solid var(--hq-yellow); color: var(--hq-black); padding:0.5rem; margin-bottom:1rem; text-align:left;}
</style>
</head>
<body>

<div class="card">
    <h2>Set New Password</h2>
    <p>Enter OTP and your new password</p>

    <?php foreach($errors as $e): ?>
        <div class="error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <?php if($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <?php if($showForm): ?>
    <form method="POST">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <input type="password" name="password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>
</div>

</body>
</html>

<?php
// admin/reset_password.php
session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/includes/db.php';        // your PDO connection
require __DIR__ . '/includes/functions.php'; // for sendEmail + logging
require __DIR__ . '/includes/csrf.php';      // Symfony CSRF wrapper

use Symfony\Component\Security\Csrf\CsrfToken;

$errors = [];
$success = "";

// Step 1: Verify OTP & allow reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyToken(new CsrfToken('reset_form', $csrfToken))) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $otp   = trim($_POST['otp'] ?? '');
        $pass1 = $_POST['password'] ?? '';
        $pass2 = $_POST['confirm_password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        if ($pass1 !== $pass2) {
            $errors[] = "Passwords do not match.";
        }

        if (strlen($pass1) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }

        if (empty($errors)) {
            // check OTP validity
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW()");
            $stmt->execute([$email, $otp]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $errors[] = "Invalid or expired OTP.";
            } else {
                // OTP is valid → update password
                $hashed = password_hash($pass1, PASSWORD_DEFAULT);
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $stmt->execute([$hashed, $email]);

                    // Remove used OTP
                    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $stmt->execute([$email]);

                    $pdo->commit();

                    // Log action
                    logAction($pdo, 0, "password_reset", ['email' => $email]);

                    $success = "Password successfully reset. You may now <a href='login.php'>log in</a>.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = "An error occurred. Please try again.";
                }
            }
        }
    }
}

$csrfToken = generateToken('reset_form');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
  <div class="form-container">
    <h2>Reset Password</h2>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?= implode("<br>", array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <?= $success ?>
      </div>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" required>

        <label for="otp">OTP Code</label>
        <input type="text" name="otp" id="otp" required>

        <label for="password">New Password</label>
        <input type="password" name="password" id="password" required>

        <label for="confirm_password">Confirm New Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <button type="submit">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>

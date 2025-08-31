<?php
session_start();
require_once './includes/db.php';
require_once './includes/functions.php'; // sendEmail()
require_once './includes/csrf.php';

$errors = [];
$success = '';
$showForm = true;

// Generate CSRF token
$csrfToken = generateToken('reset_form');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    if (!verifyToken('reset_form', $_POST['_csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please refresh and try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email address.";
        } else {
            // Rate limiting: max 3 attempts per 15 minutes
            if (!isset($_SESSION['reset_attempts'])) {
                $_SESSION['reset_attempts'] = [];
            }
            $_SESSION['reset_attempts'] = array_filter($_SESSION['reset_attempts'], fn($t) => $t > time() - 900);
            if (count($_SESSION['reset_attempts']) >= 3) {
                $errors[] = "Too many reset attempts. Try again in 15 minutes.";
            } else {
                // Find user
                $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Generate OTP
                    $otp = random_int(100000, 999999);

                    // Store OTP temporarily in session (or DB)
                    $_SESSION['reset_otp'] = [
                        'user_id' => $user['id'],
                        'otp' => $otp,
                        'expires' => time() + 900 // 15 min expiry
                    ];

                    // Send OTP via email
                    $subject = "Password Reset OTP - HIGH Q SOLID ACADEMY";
                    $html = "<p>Hello {$user['name']},</p>
                             <p>You requested a password reset. Use the OTP below to reset your password:</p>
                             <h2>{$otp}</h2>
                             <p>This OTP expires in 15 minutes.</p>";
                    sendEmail($email, $subject, $html);

                    $success = "OTP sent to your email. Enter it below to reset your password.";
                } else {
                    $errors[] = "Email not found in our system.";
                }

                $_SESSION['reset_attempts'][] = time();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password - HIGH Q SOLID ACADEMY</title>
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
    <h2>Reset Password</h2>
    <p>Enter your email to receive a one-time password (OTP)</p>

    <?php foreach($errors as $e): ?>
        <div class="error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <?php if($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if($showForm): ?>
    <form method="POST">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="email" name="email" placeholder="you@example.com" required>
        <button type="submit">Send OTP</button>
    </form>
    <?php endif; ?>
</div>

</body>
</html>

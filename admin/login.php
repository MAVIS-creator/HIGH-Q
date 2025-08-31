<?php
session_start();
require './includes/db.php';
require './includes/functions.php'; // sendEmail(), etc.
require './includes/csrf.php'; // CSRF functions

$error = '';

// Generate CSRF token for login form
$csrfToken = generateToken('login_form');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    if (!verifyToken('login_form', $token)) {
        $error = "Invalid CSRF token. Please refresh and try again.";
    } else {
        $email    = trim($_POST['email']);
        $password = trim($_POST['password']);

        $stmt = $pdo->prepare("SELECT u.*, r.slug AS role_slug, r.name AS role_name 
                               FROM users u 
                               LEFT JOIN roles r ON u.role_id = r.id 
                               WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active'] == 0) {
                $error = "Your account is pending approval.";
            } elseif ($user['is_active'] == 2) {
                $error = "Your account has been banned.";
            } else {
                $_SESSION['user'] = [
                    'id'        => $user['id'],
                    'name'      => $user['name'],
                    'email'     => $user['email'],
                    'role_id'   => $user['role_id'],
                    'role_slug' => $user['role_slug'],
                    'role_name' => $user['role_name']
                ];
                header("Location: pages/index.php");
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login - HIGH Q SOLID ACADEMY</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    :root {
        --primary-red: #d62828;
        --accent-yellow: #fcbf49;
        --dark-black: #000000;
        --pure-white: #ffffff;
    }
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, var(--accent-yellow), var(--primary-red));
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
    }
    .login-card {
        background: var(--pure-white);
        padding: 2rem;
        border-radius: 10px;
        width: 350px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        text-align: center;
    }
    .login-card h2 {
        color: var(--primary-red);
        margin-bottom: 0.5rem;
    }
    .login-card p {
        color: var(--dark-black);
        margin-bottom: 1.5rem;
    }
    label {
        display: block;
        text-align: left;
        font-weight: bold;
        margin-top: 1rem;
        color: var(--dark-black);
    }
    input {
        width: 100%;
        padding: 0.6rem;
        margin-top: 0.3rem;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    button {
        background: var(--primary-red);
        color: var(--pure-white);
        border: none;
        padding: 0.8rem;
        width: 100%;
        font-size: 1rem;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 1.5rem;
    }
    button:hover {
        background: var(--accent-yellow);
        color: var(--dark-black);
    }
    .error {
        background: #ffdddd;
        color: var(--primary-red);
        padding: 0.5rem;
        border-left: 4px solid var(--primary-red);
        margin-bottom: 1rem;
        text-align: left;
    }
    .forgot-link {
        margin-top: 1rem;
        display: block;
        font-size: 0.9rem;
        color: var(--primary-red);
        text-decoration: none;
    }
    .forgot-link:hover {
        text-decoration: underline;
    }
    .footer {
        margin-top: 1.5rem;
        font-size: 0.8rem;
        color: #555;
    }
</style>
</head>
<body>

<div class="login-card">
    <img src="../public/assets/images/logo.png" alt="Academy Logo" style="width:60px; margin-bottom:1rem;">
    <h2>Admin Panel Access</h2>
    <p>Always Ahead of Others</p>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <label>Email</label>
        <input type="email" name="email" placeholder="you@example.com" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="********" required>

        <button type="submit">Sign In</button>
    </form>

    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>

    <div class="footer">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED - Admin Panel
    </div>
</div>

</body>
</html>

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
    <link rel="shortcut icon" href="/HIGH-Q/admin/assets/img/favicon.ico" type="image/x-icon">
    <style>
        :root {
            --hq-primary: #ffd600;
            /* yellow */
            --hq-accent: #ff4b2b;
            /* red */
            --hq-black: #0a0a0a;
            --hq-white: #ffffff;
            --btn-padding: 0.8rem 1rem;
            --btn-radius: 8px;
            --btn-font-size: 1rem;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, var(--hq-primary), var(--hq-accent));
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            padding: 20px;
        }

        .login-card {
            background: var(--hq-white);
            padding: 2rem;
            border-radius: 12px;
            width: 380px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.18);
            text-align: center;
        }

        .login-card h2 {
            color: var(--hq-accent);
            margin-bottom: 0.5rem;
            font-size: 1.25rem
        }

        .login-card p {
            color: var(--hq-black);
            margin-bottom: 1rem
        }

        label {
            display: block;
            text-align: left;
            font-weight: 700;
            margin-top: 0.9rem;
            color: var(--hq-black)
        }

        input {
            width: 100%;
            padding: 0.65rem;
            margin-top: 0.3rem;
            border: 1px solid #ddd;
            border-radius: 8px
        }

        button {
            background: var(--hq-accent);
            color: var(--hq-white);
            border: none;
            padding: var(--btn-padding);
            width: 100%;
            font-size: var(--btn-font-size);
            border-radius: var(--btn-radius);
            cursor: pointer;
            margin-top: 1.2rem;
            font-weight: 700
        }

        button:hover {
            background: var(--hq-primary);
            color: var(--hq-black)
        }

        .error {
            background: #ffefef;
            color: var(--hq-accent);
            padding: 0.5rem;
            border-left: 4px solid var(--hq-accent);
            margin-bottom: 1rem;
            text-align: left
        }

        .forgot-link {
            margin-top: 1rem;
            display: block;
            font-size: 0.95rem;
            color: var(--hq-accent);
            text-decoration: none
        }

        .forgot-link:hover {
            text-decoration: underline
        }

        .footer {
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #555
        }
    </style>
</head>

<body>

    <div class="login-card">
    <img src="../assets/img/hq-logo.jpeg" alt="Academy Logo" style="width:60px; margin-bottom:1rem; border-radius:8px;">
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
            <div style="position:relative;">
                <input type="password" name="password" id="login_password" placeholder="********" required>
                <span class="toggle-eye" onclick="togglePassword('login_password', this)" title="Show/Hide Password" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer;">
                    &#128065;
                </span>
            </div>

            <button type="submit">Sign In</button>
        </form>

        <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
        <a href="signup.php" class="forgot-link" style="margin-top:0.25rem;">Don't have an account? Sign up</a>

        <div class="footer">
            © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED - Admin Panel
        </div>
    </div>

    <script>
        function togglePassword(fieldId, icon) {
            var input = document.getElementById(fieldId);
            if (input.type === "password") {
                input.type = "text";
                icon.innerHTML = "&#128064;"; // open eye
            } else {
                input.type = "password";
                icon.innerHTML = "&#128065;"; // closed eye
            }
        }
    </script>

</body>

</html>
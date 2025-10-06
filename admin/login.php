<?php
session_start();
require './includes/db.php';
require './includes/functions.php'; // sendEmail(), etc.
require './includes/csrf.php'; // CSRF functions
// load recaptcha config
$recfg = file_exists(__DIR__ . '/../config/recaptcha.php') ? require __DIR__ . '/../config/recaptcha.php' : ['site_key'=>'','secret'=>''];

$error = '';

// Generate CSRF token for login form
$csrfToken = generateToken('login_form');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    if (!verifyToken('login_form', $token)) {
        $error = "Invalid CSRF token. Please refresh and try again.";
    } else {
        // Verify reCAPTCHA v2/v3 token if site key configured
        if (!empty($recfg['secret'])) {
            $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
            if (!$recaptcha_response) {
                $error = "Please complete the I am not a robot check.";
            } else {
                // Verify against Google API
                $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
                $params = http_build_query(['secret'=>$recfg['secret'], 'response'=>$recaptcha_response, 'remoteip'=>$_SERVER['REMOTE_ADDR'] ?? '']);
                $opts = ['http'=>['method'=>'POST','header'=>'Content-type: application/x-www-form-urlencoded','content'=>$params,'timeout'=>5]];
                $ctx = stream_context_create($opts);
                $res = @file_get_contents($verifyUrl, false, $ctx);
                $j = $res ? json_decode($res, true) : null;
                if (!$j || empty($j['success'])) {
                    $error = 'reCAPTCHA validation failed. Please try again.';
                }
            }
        }
    } else {
        $email    = trim($_POST['email']);
        $password = trim($_POST['password']);

        $stmt = $pdo->prepare("SELECT u.*, r.slug AS role_slug, r.name AS role_name 
                               FROM users u 
                               LEFT JOIN roles r ON u.role_id = r.id 
                               WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Read max attempts setting
        $maxAttempts = 5;
        try {
            $stmtS = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
            $stmtS->execute(['system_settings']);
            $val = $stmtS->fetchColumn();
            $j = $val ? json_decode($val, true) : [];
            $maxAttempts = intval($j['advanced']['max_login_attempts'] ?? $maxAttempts);
            if ($maxAttempts < 1) $maxAttempts = 5;
        } catch (Throwable $e) { }

        // Rate-limit check: count recent attempts for this IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        try {
            if (!empty($ip)) {
                $stmtLA = $pdo->prepare('SELECT attempts, last_attempt FROM login_attempts WHERE ip = ? LIMIT 1');
                $stmtLA->execute([$ip]);
                $la = $stmtLA->fetch(PDO::FETCH_ASSOC);
                if ($la && intval($la['attempts']) >= $maxAttempts) {
                    // Block the IP as fallback
                    $insB = $pdo->prepare('INSERT INTO blocked_ips (ip, reason, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE reason = VALUES(reason)');
                    $insB->execute([$ip, 'Exceeded login attempts']);
                    $error = 'Too many login attempts. Your IP has been temporarily blocked.';
                }
            }
        } catch (Throwable $e) { error_log('login rate-check error: ' . $e->getMessage()); }

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
                // On successful login, clear any login_attempts record for this IP
                try {
                    $stmtDel = $pdo->prepare('DELETE FROM login_attempts WHERE ip = ? OR email = ?');
                    $stmtDel->execute([$ip, $email]);
                } catch (Throwable $e) { error_log('clear login attempts failed: ' . $e->getMessage()); }

                header("Location: pages/index.php");
                exit;
            }
        } else {
            $error = "Invalid email or password.";
            // record failed attempt
            try {
                if (!empty($ip)) {
                    $stmtUp = $pdo->prepare('INSERT INTO login_attempts (email, ip, attempts, last_attempt) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()');
                    $stmtUp->execute([$email, $ip]);
                    // If attempts now exceed threshold, block IP
                    $stmtChk = $pdo->prepare('SELECT attempts FROM login_attempts WHERE ip = ? LIMIT 1');
                    $stmtChk->execute([$ip]);
                    $cur = $stmtChk->fetchColumn();
                    if ($cur !== false && intval($cur) >= $maxAttempts) {
                        $insB = $pdo->prepare('INSERT INTO blocked_ips (ip, reason, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE reason = VALUES(reason)');
                        $insB->execute([$ip, 'Exceeded login attempts']);
                    }
                }
            } catch (Throwable $e) { error_log('record login attempt failed: ' . $e->getMessage()); }
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
    <link rel="stylesheet" href="./assets/css/admin.css">
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
    <img src="./assets/img/hq-logo.jpeg" alt="Academy Logo" class="brand-logo" style="margin-bottom:1rem;">
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

        <?php if (!empty($recfg['site_key'])): ?>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <script>
                // Inject a grecaptcha widget into the login form by appending a div
                (function(){
                    var f = document.querySelector('form');
                    if (!f) return;
                    var w = document.createElement('div');
                    w.className = 'g-recaptcha';
                    w.setAttribute('data-sitekey','<?= htmlspecialchars($recfg['site_key']) ?>');
                    w.style.marginTop = '12px';
                    f.insertBefore(w, f.querySelector('button'));
                })();
            </script>
        <?php endif; ?>

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
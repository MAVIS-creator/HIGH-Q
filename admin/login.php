<?php
session_start();
require './includes/db.php';
require './includes/functions.php';
require './includes/csrf.php';
require './includes/auth.php';

$recfg = file_exists(__DIR__ . '/config/recaptcha.php') ? require __DIR__ . '/config/recaptcha.php' : (file_exists(__DIR__ . '/../config/recaptcha.php') ? require __DIR__ . '/../config/recaptcha.php' : ['site_key'=>'','secret'=>'']);

$error = '';
$csrfToken = generateToken('login_form');
$allowAdminSignup = true;
$bruteForceEnabled = true;
$globalTwoFactorRequired = false;
try {
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($totalUsers > 0 && function_exists('hqAdminRegistrationEnabled')) {
        $allowAdminSignup = hqAdminRegistrationEnabled($pdo);
    }
    $settings = function_exists('hqLoadSystemSettings') ? hqLoadSystemSettings($pdo) : [];
    if (isset($settings['advanced']) && is_array($settings['advanced']) && array_key_exists('brute_force', $settings['advanced'])) {
        $bruteForceEnabled = (bool)$settings['advanced']['brute_force'];
    }
    if (isset($settings['security']) && is_array($settings['security']) && array_key_exists('two_factor', $settings['security'])) {
        $globalTwoFactorRequired = (bool)$settings['security']['two_factor'];
    }
} catch (Throwable $e) {
    $allowAdminSignup = true;
    $bruteForceEnabled = true;
    $globalTwoFactorRequired = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    if (!verifyToken('login_form', $token)) {
        $error = "Invalid CSRF token. Please refresh and try again.";
    } else {
        if (empty($error)) {
            $email    = trim($_POST['email']);
            $password = trim($_POST['password']);

            $stmt = $pdo->prepare("SELECT u.*, r.slug AS role_slug, r.name AS role_name 
                                   FROM users u 
                                   LEFT JOIN roles r ON u.role_id = r.id 
                                   WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            $maxAttempts = 5;
            try {
                $stmtS = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
                $stmtS->execute(['system_settings']);
                $val = $stmtS->fetchColumn();
                $j = $val ? json_decode($val, true) : [];
                $maxAttempts = intval($j['advanced']['max_login_attempts'] ?? $maxAttempts);
                if ($maxAttempts < 1) $maxAttempts = 5;
            } catch (Throwable $e) { }

            $ip = function_exists('hqAdminClientIp') ? hqAdminClientIp() : ($_SERVER['REMOTE_ADDR'] ?? '');
            try {
                if ($bruteForceEnabled && !empty($ip)) {
                    $stmtLA = $pdo->prepare('SELECT attempts, last_attempt FROM login_attempts WHERE ip = ? LIMIT 1');
                    $stmtLA->execute([$ip]);
                    $la = $stmtLA->fetch(PDO::FETCH_ASSOC);
                    if ($la && intval($la['attempts']) >= $maxAttempts) {
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
                    $userHasGoogle2fa = !empty($user['google2fa_enabled']) && !empty($user['google2fa_secret']);
                    if ($globalTwoFactorRequired || $userHasGoogle2fa) {
                        $_SESSION['pending_admin_login'] = [
                            'id' => (int)$user['id'],
                            'name' => $user['name'],
                            'email' => $user['email'],
                            'role_id' => (int)$user['role_id'],
                            'role_slug' => $user['role_slug'],
                            'role_name' => $user['role_name'],
                            'google2fa_enabled' => $userHasGoogle2fa,
                            'force_two_factor' => $globalTwoFactorRequired,
                            'ip' => $ip,
                        ];
                        unset($_SESSION['google2fa_temp_secret'], $_SESSION['user']);
                        header("Location: verify_2fa.php");
                        exit;
                    }

                    if (function_exists('hqFinalizeAdminLoginSession')) {
                        hqFinalizeAdminLoginSession($pdo, $user, $ip);
                    }

                    header("Location: index.php?pages=dashboard");
                    exit;
                }
            } else {
                $error = "Invalid email or password.";
                try {
                    if ($bruteForceEnabled && !empty($ip)) {
                        $stmtUp = $pdo->prepare('INSERT INTO login_attempts (email, ip, attempts, last_attempt) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()');
                        $stmtUp->execute([$email, $ip]);
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - HIGH Q SOLID ACADEMY</title>
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
            <!-- Logo -->
            <div class="auth-logo">
                <img src="./assets/img/hq-logo.jpeg" alt="HIGH Q SOLID ACADEMY">
            </div>

            <!-- Title -->
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to access your admin dashboard</p>
            
            <div style="text-align: center;">
                <span class="auth-tagline">
                    <i class='bx bx-star'></i>
                    Always Ahead of Others
                </span>
            </div>

            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><i class='bx bx-error-circle'></i></span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($globalTwoFactorRequired): ?>
                <div class="alert" style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;margin-bottom:18px;padding:12px 14px;border-radius:10px;">
                    <span class="alert-icon"><i class='bx bx-shield-quarter'></i></span>
                    <span>Two-factor authentication is required for admin access. After your password is verified, you'll complete a Google Authenticator step.</span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="auth-form" id="loginForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="you@example.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                            <i class='bx bx-hide'></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class='bx bx-log-in'></i>&nbsp; Sign In
                </button>
            </form>

            <!-- Links -->
            <div class="auth-links">
                <a href="forgot_password.php" class="auth-link">
                    <i class='bx bx-lock-alt'></i> Forgot your password?
                </a>
                <?php if ($allowAdminSignup): ?>
                <a href="signup.php" class="auth-link auth-link-primary">
                    <i class='bx bx-user-plus'></i> Don't have an account? Sign up
                </a>
                <?php endif; ?>
            </div>

            <!-- Features -->
            <div class="auth-features">
                <div class="feature-item">
                    <div class="feature-icon"><i class='bx bx-shield-quarter'></i></div>
                    <div>
                        <div class="feature-title">Secure</div>
                        <div class="feature-desc">256-bit SSL</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class='bx bx-user-check'></i></div>
                    <div>
                        <div class="feature-title">Role-Based</div>
                        <div class="feature-desc">Access Control</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class='bx bx-time-five'></i></div>
                    <div>
                        <div class="feature-title">24/7</div>
                        <div class="feature-desc">Available</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="auth-footer">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED. All rights reserved.
    </footer>

    <?php if (!empty($recfg['site_key'])): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>

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

        // Add loading state on form submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('btn-loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>

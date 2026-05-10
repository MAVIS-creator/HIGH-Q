<?php
session_start();
require './includes/db.php';
require './includes/functions.php';
require './includes/csrf.php';
require './includes/auth.php';
require __DIR__ . '/../vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

$pending = $_SESSION['pending_admin_login'] ?? null;
if (!is_array($pending) || empty($pending['id']) || empty($pending['email'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$pending['id'];
$userName = (string)($pending['name'] ?? 'Admin');
$userEmail = (string)($pending['email'] ?? '');
$globalRequired = !empty($pending['force_two_factor']);
$error = '';
$info = '';
$csrfToken = generateToken('verify_2fa_form');

if (isset($_GET['cancel']) && $_GET['cancel'] === '1') {
    unset($_SESSION['pending_admin_login'], $_SESSION['google2fa_temp_secret']);
    header('Location: login.php');
    exit;
}

$userRecord = null;
try {
    $stmt = $pdo->prepare("SELECT u.*, r.slug AS role_slug, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'Unable to load your account for two-factor verification.';
}

if (!$userRecord) {
    unset($_SESSION['pending_admin_login'], $_SESSION['google2fa_temp_secret']);
    header('Location: login.php');
    exit;
}

$hasPersisted2fa = !empty($userRecord['google2fa_enabled']) && !empty($userRecord['google2fa_secret']);
$needsSetup = $globalRequired && !$hasPersisted2fa;
$tempSecret = $_SESSION['google2fa_temp_secret'] ?? null;
$qrUrl = null;

if ($needsSetup && empty($tempSecret) && empty($error)) {
    try {
        $g = new GoogleAuthenticator();
        $tempSecret = $g->generateSecret();
        $_SESSION['google2fa_temp_secret'] = $tempSecret;
    } catch (Throwable $e) {
        $error = 'We could not start Google Authenticator setup right now.';
    }
}

if ($needsSetup && !empty($tempSecret)) {
    $qrUrl = GoogleQrUrl::generate($userEmail, $tempSecret, 'HIGH-Q');
    $info = 'Scan the QR code with Google Authenticator, then enter the 6-digit code to finish signing in.';
} elseif (!$needsSetup) {
    $info = 'Enter the 6-digit code from your Google Authenticator app to finish signing in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    $action = $_POST['action'] ?? 'verify';
    $code = trim((string)($_POST['code'] ?? ''));

    if (!verifyToken('verify_2fa_form', $token)) {
        $error = 'Invalid CSRF token. Please refresh and try again.';
    } elseif (!preg_match('/^[0-9]{6}$/', $code)) {
        $error = 'Please enter a valid 6-digit authentication code.';
    } else {
        try {
            $g = new GoogleAuthenticator();

            if ($needsSetup) {
                $secret = $_SESSION['google2fa_temp_secret'] ?? null;
                if (!$secret) {
                    $error = 'Your setup session expired. Please log in again to restart 2FA setup.';
                } elseif (!$g->checkCode($secret, $code)) {
                    $error = 'The setup code is invalid or expired. Please try the latest code from your app.';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET google2fa_secret = ?, google2fa_enabled = 1 WHERE id = ?');
                    $stmt->execute([$secret, $userId]);
                    $userRecord['google2fa_secret'] = $secret;
                    $userRecord['google2fa_enabled'] = 1;
                    unset($_SESSION['google2fa_temp_secret']);
                    if (function_exists('sendAdminChangeNotification')) {
                        try {
                            sendAdminChangeNotification(
                                $pdo,
                                'Google Authenticator Enabled During Login',
                                [
                                    'User ID' => $userId,
                                    'Action' => '2FA enabled from mandatory login setup'
                                ],
                                $userId
                            );
                        } catch (Throwable $_) {
                        }
                    }
                    if (function_exists('hqFinalizeAdminLoginSession')) {
                        hqFinalizeAdminLoginSession($pdo, $userRecord, (string)($pending['ip'] ?? ''));
                    }
                    header('Location: index.php?pages=dashboard');
                    exit;
                }
            } else {
                $secret = (string)($userRecord['google2fa_secret'] ?? '');
                if ($secret === '') {
                    $error = 'Your account does not have a Google Authenticator secret stored.';
                } elseif (!$g->checkCode($secret, $code)) {
                    $error = 'The authentication code is invalid or expired. Please try again.';
                } else {
                    if (function_exists('hqFinalizeAdminLoginSession')) {
                        hqFinalizeAdminLoginSession($pdo, $userRecord, (string)($pending['ip'] ?? ''));
                    }
                    header('Location: index.php?pages=dashboard');
                    exit;
                }
            }
        } catch (Throwable $e) {
            $error = 'We could not complete the two-factor verification right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification - HIGH Q SOLID ACADEMY</title>
    <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/auth.css">
    <link rel="stylesheet" href="./assets/css/admin-minimal.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card" style="max-width:560px;">
            <div class="auth-logo">
                <img src="./assets/img/hq-logo.jpeg" alt="HIGH Q SOLID ACADEMY">
            </div>

            <h1 class="auth-title"><?= $needsSetup ? 'Set Up Two-Factor Authentication' : 'Two-Factor Verification' ?></h1>
            <p class="auth-subtitle">Hello <?= htmlspecialchars($userName) ?>, <?= htmlspecialchars($info) ?></p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><i class='bx bx-error-circle'></i></span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($needsSetup && $qrUrl): ?>
                <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:18px;margin-bottom:18px;text-align:center;">
                    <img src="<?= htmlspecialchars($qrUrl) ?>" alt="Google Authenticator QR code" style="max-width:220px;width:100%;height:auto;border-radius:10px;background:#fff;padding:10px;">
                    <p style="margin:14px 0 6px;font-weight:600;">Manual setup key</p>
                    <code style="display:inline-block;background:#111827;color:#fff;padding:10px 12px;border-radius:8px;word-break:break-all;"><?= htmlspecialchars((string)$tempSecret) ?></code>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="<?= $needsSetup ? 'setup_verify' : 'verify' ?>">

                <div class="form-group">
                    <label class="form-label"><?= $needsSetup ? 'Code From Newly Added Account' : 'Authenticator Code' ?></label>
                    <input type="text" name="code" class="form-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autocomplete="one-time-code">
                </div>

                <button type="submit" class="btn-primary">
                    <i class='bx bx-check-shield'></i>&nbsp; <?= $needsSetup ? 'Enable and Continue' : 'Verify and Continue' ?>
                </button>
            </form>

            <div class="auth-links">
                <a href="verify_2fa.php?cancel=1" class="auth-link">
                    <i class='bx bx-arrow-back'></i> Back to login
                </a>
            </div>
        </div>
    </div>
</body>
</html>

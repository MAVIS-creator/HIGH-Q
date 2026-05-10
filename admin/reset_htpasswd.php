<?php
declare(strict_types=1);

use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
} catch (Throwable $e) {
}

$htpasswdFile = __DIR__ . DIRECTORY_SEPARATOR . '.htpasswd';
$logFile = __DIR__ . '/../storage/logs/htpasswd_reset.log';
$defaultUsername = 'admin';

function hq_reset_log(string $message, string $logFile): void
{
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($logFile, '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function hq_is_local_request(): bool
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $hostOnly = preg_replace('/:\d+$/', '', $host);
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');

    return in_array($hostOnly, ['localhost', '127.0.0.1', '::1'], true)
        || str_ends_with($hostOnly, '.localhost')
        || in_array($remote, ['127.0.0.1', '::1'], true);
}

function hq_has_valid_reset_access(): bool
{
    if (hq_is_local_request()) {
        return true;
    }

    $token = trim((string)($_ENV['ADMIN_HTPASSWD_RESET_TOKEN'] ?? ''));
    if ($token === '' || strlen($token) < 24) {
        return false;
    }

    $provided = trim((string)($_POST['access_token'] ?? $_GET['token'] ?? ''));
    if ($provided === '') {
        return false;
    }

    return hash_equals($token, $provided);
}

function hq_upsert_htpasswd_user(string $file, string $username, string $password): bool
{
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    if ($hash === false) {
        return false;
    }

    $lines = [];
    if (is_file($file)) {
        $existing = @file($file, FILE_IGNORE_NEW_LINES);
        if (is_array($existing)) {
            $lines = $existing;
        }
    }

    $updated = false;
    foreach ($lines as $idx => $line) {
        if (strpos($line, ':') === false) {
            continue;
        }
        [$existingUser] = explode(':', $line, 2);
        if (hash_equals($existingUser, $username)) {
            $lines[$idx] = $username . ':' . $hash;
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        $lines[] = $username . ':' . $hash;
    }

    $content = implode(PHP_EOL, array_filter($lines, static fn($line) => trim((string)$line) !== '')) . PHP_EOL;
    return @file_put_contents($file, $content, LOCK_EX) !== false;
}

$isLocal = hq_is_local_request();
$hasAccess = hq_has_valid_reset_access();
$resetTokenConfigured = strlen(trim((string)($_ENV['ADMIN_HTPASSWD_RESET_TOKEN'] ?? ''))) >= 24;
$error = null;
$success = null;

if (!$hasAccess) {
    http_response_code(403);
    hq_reset_log('Denied reset access from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' host=' . ($_SERVER['HTTP_HOST'] ?? 'unknown'), $logFile);
}

if ($hasAccess && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? $defaultUsername));
    $newPassword = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{3,64}$/', $username)) {
        $error = 'Use a valid username with 3-64 letters, numbers, dots, underscores, or hyphens.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 12) {
        $error = 'Password must be at least 12 characters.';
    } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/\d/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
        $error = 'Password must include uppercase, lowercase, number, and symbol.';
    } else {
        if (hq_upsert_htpasswd_user($htpasswdFile, $username, $newPassword)) {
            $success = 'The .htpasswd credential was updated successfully.';
            hq_reset_log('Updated .htpasswd username=' . $username . ' from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), $logFile);
        } else {
            $error = 'Could not write the .htpasswd file. Check file permissions on the admin folder.';
            hq_reset_log('Failed writing .htpasswd username=' . $username, $logFile);
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset .htpasswd</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: Arial, Helvetica, sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #111827 100%); color: #0f172a; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: min(100%, 560px); background: #fff; border-radius: 18px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #ffd600 0%, #f59e0b 100%); padding: 22px 26px; }
        .card-header h1 { margin: 0 0 6px; font-size: 1.7rem; color: #111827; }
        .card-header p { margin: 0; color: rgba(17, 24, 39, 0.82); }
        .card-body { padding: 26px; }
        .alert { border-radius: 12px; padding: 14px 16px; margin-bottom: 18px; font-size: 0.95rem; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-note { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .alert-warning { background: #fff7ed; color: #9a3412; border: 1px solid #fdba74; }
        .field { margin-bottom: 18px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; color: #111827; }
        input[type="text"], input[type="password"] { width: 100%; padding: 13px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 1rem; }
        input:focus { outline: none; border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18); }
        .btn { width: 100%; border: 0; border-radius: 12px; padding: 14px 16px; font-size: 1rem; font-weight: 700; background: linear-gradient(135deg, #ffd600 0%, #f59e0b 100%); color: #111827; cursor: pointer; }
        .meta { margin-top: 18px; font-size: 0.92rem; color: #475569; line-height: 1.6; }
        code { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 2px 6px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h1>Reset .htpasswd</h1>
            <p>Update the outer admin gate password safely.</p>
        </div>
        <div class="card-body">
            <?php if (!$hasAccess): ?>
                <div class="alert alert-error">
                    Access denied. This reset page is only available on localhost or with a valid reset token.
                </div>
                <div class="meta">
                    For hosted access, set a strong token in <code>.env</code> as <code>ADMIN_HTPASSWD_RESET_TOKEN</code> and open this page with <code>?token=YOUR_TOKEN</code>.
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div class="alert alert-note">
                    This controls the extra browser prompt before anyone can even reach the admin area.
                </div>
                <?php if (!$isLocal && !$resetTokenConfigured): ?>
                    <div class="alert alert-warning">
                        No hosted reset token is configured yet. Localhost access still works, but hosted recovery should not be left without a token.
                    </div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <?php if (!$isLocal): ?>
                        <input type="hidden" name="access_token" value="<?= htmlspecialchars((string)($_GET['token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <div class="field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars((string)($_POST['username'] ?? $defaultUsername), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="field">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" minlength="12" required autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="12" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn">Update .htpasswd Password</button>
                </form>
                <div class="meta">
                    <strong>Recommended:</strong> use a long unique password here, different from the admin dashboard login password.<br>
                    File path: <code><?= htmlspecialchars($htpasswdFile, ENT_QUOTES, 'UTF-8') ?></code>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


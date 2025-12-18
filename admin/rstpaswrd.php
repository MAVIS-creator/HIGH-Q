<?php
/**
 * .htpasswd Password Reset Tool
 * 
 * Run this script in your browser to reset your admin .htpasswd password
 * Access: http://localhost/HIGH-Q/admin/reset_htpasswd.php
 * 
 * IMPORTANT: Delete or rename this file after use for security!
 */

$htpasswdFile = realpath(__DIR__) . DIRECTORY_SEPARATOR . '.htpasswd';
$username = 'admin';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword)) {
        $error = "Password cannot be empty";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match";
    } elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Prefer bcrypt (Apache supports $2y$ hashes). Fallback to APR1-MD5 if bcrypt is unavailable.
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        if ($hash === false) {
            $hash = crypt($newPassword, '$apr1$' . substr(base64_encode(random_bytes(6)), 0, 8) . '$');
        }

        $content = "$username:$hash\n";

        if (file_put_contents($htpasswdFile, $content, LOCK_EX)) {
            $success = "Password successfully updated!";
            $showPassword = $newPassword; // Store to display once
        } else {
            $error = "Failed to write to .htpasswd file. Check permissions at: {$htpasswdFile}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin Password</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:active {
            transform: translateY(0);
        }
        .password-display {
            background: #f7fafc;
            padding: 16px;
            border-radius: 8px;
            border: 2px dashed #cbd5e0;
            margin-top: 20px;
        }
        .password-display strong {
            color: #2d3748;
            display: block;
            margin-bottom: 8px;
        }
        .password-display code {
            background: #2d3748;
            color: #48bb78;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
        }
        .warning-box {
            background: #fffaf0;
            border: 2px solid #fbd38d;
            padding: 16px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .warning-box strong {
            color: #c05621;
            display: block;
            margin-bottom: 8px;
        }
        .warning-box p {
            color: #744210;
            font-size: 13px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Reset Admin Password</h1>
        <p class="subtitle">Update your .htpasswd authentication password</p>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><i class='bx bx-error-circle'></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><i class='bx bx-check-circle'></i> <?= htmlspecialchars($success) ?></div>
            <?php if (isset($showPassword)): ?>
                <div class="password-display">
                    <strong>Your New Password (Save this!):</strong>
                    <code><?= htmlspecialchars($showPassword) ?></code>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" value="admin" readonly>
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="Enter new password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Confirm new password">
            </div>

            <button type="submit" class="btn">Reset Password</button>
        </form>

        <div class="warning-box">
            <strong><i class='bx bx-error'></i> Security Warning</strong>
            <p>
                This script allows anyone who can access it to reset your admin password. 
                <strong>Delete or rename this file immediately after use!</strong>
                You can always access it again by renaming it back when needed.
            </p>
        </div>
    </div>
</body>
</html>

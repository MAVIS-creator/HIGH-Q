<?php
session_start();
require './includes/db.php';
require './includes/functions.php';
require './includes/csrf.php';
require __DIR__ . '/../vendor/autoload.php';

// Load reCAPTCHA config
$recfg = file_exists(__DIR__ . '/config/recaptcha.php')
    ? require __DIR__ . '/config/recaptcha.php'
    : (file_exists(__DIR__ . '/../config/recaptcha.php')
        ? require __DIR__ . '/../config/recaptcha.php'
        : ['site_key' => '', 'secret' => '', 'enabled' => false]);

$errors = [];
$success = '';

function resizeAndCrop($srcPath, $destPath, $targetWidth = 300, $targetHeight = 300)
{
    if (!function_exists('imagecreatetruecolor') || (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng') && !function_exists('imagecreatefromwebp'))) {
        try { @file_put_contents(__DIR__ . '/../storage/logs/admin_signup_errors.log', "[" . date('c') . "] GD extension not available — skipping avatar resize for: $srcPath\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
        return true;
    }

    [$srcWidth, $srcHeight, $srcType] = getimagesize($srcPath);

    switch ($srcType) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($srcPath);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($srcPath);
            break;
        case IMAGETYPE_WEBP:
            $srcImage = imagecreatefromwebp($srcPath);
            break;
        default:
            return false;
    }

    $srcAspect = $srcWidth / $srcHeight;
    $targetAspect = $targetWidth / $targetHeight;

    if ($srcAspect > $targetAspect) {
        $newHeight = $srcHeight;
        $newWidth = intval($srcHeight * $targetAspect);
        $srcX = intval(($srcWidth - $newWidth) / 2);
        $srcY = 0;
    } else {
        $newWidth = $srcWidth;
        $newHeight = intval($srcWidth / $targetAspect);
        $srcX = 0;
        $srcY = intval(($srcHeight - $newHeight) / 2);
    }

    $destImage = imagecreatetruecolor($targetWidth, $targetHeight);

    if ($srcType == IMAGETYPE_PNG || $srcType == IMAGETYPE_WEBP) {
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
    }

    imagecopyresampled($destImage, $srcImage, 0, 0, $srcX, $srcY, $targetWidth, $targetHeight, $newWidth, $newHeight);

    switch ($srcType) {
        case IMAGETYPE_JPEG:
            imagejpeg($destImage, $destPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($destImage, $destPath, 8);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($destImage, $destPath, 90);
            break;
    }

    imagedestroy($srcImage);
    imagedestroy($destImage);

    return true;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['_csrf_token'] ?? '';

        // reCAPTCHA validation
        if (!empty($recfg['enabled'])) {
            $rc = $_POST['g-recaptcha-response'] ?? '';
            if (!$rc) {
                $errors[] = 'Please complete the reCAPTCHA verification.';
            } else {
                $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
                $params = http_build_query([
                    'secret' => $recfg['secret'] ?? '',
                    'response' => $rc,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
                $ctx = stream_context_create(['http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $params,
                    'timeout' => 5
                ]]);
                $res = @file_get_contents($verifyUrl, false, $ctx);
                $j = $res ? json_decode($res, true) : null;
                if (!$j || empty($j['success'])) {
                    $errors[] = 'reCAPTCHA validation failed. Please try again.';
                }
            }
        }

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $avatar = $_FILES['avatar'] ?? null;

        if (!verifyToken('signup_form', $token)) {
            $errors[] = "Invalid CSRF token. Please refresh and try again.";
        }

        if (!$name || !$email || !$password) $errors[] = "Name, Email, and Password are required.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
        if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = "Email already registered.";

        // Avatar upload
        $avatarPath = null;
        if ($avatar && $avatar['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 2 * 1024 * 1024;

            if (!in_array($avatar['type'], $allowedTypes)) {
                $errors[] = "Only JPG, PNG, and WEBP images are allowed.";
            } elseif ($avatar['size'] > $maxSize) {
                $errors[] = "Avatar too large. Max 2MB allowed.";
            } else {
                $uploadDir = __DIR__ . "/assets/avatars/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $ext = strtolower(pathinfo($avatar['name'], PATHINFO_EXTENSION));
                $fileName = uniqid("avatar_") . "." . $ext;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($avatar['tmp_name'], $targetPath)) {
                    resizeAndCrop($targetPath, $targetPath, 300, 300);
                    $avatarPath = "uploads/avatars/" . $fileName;
                } else {
                    $errors[] = "Failed to upload avatar.";
                }
            }
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = 1");
            $stmt->execute();
            $isFirstAdmin = $stmt->fetchColumn() == 0;

            if ($isFirstAdmin) {
                $role_id = 1;
            } else {
                try {
                    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE slug = ? LIMIT 1');
                    $roleStmt->execute(['applicant']);
                    $r = $roleStmt->fetch(PDO::FETCH_ASSOC);
                    if ($r && !empty($r['id'])) {
                        $role_id = (int)$r['id'];
                    } else {
                        $ins = $pdo->prepare('INSERT INTO roles (name, slug, max_count) VALUES (?, ?, ?)');
                        $ins->execute(['Applicant', 'applicant', null]);
                        $role_id = (int)$pdo->lastInsertId();
                    }
                } catch (Throwable $_) {
                    $role_id = 2;
                }
            }
            $is_active = $isFirstAdmin ? 1 : 0;

            $verificationToken = $is_active ? null : bin2hex(random_bytes(32));
            $verifiedAt = $is_active ? date('Y-m-d H:i:s') : null;
            $sentAt = $is_active ? null : date('Y-m-d H:i:s');

            $stmt = $pdo->prepare("INSERT INTO users (role_id, name, phone, email, password, avatar, is_active, email_verification_token, email_verified_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$role_id, $name, $phone, $email, $hashedPassword, $avatarPath, $is_active, $verificationToken, $verifiedAt]);

            if (!$is_active) {
                $uid = $pdo->lastInsertId();
                $uupd = $pdo->prepare('UPDATE users SET email_verification_sent_at = ? WHERE id = ?');
                $uupd->execute([$sentAt, $uid]);

                $verifyUrl = admin_url('verify_email.php?token=' . urlencode($verificationToken));

                $subject = "Verify your email for HIGH Q SOLID ACADEMY";
                $html = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #ffd600, #ff4b2b); padding: 30px; text-align: center;'>
                        <h1 style='color: #fff; margin: 0;'>HIGH Q SOLID ACADEMY</h1>
                    </div>
                    <div style='padding: 30px; background: #fff;'>
                        <p style='font-size: 16px;'>Hello <strong>$name</strong>,</p>
                        <p style='font-size: 16px;'>Thanks for registering! Your application is pending admin approval.</p>
                        <p style='font-size: 16px;'>Please verify your email:</p>
                        <a href=\"{$verifyUrl}\" style='display: inline-block; padding: 14px 28px; background: #1a73e8; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;'>Verify Email</a>
                    </div>
                </div>";
                @sendEmail($email, $subject, $html);

                try {
                    $admins = $pdo->prepare('SELECT email, name FROM users WHERE role_id = 1');
                    $admins->execute();
                    $reviewUrl = admin_url('index.php?pages=users');
                    $notifySubject = "New admin application submitted";
                    $notifyHtml = "<p>A new admin application was submitted by <strong>" . htmlspecialchars($name) . "</strong> (" . htmlspecialchars($email) . ").</p><p>Review applications: <a href=\"{$reviewUrl}\">Admin applications</a></p>";
                    while ($a = $admins->fetch(PDO::FETCH_ASSOC)) {
                        sendEmail($a['email'], $notifySubject, $notifyHtml);
                    }
                } catch (Throwable $_) {}

                $success = "Application submitted! Please check your email to verify your account.";
            } else {
                $subject = "Welcome to HIGH Q SOLID ACADEMY";
                $html = "<p>Hello $name,</p><p>Your account has been created with Main Admin privileges.</p>";
                sendEmail($email, $subject, $html);
                $success = "Account created successfully! You can now log in.";
            }
        }
    } catch (Throwable $e) {
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logDir . '/admin_signup_errors.log', "[" . date('c') . "] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND | LOCK_EX);
        $errors[] = "Server error occurred. Please try again or contact support.";
    }
}

$csrfToken = generateToken('signup_form');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - HIGH Q SOLID ACADEMY</title>
    <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/auth.css">
    <style>
        .auth-container { max-width: 480px; }
        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--hq-gray);
            border-radius: var(--radius-md);
            border: 2px dashed #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .avatar-upload:hover {
            border-color: var(--hq-blue);
            background: #f0f7ff;
        }
        .avatar-preview {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-full);
            object-fit: cover;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--hq-gray-dark);
            overflow: hidden;
        }
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-text {
            flex: 1;
        }
        .avatar-text h4 {
            margin: 0 0 4px;
            font-size: 0.9rem;
            color: var(--hq-black);
        }
        .avatar-text p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--hq-gray-dark);
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <!-- Logo -->
            <div class="auth-logo">
                <img src="./assets/img/hq-logo.jpeg" alt="HIGH Q SOLID ACADEMY">
            </div>

            <!-- Title -->
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Register for admin access to HIGH Q SOLID ACADEMY</p>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon"><i class='bx bx-check-circle'></i></span>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
                <a href="login.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none; margin-top: 16px;">
                    <i class='bx bx-log-in'></i>&nbsp; Go to Login
                </a>
            <?php else: ?>
                <!-- Error Messages -->
                <?php foreach ($errors as $e): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon"><i class='bx bx-error-circle'></i></span>
                        <span><?= htmlspecialchars($e) ?></span>
                    </div>
                <?php endforeach; ?>

                <!-- Signup Form -->
                <form method="POST" enctype="multipart/form-data" class="auth-form" id="signupForm">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-input" placeholder="Enter your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-input" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-input" placeholder="+234 xxx xxx xxxx" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-input" placeholder="Min. 8 characters" required autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                <i class='bx bx-hide'></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Profile Photo (Optional)</label>
                        <label class="avatar-upload" for="avatarInput">
                            <div class="avatar-preview" id="avatarPreview">
                                <i class='bx bx-user'></i>
                            </div>
                            <div class="avatar-text">
                                <h4>Upload Photo</h4>
                                <p>JPG, PNG or WEBP. Max 2MB</p>
                            </div>
                        </label>
                        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                    </div>

                    <?php if (!empty($recfg['enabled']) && !empty($recfg['site_key'])): ?>
                        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recfg['site_key']) ?>"></div>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    <?php endif; ?>

                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class='bx bx-user-plus'></i>&nbsp; Create Account
                    </button>
                </form>
            <?php endif; ?>

            <!-- Links -->
            <div class="auth-links">
                <a href="login.php" class="auth-link auth-link-primary">
                    <i class='bx bx-log-in'></i> Already have an account? Sign in
                </a>
            </div>

            <!-- Info -->
            <div class="alert alert-info" style="animation: none; margin-top: 20px;">
                <span class="alert-icon"><i class='bx bx-info-circle'></i></span>
                <div style="font-size: 0.85rem;">
                    <strong>Note:</strong> New registrations require admin approval. You'll receive an email once your account is activated.
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="auth-footer">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED. All rights reserved.
    </footer>

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

        // Avatar preview
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Loading state
        document.getElementById('signupForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('btn-loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>

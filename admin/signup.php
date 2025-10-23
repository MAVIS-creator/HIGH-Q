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
    // If GD image functions are not available, skip resizing to avoid fatal errors
    if (!function_exists('imagecreatetruecolor') || (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng') && !function_exists('imagecreatefromwebp'))) {
        // Log a warning to admin logs so the server admin can enable the GD extension if desired
        try { @file_put_contents(__DIR__ . '/../storage/logs/admin_signup_errors.log', "[" . date('c') . "] GD extension not available — skipping avatar resize for: $srcPath\n", FILE_APPEND | LOCK_EX); } catch (Throwable $_) {}
        return true; // treat as success so upload continues without resizing
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

        // reCAPTCHA - only validate when explicitly enabled in admin config
        if (!empty($recfg['enabled'])) {
            $rc = $_POST['g-recaptcha-response'] ?? '';
            if (!$rc) {
                $errors[] = 'Please complete the I am not a robot check.';
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

            // Default role assignment:
            // If this is the first admin, give role_id=1 (Main Admin). Otherwise try to find
            // a sensible fallback role from the `roles` table (sub-admin/user/student) and use that.
            // This avoids inserting NULL into a non-nullable role_id column.
            if ($isFirstAdmin) {
                $role_id = 1;
            } else {
                // Non-first signups are treated as applications.
                // Ensure there's an 'applicant' role to assign so the DB role_id is not NULL.
                try {
                    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE slug = ? LIMIT 1');
                    $roleStmt->execute(['applicant']);
                    $r = $roleStmt->fetch(PDO::FETCH_ASSOC);
                    if ($r && !empty($r['id'])) {
                        $role_id = (int)$r['id'];
                    } else {
                        // Create applicant role
                        $ins = $pdo->prepare('INSERT INTO roles (name, slug, max_count) VALUES (?, ?, ?)');
                        $ins->execute(['Applicant', 'applicant', null]);
                        $role_id = (int)$pdo->lastInsertId();
                    }
                } catch (Throwable $_) {
                    // If roles table is missing or query fails, fall back to 2 to avoid DB error
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
                // Application flow: user is inactive. Send verification email AND notify main admins to review.
                $uid = $pdo->lastInsertId();
                $uupd = $pdo->prepare('UPDATE users SET email_verification_sent_at = ? WHERE id = ?');
                $uupd->execute([$sentAt, $uid]);

                $appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? null);
                $verifyUrl = ($appUrl ? rtrim($appUrl, '/') : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'])) . '/admin/verify_email.php?token=' . urlencode($verificationToken);

                $subject = "Verify your email for HIGH Q SOLID ACADEMY";
                $html = "<p>Hello $name,</p>
                         <p>Thanks for registering with HIGH Q SOLID ACADEMY. Your application is pending admin approval.</p>
                         <p>Please verify your email address by clicking the link below:</p>
                         <p><a href=\"{$verifyUrl}\">Verify my email</a></p>";
                @sendEmail($email, $subject, $html);

                // Notify all main admins (role_id=1) about the new application with a review link
                try {
                    $admins = $pdo->prepare('SELECT email, name FROM users WHERE role_id = 1');
                    $admins->execute();
                    $reviewUrl = ($appUrl ? rtrim($appUrl, '/') : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://' ) . ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'])) . '/admin./pages/users.php';
                    $notifySubject = "New admin application submitted";
                    $notifyHtml = "<p>A new admin application was submitted by <strong>" . htmlspecialchars($name) . "</strong> (" . htmlspecialchars($email) . ").</p><p>Review applications: <a href=\"{$reviewUrl}\">Admin applications</a></p>";
                    while ($a = $admins->fetch(PDO::FETCH_ASSOC)) {
                        sendEmail($a['email'], $notifySubject, $notifyHtml);
                    }
                } catch (Throwable $_) {}

                $success = "Application submitted. Please check your email to verify your account; admins will review your application.";
            } else {
                $subject = "Welcome to HIGH Q SOLID ACADEMY";
                $html = "<p>Hello $name,</p>
                         <p>Your account has been created with Main Admin privileges.</p>";
                sendEmail($email, $subject, $html);
                $success = "Account created successfully! You can now log in.";
            }
        }
    } catch (Throwable $e) {
        // Ensure logs dir exists and write exception details for debugging
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logDir . '/admin_signup_errors.log', "[" . date('c') . "] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND | LOCK_EX);
        // Provide a friendly error to the UI (avoid exposing internals)
        $errors[] = "Server error occurred while creating account. Please check the admin logs or contact the site administrator.";
    }
}

$csrfToken = generateToken('signup_form');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - HIGH Q SOLID ACADEMY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./assets/css/admin.css">
    <style>
        :root {
            --hq-primary: #ffd600;
            --hq-accent: #ff4b2b;
            --hq-black: #0a0a0a;
            --hq-gray: #f3f4f6;
            --btn-padding: 0.8rem 1rem;
            --btn-radius: 8px;
            --btn-font-size: 1rem;
        }
        body {
            margin: 0;
            font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, Arial;
            background: linear-gradient(135deg, var(--hq-primary), var(--hq-accent));
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            padding: 20px;
        }
        .signup-card {
            background: var(--hq-gray);
            padding: 2rem;
            border-radius: 12px;
            width: 380px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
        }
        .signup-card h2 {
            color: var(--hq-accent);
            text-align: center;
            margin-bottom: 1rem;
        }
        label {
            display: block;
            font-weight: 700;
            margin-top: 0.9rem;
            color: var(--hq-black);
        }
        input {
            width: 100%;
            padding: 0.65rem;
            margin-top: 0.3rem;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        button {
            background: var(--hq-accent);
            color: #fff;
            border: none;
            padding: var(--btn-padding);
            width: 100%;
            font-size: var(--btn-font-size);
            border-radius: var(--btn-radius);
            cursor: pointer;
            margin-top: 1.2rem;
            font-weight: 700;
        }
        button:hover {
            background: var(--hq-primary);
            color: var(--hq-black);
        }
        .error, .success {
            padding: 0.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        .error {
            background: #ffefef;
            color: var(--hq-accent);
            border-color: var(--hq-accent);
        }
        .success {
            background: #ddffdd;
            color: green;
            border-color: green;
        }
        .footer {
            position: fixed;
            left: 0; bottom: 0;
            width: 100%;
            background: #fff;
            color: #555;
            padding: 10px 0;
            text-align: center;
            font-size: 0.9rem;
            box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.07);
        }
        label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
  color: #333;
}

/* Style the file input */
input[type="file"] {
  display: block;
  width: 100%;
  max-width: 350px;
  padding: 10px;
  border: 2px solid #ccc;
  border-radius: 8px;
  background-color: #f9f9f9;
  color: #555;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

/* When hovering */
input[type="file"]:hover {
  border-color: #3b82f6;
  background-color: #eef7ff;
}

/* Remove default ugly focus outline and make it nice */
input[type="file"]:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
}

    </style>
</head>
<body>

    <div class="signup-card">
        <h2>Create Account</h2>

        <?php if (!empty($errors) || $success): ?>
            <!-- Errors and success are shown via SweetAlert below -->
            <div id="serverMessages" style="display:none" data-errors="<?= htmlspecialchars(json_encode($errors)) ?>" data-success="<?= htmlspecialchars($success) ?>"></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <label>Name</label>
            <input type="text" name="name" placeholder="John Doe" required>
            <label>Phone Number</label>
            <input type="text" name="phone" placeholder="+234 801 234 5678">
            <label>Email</label>
            <input type="email" name="email" placeholder="you@example.com" required>
            <label>Password</label>
            <input type="password" name="password" placeholder="********" required>
            <label>Upload Passport Photo</label>
            <input type="file" name="avatar" accept="image/png, image/jpeg, image/webp" <?php if (!empty($recfg['enabled'])) echo 'required'; ?>>
            <button type="submit">Create Account</button>
        </form>

        <p style="margin-top: 1rem; text-align:center;">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>

    <div class="footer">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED - Admin Panel
    </div>

        <!-- SweetAlert2 for nicer alerts -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Show server-side messages (errors or success) using SweetAlert
            (function(){
                var container = document.getElementById('serverMessages');
                if (!container) return;
                try {
                    var errors = JSON.parse(container.getAttribute('data-errors') || '[]');
                } catch(e){ errors = []; }
                var success = container.getAttribute('data-success') || '';
                if (errors && errors.length) {
                    Swal.fire({
                        title: 'Error',
                        html: errors.map(e=>"<div>"+e+"</div>").join(''),
                        icon: 'error',
                        customClass: { popup: 'hq-swal' }
                    });
                } else if (success) {
                    Swal.fire({ title: 'Success', html: success, icon: 'success', customClass: { popup: 'hq-swal' } });
                }
            })();
        </script>

        <?php if (!empty($recfg['enabled']) && !empty($recfg['site_key'])): ?>
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <script>
                        (function(){
                                const f = document.querySelector('form');
                                if (!f) return;
                                const w = document.createElement('div');
                                w.className = 'g-recaptcha';
                                w.setAttribute('data-sitekey','<?= htmlspecialchars($recfg['site_key']) ?>');
                                w.style.marginTop = '12px';
                                f.insertBefore(w, f.querySelector('button'));
                        })();
                </script>
        <?php endif; ?>

</body>
</html>

<?php
session_start();
require './includes/db.php';
require './includes/functions.php'; // contains sendEmail()
require './includes/csrf.php';
require __DIR__ . '/../vendor/autoload.php';

// load admin-specific recaptcha config (ADMIN_RECAPTCHA_* or admin/config wrapper)
$recfg = file_exists(__DIR__ . '/config/recaptcha.php') ? require __DIR__ . '/config/recaptcha.php' : (file_exists(__DIR__ . '/../config/recaptcha.php') ? require __DIR__ . '/../config/recaptcha.php' : ['site_key'=>'','secret'=>'']);

$errors = [];
$success = '';

function resizeAndCrop($srcPath, $destPath, $targetWidth = 300, $targetHeight = 300)
{
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
            return false; // not supported
    }

    // Calculate crop area (center crop)
    $srcAspect = $srcWidth / $srcHeight;
    $targetAspect = $targetWidth / $targetHeight;

    if ($srcAspect > $targetAspect) {
        $newHeight = $srcHeight;
        $newWidth  = intval($srcHeight * $targetAspect);
        $srcX = intval(($srcWidth - $newWidth) / 2);
        $srcY = 0;
    } else {
        $newWidth  = $srcWidth;
        $newHeight = intval($srcWidth / $targetAspect);
        $srcX = 0;
        $srcY = intval(($srcHeight - $newHeight) / 2);
    }

    $destImage = imagecreatetruecolor($targetWidth, $targetHeight);

    // Preserve transparency
    if ($srcType == IMAGETYPE_PNG || $srcType == IMAGETYPE_WEBP) {
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
    }

    imagecopyresampled(
        $destImage,
        $srcImage,
        0,
        0,
        $srcX,
        $srcY,
        $targetWidth,
        $targetHeight,
        $newWidth,
        $newHeight
    );

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['_csrf_token'] ?? '';
    // verify recaptcha if configured
    if (!empty($recfg['secret'])) {
        $rc = $_POST['g-recaptcha-response'] ?? '';
        if (!$rc) { $errors[] = 'Please complete the I am not a robot check.'; }
        else {
            $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
            $params = http_build_query(['secret'=>$recfg['secret'], 'response'=>$rc, 'remoteip'=>$_SERVER['REMOTE_ADDR'] ?? '']);
            $opts = ['http'=>['method'=>'POST','header'=>'Content-type: application/x-www-form-urlencoded','content'=>$params,'timeout'=>5]];
            $ctx = stream_context_create($opts);
            $res = @file_get_contents($verifyUrl, false, $ctx);
            $j = $res ? json_decode($res, true) : null;
            if (!$j || empty($j['success'])) { $errors[] = 'reCAPTCHA validation failed. Please try again.'; }
        }
    }
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $avatar   = $_FILES['avatar'] ?? null; // NEW: avatar upload

    // CSRF check
    if (!verifyToken('signup_form', $token)) {
        $errors[] = "Invalid CSRF token. Please refresh and try again.";
    }

    // Basic validation
    if (!$name || !$email || !$password) {
        $errors[] = "Name, Email, and Password are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Email already registered.";
    }

    // Avatar validation
    $avatarPath = null;
    if ($avatar && $avatar['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($avatar['type'], $allowedTypes)) {
            $errors[] = "Only JPG, PNG, and WEBP images are allowed.";
        } elseif ($avatar['size'] > $maxSize) {
            $errors[] = "Avatar too large. Max 2MB allowed.";
        } else {
            $uploadDir = __DIR__ . "./assets/avatars/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
            $fileName = uniqid("avatar_") . "." . strtolower($ext);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($avatar['tmp_name'], $targetPath)) {
                // Crop & resize to passport style (300x300)
                resizeAndCrop($targetPath, $targetPath, 300, 300);
                $avatarPath = "uploads/avatars/" . $fileName;
            } else {
                $errors[] = "Failed to upload avatar.";
            }
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Check if this is the first admin
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = 1");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();

        if ($adminCount == 0) {
            $role_id   = 1; // Main Admin
            $is_active = 1; // Active immediately
        } else {
            $role_id   = NULL;
            $is_active = 0;
        }

        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (role_id, name, phone, email, password, avatar, is_active, email_verification_token, email_verified_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // For non-first users we will generate a verification token and set is_active to 0
        $verificationToken = null;
        $verifiedAt = null;
        if (!$is_active) {
            // generate a secure token
            $verificationToken = bin2hex(random_bytes(32));
            $sentAt = date('Y-m-d H:i:s');
        } else {
            $verifiedAt = date('Y-m-d H:i:s');
            $sentAt = null;
        }

        $stmt->execute([$role_id, $name, $phone, $email, $hashedPassword, $avatarPath, $is_active, $verificationToken, $verifiedAt]);

        // If non-active account, store sent timestamp and send verification email
        if (!$is_active) {
            try {
                // Update sent timestamp
                $uid = $pdo->lastInsertId();
                $uupd = $pdo->prepare('UPDATE users SET email_verification_sent_at = ? WHERE id = ?');
                $uupd->execute([$sentAt, $uid]);
            } catch (Throwable $e) { error_log('signup: failed to update sent_at: ' . $e->getMessage()); }

            // Build verification URL using APP_URL if available
            $appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? null);
            if ($appUrl) {
                $verifyUrl = rtrim($appUrl, '/') . '/admin/verify_email.php?token=' . urlencode($verificationToken);
            } else {
                $verifyUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://';
                $verifyUrl .= ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']) . '/admin/verify_email.php?token=' . urlencode($verificationToken);
            }

            $subject = "Verify your email for HIGH Q SOLID ACADEMY";
            $html = "<p>Hello $name,</p>
                     <p>Thanks for registering with HIGH Q SOLID ACADEMY.</p>
                     <p>Please verify your email address by clicking the link below (link expires in 72 hours):</p>
                     <p><a href=\"{$verifyUrl}\">Verify my email</a></p>
                     <p>If you did not create this account, ignore this email.</p>";

            // Send verification email (do not block signup on failure)
            @sendEmail($email, $subject, $html);

            $success = "Account created successfully! Please check your email to verify your account.";
        } else {
            $subject = "Welcome to HIGH Q SOLID ACADEMY";
            $html = "<p>Hello $name,</p>
                     <p>Your account has been created with Main Admin privileges.</p>
                     <p>You can now log in to the admin panel.</p>";
            sendEmail($email, $subject, $html);
            $success = "Account created successfully! You can now log in.";
        }
    }
}

// Generate CSRF token
$csrfToken = generateToken('signup_form');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - HIGH Q SOLID ACADEMY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/admin.css">
    <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">
    <style>
        :root{
            
    <style>
        :root {
            --hq-primary: #ffd600;
            --hq-accent: #ff4b2b;
            --hq-black: #0a0a0a;
            --hq-gray: #f3f4f6;
            --hq-red: #ff4b2b;
            --hq-yellow: #ffd600;
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
            padding: 40px 18px;
            height: 100vh;
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
            margin-bottom: 1rem;
            text-align: center;
            font-size: 1.25rem;
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
        .error {
            background: #ffefef;
            color: var(--hq-accent);
            padding: 0.5rem;
            border-left: 4px solid var(--hq-accent);
            margin-bottom: 1rem;
        }
        .success {
            background: #ddffdd;
            color: green;
            padding: 0.5rem;
            border-left: 4px solid green;
            margin-bottom: 1rem;
        }
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.3rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            font-size: 0.9rem;
            cursor: pointer;
        }
        input[type="file"]::-webkit-file-upload-button {
            background: var(--hq-red);
            color: var(--hq-gray);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            font-weight: 600;
        }
        input[type="file"]::-webkit-file-upload-button:hover {
            background: var(--hq-yellow);
            color: var(--hq-black);
        }
        input[type="file"]::file-selector-button {
            background: var(--hq-red);
            color: var(--hq-gray);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            font-weight: 600;
        }
        input[type="file"]::file-selector-button:hover {
            background: var(--hq-yellow);
            color: var(--hq-black);
        }
    </style>
        

        .signup-card {
            background: var(--hq-gray);
            padding: 2rem;
            border-radius: 12px;
            width: 380px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
        }

        .signup-card h2 {
            color: var(--hq-accent);
            margin-bottom: 1rem;
            text-align: center;
            font-size: 1.25rem
        }

        label {
            display: block;
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
            color: #fff;
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
            margin-bottom: 1rem
        }

        .success {
            background: #ddffdd;
            color: green;
            padding: 0.5rem;
            border-left: 4px solid green;
            margin-bottom: 1rem
        }

        /* File input styling */
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.3rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            font-size: 0.9rem;
            cursor: pointer;
        }

        input[type="file"]::-webkit-file-upload-button {
            background: var(--hq-red);
            color: var(--hq-gray);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            font-weight: 600;
        }

        input[type="file"]::-webkit-file-upload-button:hover {
            background: var(--hq-yellow);
            color: var(--hq-black);
        }

        input[type="file"]::file-selector-button {
            background: var(--hq-red);
            color: var(--hq-gray);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            font-weight: 600;
        }

        input[type="file"]::file-selector-button:hover {
            background: var(--hq-yellow);
            color: var(--hq-black);
        }
    </style>
</head>

    <div class="footer" style="position:fixed;left:0;bottom:0;width:100%;background:#fff;color:#555;padding:10px 0;text-align:center;z-index:1000;box-shadow:0 -2px 12px rgba(0,0,0,0.07);font-size:0.95rem;">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED - Admin Panel
    </div>
</body>

    <div class="signup-card">
        <h2>Create Account</h2>
        <?php if (!empty($errors)): ?>
            <div class="error"><?php foreach ($errors as $err) echo $err . "<br>"; ?></div>
        <?php elseif ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
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
            <div style="position:relative;">
                <input type="password" name="password" id="signup_password" placeholder="********" required>
                <span class="toggle-eye" onclick="togglePassword('signup_password', this)">👁️</span>
            </div>
            <label>Upload Passport Photo</label>
            <input type="file" name="avatar" accept="image/png, image/jpeg, image/webp" required>
            <button type="submit">Create Account</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
        <?php if (!empty($recfg['site_key'])): ?>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <script>
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
        <script>
            function togglePassword(fieldId, icon) {
                var input = document.getElementById(fieldId);
                if (input.type === "password") {
                    input.type = "text";
                    icon.innerHTML = "&#128064;";
                } else {
                    input.type = "password";
                    icon.innerHTML = "&#128065;";
                }
            }
        </script>

        <p style="margin-top: 1rem; text-align:center;">
            <a href="login.php">Already have an account? Log in</a>
        </p>
    </div>
</body>

</html>
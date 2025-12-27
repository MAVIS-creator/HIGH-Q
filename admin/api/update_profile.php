<?php
// admin/api/update_profile.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $currentEmail = $_SESSION['user']['email'] ?? '';

    if (empty($name)) {
        throw new Exception('Name is required');
    }

    // If email changed, require verification
    if (!empty($email) && $email !== $currentEmail) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        // Check if email is already used
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Email is already in use');
        }

        // Require email verification
        $verifiedEmail = $_SESSION['verified_email'] ?? null;
        if ($verifiedEmail !== $email) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Email change requires verification. Send verification code first.']);
            exit;
        }

        // Clear verified flag after use
        unset($_SESSION['verified_email']);
    }

    // If phone changed, require verification
    if (!empty($phone) && $phone !== ($_SESSION['user']['phone'] ?? '')) {
        if (!preg_match('/^[0-9\s\-\+\(\)]{10,}$/', $phone)) {
            throw new Exception('Invalid phone number');
        }

        $verifiedPhone = $_SESSION['verified_phone'] ?? null;
        if ($verifiedPhone !== $phone) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Phone change requires verification. Send verification code first.']);
            exit;
        }

        // Clear verified flag after use
        unset($_SESSION['verified_phone']);
    }

    // Handle avatar upload
    $avatarPath = null;
    if (!empty($_FILES['avatar']['name'])) {
        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed');
        }

        if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            throw new Exception('File too large. Maximum size is 2MB');
        }

        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
            $avatarPath = 'public/uploads/avatars/' . $filename;
        }
    }

    // Build update statement dynamically
    $updates = ['name = ?'];
    $params = [$name];

    if (!empty($email)) {
        $updates[] = 'email = ?';
        $params[] = $email;
    }

    if (!empty($phone)) {
        $updates[] = 'phone = ?';
        $params[] = $phone;
    }

    if ($avatarPath) {
        $updates[] = 'avatar = ?';
        $params[] = $avatarPath;
    }

    $params[] = $userId;

    $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($params);

    // Update session
    $_SESSION['user']['name'] = $name;
    if (!empty($email)) {
        $_SESSION['user']['email'] = $email;
    }
    if (!empty($phone)) {
        $_SESSION['user']['phone'] = $phone;
    }
    if ($avatarPath) {
        $_SESSION['user']['avatar'] = $avatarPath;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

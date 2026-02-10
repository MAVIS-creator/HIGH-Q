<?php
// admin/api/update_password.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];
$currentEmail = $_SESSION['user']['email'] ?? '';

$accountVerified = !empty($_SESSION['account_verified_until'])
    && time() <= (int)$_SESSION['account_verified_until']
    && !empty($_SESSION['account_verified_email'])
    && $_SESSION['account_verified_email'] === $currentEmail;

try {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        throw new Exception('All fields are required');
    }

    if ($newPassword !== $confirmPassword) {
        throw new Exception('New passwords do not match');
    }

    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    if (!$accountVerified) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Password change requires email verification.']);
        exit;
    }

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        throw new Exception('Current password is incorrect');
    }

    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);

    unset($_SESSION['account_verified_until'], $_SESSION['account_verified_email']);

    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

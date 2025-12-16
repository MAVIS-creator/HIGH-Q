<?php
// admin/api/google2fa_disable.php
// Disable Google Authenticator 2FA
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $password = $data['password'] ?? '';

    if (empty($password)) {
        throw new Exception('Password required to disable 2FA');
    }

    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception('Incorrect password');
    }

    // Disable 2FA
    $updateStmt = $pdo->prepare("UPDATE users SET google2fa_enabled = 0, google2fa_secret = NULL WHERE id = ?");
    $updateStmt->execute([$userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Google Authenticator disabled successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

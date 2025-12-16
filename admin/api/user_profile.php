<?php
// admin/api/user_profile.php
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
    // Get user data
    $stmt = $pdo->prepare("
         SELECT u.name, u.email, u.phone, u.avatar, r.name as role, 
             u.google2fa_enabled, u.google2fa_secret
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Convert to boolean for JSON
    $user['google2fa_enabled'] = !empty($user['google2fa_enabled']);
    
    // Don't send secret to client
    unset($user['google2fa_secret']);

    echo json_encode($user);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

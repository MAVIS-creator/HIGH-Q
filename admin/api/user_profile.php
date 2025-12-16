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
        SELECT u.name, u.email, u.phone, u.avatar, r.name as role
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Check if two-factor is enabled in site settings
    $settingsStmt = $pdo->query("SELECT two_factor FROM site_settings LIMIT 1");
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    
    $user['two_factor_enabled'] = !empty($settings['two_factor']);

    echo json_encode($user);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

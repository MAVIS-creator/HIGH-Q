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

    // Try to get google2fa_enabled status (gracefully handle if columns don't exist)
    $user['google2fa_enabled'] = false;
    try {
        $check = $pdo->prepare("SELECT google2fa_enabled FROM users WHERE id = ? LIMIT 1");
        $check->execute([$userId]);
        $result = $check->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $user['google2fa_enabled'] = !empty($result['google2fa_enabled']);
        }
    } catch (PDOException $e) {
        // Column doesn't exist, default to false
    }

    // Load preferences if available
    $prefs = null;
    try {
        $prefStmt = $pdo->prepare("SELECT preferences FROM users WHERE id = ? LIMIT 1");
        $prefStmt->execute([$userId]);
        $prefs = $prefStmt->fetchColumn();
    } catch (PDOException $e) {
        $prefs = null;
    }
    if (empty($prefs)) {
        try {
            $prefStmt = $pdo->prepare("SELECT preferences FROM user_preferences WHERE user_id = ? LIMIT 1");
            $prefStmt->execute([$userId]);
            $prefs = $prefStmt->fetchColumn();
        } catch (PDOException $e) {
            $prefs = null;
        }
    }
    $user['preferences'] = $prefs ? json_decode($prefs, true) : new stdClass();

    echo json_encode($user);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

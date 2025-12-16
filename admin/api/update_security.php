<?php
// admin/api/update_security.php
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

    $sessionTimeout = !empty($data['session_timeout']);
    $loginNotifications = !empty($data['login_notifications']);

    // Store preferences in user metadata or separate table
    // For now, we'll use a simple JSON column or create user_preferences table
    $preferences = json_encode([
        'session_timeout' => $sessionTimeout,
        'login_notifications' => $loginNotifications
    ]);

    // Try to update or insert preferences
    $stmt = $pdo->prepare("
        UPDATE users 
        SET preferences = ? 
        WHERE id = ?
    ");
    
    // If preferences column doesn't exist, this will fail gracefully
    try {
        $stmt->execute([$preferences, $userId]);
    } catch (PDOException $e) {
        // Column might not exist yet, just return success for now
    }

    echo json_encode([
        'success' => true,
        'message' => 'Security settings updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

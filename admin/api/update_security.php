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

    // Store preferences in user metadata or fallback table
    $preferences = json_encode([
        'session_timeout' => $sessionTimeout,
        'login_notifications' => $loginNotifications
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $updated = false;
    try {
        $stmt = $pdo->prepare("UPDATE users SET preferences = ? WHERE id = ?");
        $stmt->execute([$preferences, $userId]);
        $updated = true;
    } catch (PDOException $e) {
        $updated = false;
    }

    if (!$updated) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_preferences (
            user_id INT PRIMARY KEY,
            preferences TEXT NULL,
            updated_at DATETIME NULL
        )");
        $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, preferences, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE preferences = VALUES(preferences), updated_at = NOW()"
        );
        $stmt->execute([$userId, $preferences]);
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

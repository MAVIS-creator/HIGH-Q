<?php
// admin/api/update_account_preferences.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Invalid payload');
    }

    $preferences = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

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

    try {
        sendAdminChangeNotification(
            $pdo,
            'Account Preferences Updated',
            [
                'User ID' => $userId,
                'Updated Keys' => implode(', ', array_keys($data))
            ],
            (int)$userId
        );
    } catch (Throwable $e) {
    }

    echo json_encode(['success' => true, 'message' => 'Account preferences saved']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
// admin/api/google2fa_disable.php
// Disable Google Authenticator 2FA
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $password = $data['password'] ?? '';

    if ($password === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password required to disable 2FA']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        exit;
    }

    $updateStmt = $pdo->prepare('UPDATE users SET google2fa_enabled = 0, google2fa_secret = NULL WHERE id = ?');
    $updateStmt->execute([$userId]);

    unset($_SESSION['google2fa_temp_secret']);
    $_SESSION['user']['google2fa_enabled'] = false;

    if (function_exists('sendAdminChangeNotification')) {
        try {
            sendAdminChangeNotification(
                $pdo,
                'Google Authenticator Disabled',
                [
                    'User ID' => $userId,
                    'Action' => '2FA disabled'
                ],
                $userId
            );
        } catch (Throwable $_) {}
    }

    echo json_encode([
        'success' => true,
        'message' => 'Google Authenticator disabled successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    $msg = 'Database error while disabling 2FA';
    if (stripos($e->getMessage(), 'unknown column') !== false) {
        $msg = 'Missing google2fa columns on users table; please add google2fa_secret (VARCHAR) and google2fa_enabled (TINYINT)';
    }
    echo json_encode(['success' => false, 'message' => $msg, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to disable 2FA', 'error' => $e->getMessage()]);
}

<?php
// admin/api/google2fa_verify.php
// Verify Google Authenticator code and enable 2FA
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
$body = json_decode(file_get_contents('php://input'), true);
$code = trim($body['code'] ?? '');

if ($code === '' || !preg_match('/^[0-9]{6}$/', $code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a 6-digit code']);
    exit;
}

// Secret generated in setup
$secret = $_SESSION['google2fa_temp_secret'] ?? null;
if (!$secret) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Start setup first to get a QR code']);
    exit;
}

try {
    $g = new GoogleAuthenticator();
    if (!$g->checkCode($secret, $code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired code']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE users SET google2fa_secret = ?, google2fa_enabled = 1 WHERE id = ?');
    $stmt->execute([$secret, $userId]);

    $_SESSION['user']['google2fa_enabled'] = true;
    unset($_SESSION['google2fa_temp_secret']);

    echo json_encode(['success' => true, 'message' => 'Google Authenticator enabled']);
} catch (PDOException $e) {
    http_response_code(500);
    $msg = 'Database error while saving 2FA settings';
    if (stripos($e->getMessage(), 'unknown column') !== false) {
        $msg = 'Missing google2fa columns on users table; please add google2fa_secret (VARCHAR) and google2fa_enabled (TINYINT)';
    }
    echo json_encode(['success' => false, 'message' => $msg, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to verify code', 'error' => $e->getMessage()]);
}

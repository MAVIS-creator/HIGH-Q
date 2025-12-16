<?php
// admin/api/google2fa_setup.php
// Generate Google Authenticator secret and QR code (using Sonata library)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
$email = $_SESSION['user']['email'] ?? ('user' . $userId);

try {
    $g = new GoogleAuthenticator();
    $secret = $g->generateSecret();

    // Store temp secret in session until user verifies
    $_SESSION['google2fa_temp_secret'] = $secret;

    $issuer = 'HIGH-Q';
    $qrUrl = GoogleQrUrl::generate($email, $secret, $issuer);

    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'qr_code_url' => $qrUrl
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate QR code',
        'error' => $e->getMessage()
    ]);
}

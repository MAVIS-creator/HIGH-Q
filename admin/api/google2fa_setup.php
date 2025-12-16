<?php
// admin/api/google2fa_setup.php
// Generate Google Authenticator secret and QR code
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
    // Simple Google Authenticator secret generator (Base32)
    function generateSecret($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    // Check if user already has a secret
    $stmt = $pdo->prepare("SELECT google2fa_secret FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    $secret = $user['google2fa_secret'] ?? null;

    // Generate new secret if doesn't exist
    if (empty($secret)) {
        $secret = generateSecret();
        
        // Update user with new secret
        $updateStmt = $pdo->prepare("UPDATE users SET google2fa_secret = ? WHERE id = ?");
        $updateStmt->execute([$secret, $userId]);
    }

    // Get user email for QR code label
    $userStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch();

    $email = $userData['email'] ?? 'user@highq.com';
    $name = $userData['name'] ?? 'HIGH-Q User';

    // Generate Google Authenticator URI
    $issuer = 'HIGH-Q Academy';
    $label = "$issuer ($email)";
    $uri = "otpauth://totp/" . rawurlencode($label) . "?secret=$secret&issuer=" . rawurlencode($issuer);

    // Generate QR code URL using Google Charts API (or you can use a PHP QR library)
    $qrCodeUrl = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($uri);

    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'qr_code_url' => $qrCodeUrl,
        'manual_entry' => $secret,
        'uri' => $uri
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

<?php
// admin/api/google2fa_verify.php
// Verify Google Authenticator code and enable 2FA
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
    $code = $data['code'] ?? '';

    if (empty($code) || !is_numeric($code) || strlen($code) !== 6) {
        throw new Exception('Invalid verification code format');
    }

    // Get user's secret
    $stmt = $pdo->prepare("SELECT google2fa_secret FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (empty($user['google2fa_secret'])) {
        throw new Exception('2FA not set up. Please generate a secret first.');
    }

    $secret = $user['google2fa_secret'];

    // Verify the code
    if (verifyGoogleAuthCode($secret, $code)) {
        // Enable 2FA for user
        $updateStmt = $pdo->prepare("UPDATE users SET google2fa_enabled = 1 WHERE id = ?");
        $updateStmt->execute([$userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Google Authenticator enabled successfully!'
        ]);
    } else {
        throw new Exception('Invalid verification code. Please try again.');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Verify Google Authenticator TOTP code
 */
function verifyGoogleAuthCode($secret, $code, $discrepancy = 1) {
    $timestamp = floor(time() / 30);
    
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        if (getGoogleAuthCode($secret, $timestamp + $i) === $code) {
            return true;
        }
    }
    
    return false;
}

/**
 * Generate Google Authenticator TOTP code
 */
function getGoogleAuthCode($secret, $timestamp = null) {
    if ($timestamp === null) {
        $timestamp = floor(time() / 30);
    }
    
    $secret = base32_decode($secret);
    $time = pack('N*', 0) . pack('N*', $timestamp);
    $hash = hash_hmac('sha1', $time, $secret, true);
    $offset = ord($hash[19]) & 0xf;
    
    $code = (
        ((ord($hash[$offset + 0]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

/**
 * Decode Base32 string
 */
function base32_decode($secret) {
    if (empty($secret)) {
        return '';
    }
    
    $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32charsFlipped = array_flip(str_split($base32chars));
    
    $paddingCharCount = substr_count($secret, '=');
    $allowedValues = [6, 4, 3, 1, 0];
    
    if (!in_array($paddingCharCount, $allowedValues)) {
        return false;
    }
    
    for ($i = 0; $i < 4; $i++) {
        if ($paddingCharCount == $allowedValues[$i] &&
            substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
            return false;
        }
    }
    
    $secret = str_replace('=', '', $secret);
    $secret = str_split($secret);
    $binaryString = '';
    
    for ($i = 0; $i < count($secret); $i = $i + 8) {
        $x = '';
        if (!in_array($secret[$i], $base32charsFlipped)) {
            return false;
        }
        
        for ($j = 0; $j < 8; $j++) {
            $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
        }
        
        $eightBits = str_split($x, 8);
        for ($z = 0; $z < count($eightBits); $z++) {
            $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
        }
    }
    
    return $binaryString;
}

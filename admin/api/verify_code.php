<?php
// admin/api/verify_code.php
// Verify the code sent to email/phone
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$code = trim($body['code'] ?? '');

if ($code === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Code is required']);
    exit;
}

// Check if verification code exists in session
if (empty($_SESSION['verification_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No verification code found. Start verification first.']);
    exit;
}

$verif = $_SESSION['verification_code'];

// Check expiry
if (time() > $verif['expires']) {
    unset($_SESSION['verification_code']);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Code has expired. Request a new one.']);
    exit;
}

// Verify code
if ($code !== $verif['code']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
    exit;
}

// Code is valid - store verified value in session for next step
$_SESSION['verified_' . $verif['type']] = $verif['value'];
if (!empty($verif['purpose']) && $verif['purpose'] === 'account') {
    $_SESSION['account_verified_until'] = time() + 600;
    $_SESSION['account_verified_email'] = $verif['value'];
}
unset($_SESSION['verification_code']);

echo json_encode([
    'success' => true,
    'message' => 'Code verified successfully',
    'type' => $verif['type'],
    'value' => $verif['value'],
    'purpose' => $verif['purpose'] ?? null
]);

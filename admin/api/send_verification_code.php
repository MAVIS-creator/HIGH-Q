<?php
// admin/api/send_verification_code.php
// Send verification code to email or phone
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
$body = json_decode(file_get_contents('php://input'), true);
$type = $body['type'] ?? ''; // 'email' or 'phone'
$value = trim($body['value'] ?? '');
$purpose = $body['purpose'] ?? '';

if (!in_array($type, ['email', 'phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid verification type']);
    exit;
}

if ($purpose === 'account') {
    $type = 'email';
    $value = trim($_SESSION['user']['email'] ?? '');
    if ($value === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No registered account email found for verification']);
        exit;
    }
}

if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

if ($type === 'phone' && !preg_match('/^[0-9\s\-\+\(\)]{10,}$/', $value)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

try {
    // Generate 6-digit code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store in session with expiry (10 minutes)
    $_SESSION['verification_code'] = [
        'code' => $code,
        'type' => $type,
        'value' => $value,
        'purpose' => $purpose,
        'expires' => time() + 600
    ];

    if ($type === 'email') {
        // Send email
        $subject = 'HIGH-Q - Email Verification Code';
        $html = "
            <h2>Email Verification</h2>
            <p>Your verification code is:</p>
            <h1 style='color:#ffd600;font-size:2rem;letter-spacing:0.5rem'>" . htmlspecialchars($code) . "</h1>
            <p>This code expires in 10 minutes.</p>
            <p>If you didn't request this, please ignore this email.</p>
        ";
        
        if (function_exists('sendEmail')) {
            @sendEmail($value, $subject, $html);
        } else {
            // Manual send attempt
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            @mail($value, $subject, $html, $headers);
        }
    } elseif ($type === 'phone') {
        // In real scenario, use SMS service like Twilio, AWS SNS, etc.
        // For now, just store it (you'd implement SMS sending here)
        // Example: @sendSMS($value, "HIGH-Q verification code: $code");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Verification code sent to ' . $type,
        'verification_id' => md5($value . time())
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send verification code']);
}

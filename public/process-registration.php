<?php
// Handle universal registration wizard submissions and redirect to payment
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
$programType = $_POST['program_type'] ?? '';
$csrf = $_POST['_csrf_token'] ?? '';

// CSRF
if (!verifyToken('registration_wizard', $csrf)) {
    $errors[] = 'Invalid session token. Please refresh and try again.';
}

$validTypes = ['jamb','waec','postutme','digital','international'];
if (!in_array($programType, $validTypes, true)) {
    $errors[] = 'Invalid program type.';
}

// Site registration toggle (reuse settings logic)
$siteSettings = [];
try {
    $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
    $siteSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { $siteSettings = []; }
$registrationEnabled = true;
if (!empty($siteSettings)) {
    if (isset($siteSettings['registration'])) $registrationEnabled = (bool)$siteSettings['registration'];
    elseif (isset($siteSettings['security']['registration'])) $registrationEnabled = (bool)$siteSettings['security']['registration'];
}
if (!$registrationEnabled) {
    $errors[] = 'Registrations are temporarily closed by the administrator.';
}

// Collect core fields
$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? ($_POST['surname'] ?? ''));
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['home_address'] ?? ($_POST['address'] ?? ''));

if ($first === '') $errors[] = 'First name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if ($phone === '') $errors[] = 'Phone number is required.';

// Amount map (fallback if no course price pulled)
$basePrices = [
    'jamb' => 10000,
    'waec' => 8000,
    'postutme' => 10000,
    'digital' => 0,
    'international' => 15000,
];
$formFee = 1000;
$cardFee = 1500;

// Attempt to use courses table price when available
try {
    $slugMap = [
        'jamb' => 'jamb-post-utme',
        'waec' => 'professional-services',
        'postutme' => 'jamb-post-utme',
        'digital' => 'digital-skills',
        'international' => null,
    ];
    $slug = $slugMap[$programType] ?? null;
    if ($slug) {
        $c = $pdo->prepare('SELECT price FROM courses WHERE slug = ? LIMIT 1');
        $c->execute([$slug]);
        $p = $c->fetch(PDO::FETCH_ASSOC);
        if ($p && $p['price'] !== null && $p['price'] !== '') {
            $basePrices[$programType] = (float)$p['price'];
        }
    }
} catch (Throwable $e) { /* ignore price lookup */ }

$amount = $basePrices[$programType] + $formFee + $cardFee;

if (!empty($errors)) {
    $_SESSION['registration_errors'] = $errors;
    header('Location: register-new.php?step=2&type=' . urlencode($programType));
    exit;
}

// Build payload (store all POST for auditing)
$payload = $_POST;
unset($payload['_csrf_token']);

$reference = 'REG-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)),0,6);

try {
    $stmt = $pdo->prepare('INSERT INTO universal_registrations (program_type, first_name, last_name, email, phone, status, payment_reference, payment_status, amount, payment_method, payload, created_at) VALUES (?, ?, ?, ?, ?, "pending", ?, "pending", ?, "online", ?, NOW())');
    $stmt->execute([
        $programType,
        $first,
        $last,
        $email,
        $phone,
        $reference,
        $amount,
        json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $regId = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    $_SESSION['registration_errors'] = ['Unable to save your registration. Please try again.'];
    header('Location: register-new.php?step=2&type=' . urlencode($programType));
    exit;
}

// Create payment placeholder
$metadata = [
    'program_type' => $programType,
    'registration_id' => $regId,
    'email' => $email,
    'phone' => $phone,
    'name' => trim($first . ' ' . $last),
];
try {
    $pstmt = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata, registration_type) VALUES (NULL, ?, ?, ?, "pending", NOW(), ?, ?)');
    $pstmt->execute([
        $amount,
        'online',
        $reference,
        json_encode($metadata, JSON_UNESCAPED_UNICODE),
        $programType,
    ]);
    $_SESSION['last_payment_reference'] = $reference;
    $_SESSION['last_payment_id'] = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    // continue without payment row; payments_wait will handle missing
}

header('Location: payments_wait.php?ref=' . urlencode($reference));
exit;

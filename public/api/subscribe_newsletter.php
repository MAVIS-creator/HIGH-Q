<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status'=>'error','message'=>'Invalid email']);
    exit;
}
try {
    // generate an unsubscribe token
    $token = bin2hex(random_bytes(24));
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, created_at, unsubscribe_token, token_created_at) VALUES (?, NOW(), ?, NOW())');
    $stmt->execute([$email, $token]);
    echo json_encode(['status'=>'ok','message'=>'Subscribed']);
} catch (Throwable $e) {
    // duplicate entry or other DB error
    echo json_encode(['status'=>'error','message'=>'Subscription failed: ' . $e->getMessage()]);
}

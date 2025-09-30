<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status'=>'error','message'=>'Invalid email']);
    exit;
}
try {
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())');
    $stmt->execute([$email]);
    echo json_encode(['status'=>'ok','message'=>'Subscribed']);
} catch (Throwable $e) {
    // duplicate entry or other DB error
    echo json_encode(['status'=>'error','message'=>'Subscription failed: ' . $e->getMessage()]);
}

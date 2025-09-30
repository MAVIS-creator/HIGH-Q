<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status'=>'error','message'=>'Invalid email']);
    exit;
}
try {
    // create unsubscribe token for later
    $token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, unsubscribe_token, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE unsubscribe_token=VALUES(unsubscribe_token)');
    $stmt->execute([$email, $token]);
    // mark as pending send in a simple queue table
    try {
      $q = $pdo->prepare('INSERT INTO newsletter_queue (email, subject, payload, status, created_at) VALUES (?, ?, ?, "pending", NOW())');
      $q->execute([$email, 'Welcome', '']);
    } catch (Throwable $qe) { /* ignore if queue table missing */ }
    echo json_encode(['status'=>'ok','message'=>'Subscribed','unsubscribe_token'=>$token]);
} catch (Throwable $e) {
    // duplicate entry or other DB error
    echo json_encode(['status'=>'error','message'=>'Subscription failed: ' . $e->getMessage()]);
}

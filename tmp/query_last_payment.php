<?php
require_once __DIR__ . '/../public/config/db.php';
header('Content-Type: application/json');
try {
    $stmt = $pdo->query("SELECT id, amount, payment_method, reference, status, metadata, form_fee_paid, tutor_fee_paid, registration_type, created_at FROM payments WHERE registration_type = 'postutme' ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['found' => false]);
    } else {
        echo json_encode(['found' => true, 'row' => $row], JSON_PRETTY_PRINT);
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

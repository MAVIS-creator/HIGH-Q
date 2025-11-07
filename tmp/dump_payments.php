<?php
require_once __DIR__ . '/../public/config/db.php';
header('Content-Type: application/json');
try {
    $stmt = $pdo->query("SELECT id, reference, registration_type, amount, status, created_at, metadata FROM payments ORDER BY id DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

<?php
require_once __DIR__ . '/../public/config/db.php';
header('Content-Type: application/json');
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, email, passport_path, created_at FROM student_registrations ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) echo json_encode(['found'=>false]); else echo json_encode(['found'=>true,'row'=>$row], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode(['error'=>$e->getMessage()]);
}

<?php
require_once __DIR__ . '/../public/config/db.php';
header('Content-Type: application/json');
try {
    $st = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
    $st->execute(['system_settings']);
    $val = $st->fetchColumn();
    $j = $val ? json_decode($val, true) : null;
    echo json_encode(['raw' => $val, 'decoded' => $j], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

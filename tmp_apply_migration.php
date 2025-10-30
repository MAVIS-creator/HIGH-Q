<?php
require_once __DIR__ . '/public/config/db.php';
try {
    $sql = "ALTER TABLE payments ADD COLUMN activated_at datetime DEFAULT NULL AFTER created_at";
    $pdo->exec($sql);
    echo "ok\n";
} catch (Throwable $e) {
    echo 'error: ' . $e->getMessage() . "\n";
}

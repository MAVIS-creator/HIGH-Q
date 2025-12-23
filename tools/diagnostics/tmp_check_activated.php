<?php
require_once __DIR__ . '/public/config/db.php';
try {
    $q = $pdo->prepare("SHOW COLUMNS FROM payments LIKE 'activated_at'");
    $q->execute();
    $c = $q->fetchAll();
    echo count($c) ? 'activated_at_exists' : 'no_activated_at';
} catch (Throwable $e) {
    echo 'error: ' . $e->getMessage();
}

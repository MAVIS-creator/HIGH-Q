<?php
require_once __DIR__ . '/public/config/db.php';
try {
    $dbn = $pdo->query('select database()')->fetchColumn();
    echo "DB: " . ($dbn ?: '(none)') . "\n";
    $q = $pdo->query("SHOW CREATE TABLE payments");
    $row = $q->fetch();
    if ($row) {
        echo "SHOW CREATE TABLE payments:\n" . $row['Create Table'];
    } else {
        echo "payments table not found\n";
    }
} catch (Throwable $e) {
    echo 'error: ' . $e->getMessage();
}

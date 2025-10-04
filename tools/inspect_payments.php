<?php
// tools/inspect_payments.php - temporary debug helper (remove after use)
require_once __DIR__ . '/../admin/includes/db.php';
header('Content-Type: text/plain');
try {
    $stmt = $pdo->query("DESCRIBE payments");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo $r['Field'] . "\t" . $r['Type'] . "\t" . $r['Null'] . "\t" . $r['Key'] . "\t" . $r['Default'] . "\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}

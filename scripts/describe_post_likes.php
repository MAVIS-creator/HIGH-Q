<?php
require __DIR__ . '/../public/config/db.php';
try {
    $q = $pdo->query('DESCRIBE post_likes');
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo $r['Field'] . "\t" . $r['Type'] . "\t" . ($r['Key'] ?: '') . "\n";
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}

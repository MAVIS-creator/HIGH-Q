<?php
require __DIR__ . '/../public/config/db.php';
try {
    $st = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
    $st->execute(['system_settings']);
    $val = $st->fetchColumn();
    echo "system_settings:\n";
    var_export($val);
    echo "\n\n";
    if ($val) {
        $j = json_decode($val, true);
        echo "Decoded:\n";
        print_r($j);
    }
} catch (Throwable $e) {
    echo 'error: ' . $e->getMessage() . PHP_EOL;
}

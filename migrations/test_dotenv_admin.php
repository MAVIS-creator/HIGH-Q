<?php
require __DIR__ . '/../vendor/autoload.php';
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../admin');
    $dotenv->load();
    echo "ok\n";
} catch (Throwable $e) {
    echo "error: " . $e->getMessage() . "\n";
}

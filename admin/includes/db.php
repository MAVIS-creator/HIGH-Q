<?php
// admin/includes/db.php

require __DIR__ . '/../../vendor/autoload.php'; // adjust path if needed

use Dotenv\Dotenv;

// Prefer project root .env (two levels up), fallback to admin/.env
$rootDir = dirname(__DIR__, 2);
$adminDir = dirname(__DIR__);
try {
    if (file_exists($rootDir . '/.env')) {
        Dotenv::createImmutable($rootDir)->safeLoad();
    } elseif (file_exists($adminDir . '/.env')) {
        Dotenv::createImmutable($adminDir)->safeLoad();
    }
} catch (Throwable $e) {
    // Ignore parse errors here; we'll fall back to getenv() below
}

// Fetch env vars (fall back to getenv and sensible defaults)
$host    = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$db      = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'highq';
$user    = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$pass    = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
$charset = $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    $hint = "Please ensure your admin database credentials are set in admin/.env or the project .env file.";
    die("Database connection failed: " . $e->getMessage() . "\n" . $hint);
}

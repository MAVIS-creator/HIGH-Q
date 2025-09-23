<?php

require __DIR__ . '/../../vendor/autoload.php'; // adjust path if needed

use Dotenv\Dotenv;

// Attempt to load .env from project root (two levels up from public/config)
$rootDir = dirname(__DIR__, 2); // HIGH-Q root
$publicDir = dirname(__DIR__); // public

// Prefer root .env, fallback to public/.env; if none present, skip loading to avoid fatal errors
try {
    if (file_exists($rootDir . '/.env')) {
        Dotenv::createImmutable($rootDir)->safeLoad();
    } elseif (file_exists($publicDir . '/.env')) {
        Dotenv::createImmutable($publicDir)->safeLoad();
    } else {
        // no .env file found; continue — environment variables may be provided by server
    }
} catch (Throwable $e) {
    // If Dotenv throws unexpectedly, suppress to avoid fatal error in environments without .env
}

// Fetch env vars
$host    = $_ENV['DB_HOST'];
$db      = $_ENV['DB_NAME'];
$user    = $_ENV['DB_USER'];
$pass    = $_ENV['DB_PASS'];
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$host = $_ENV['DB_HOST'];
db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "DB connect failed: " . $e->getMessage();
    exit(1);
}
$stmt = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'site_settings' ORDER BY ORDINAL_POSITION");
$stmt->execute([$db]);
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);

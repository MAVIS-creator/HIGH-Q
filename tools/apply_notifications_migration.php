<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_NAME'];
$dbUser = $_ENV['DB_USER'];
$dbPass = $_ENV['DB_PASS'];
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Check users table exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users'");
$stmt->execute([$dbName]);
$usersExists = (bool)$stmt->fetchColumn();
if (!$usersExists) {
    echo "ERROR: 'users' table does not exist in database '{$dbName}'. Cannot create notifications table with FK.\n";
    exit(1);
}

// Check notifications table
$stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'notifications'");
$stmt->execute([$dbName]);
$exists = (bool)$stmt->fetchColumn();
if ($exists) {
    echo "No action: 'notifications' table already exists.\n";
    // Print columns
    $stmt = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'notifications' ORDER BY ORDINAL_POSITION");
    $stmt->execute([$dbName]);
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

// Read migration SQL
$migrationPath = __DIR__ . '/../migrations/2025-09-26-create-notifications-table.sql';
if (!is_readable($migrationPath)) {
    echo "Migration SQL not found at {$migrationPath}\n";
    exit(1);
}
$sql = file_get_contents($migrationPath);
if (!$sql) {
    echo "Failed to read migration SQL file.\n";
    exit(1);
}

try {
    $pdo->beginTransaction();
    $pdo->exec($sql);
    $pdo->commit();
    echo "Migration applied: 'notifications' table created.\n";
    $stmt = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'notifications' ORDER BY ORDINAL_POSITION");
    $stmt->execute([$dbName]);
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

<?php
require __DIR__ . '/../public/config/db.php';

$dir = dirname(__DIR__) . '/migrations';
$files = array_map('basename', glob($dir . '/*.sql'));
sort($files);

// Ensure migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(512) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$applied = [];
$stmt = $pdo->query('SELECT filename FROM migrations');
foreach ($stmt as $row) {
    $applied[$row['filename']] = true;
}

$pending = array_values(array_diff($files, array_keys($applied)));

echo "Total files: " . count($files) . "\n";
echo "Applied: " . count($applied) . "\n";
echo "Pending: " . count($pending) . "\n";
foreach ($pending as $p) {
    echo " - $p\n";
}

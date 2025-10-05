<?php
// tools/run_migrations.php
// Simple migration runner: applies any .sql files in migrations/ in alphabetical order
// and records applied filenames in a migrations table.

$root = dirname(__DIR__);
require $root . '/public/config/db.php'; // loads $pdo

$dir = $root . DIRECTORY_SEPARATOR . 'migrations';
if (!is_dir($dir)) {
    echo "No migrations directory found at: $dir\n";
    exit(1);
}

// Ensure migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(512) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$applied = [];
$stmt = $pdo->query("SELECT filename FROM migrations");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $applied[$r['filename']] = true;
}

$files = glob($dir . DIRECTORY_SEPARATOR . '*.sql');
if (!$files) {
    echo "No SQL migration files found in $dir\n";
    exit(0);
}

sort($files);
foreach ($files as $f) {
    $base = basename($f);
    if (isset($applied[$base])) {
        echo "Skipping already applied: $base\n";
        continue;
    }
    echo "Applying: $base\n";
    $sql = file_get_contents($f);
    if ($sql === false) {
        echo "Failed to read $f\n";
        continue;
    }
    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $ins->execute([$base]);
        $pdo->commit();
        echo "Applied: $base\n";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Failed to apply $base: " . $e->getMessage() . "\n"
            . "Transaction rolled back. Check SQL and re-run.\n";
        exit(1);
    }
}

echo "All migrations complete.\n";

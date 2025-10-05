<?php
// tools/run_specific_migrations.php
// Run only migrations matching a pattern (useful to avoid older broken migrations)
$root = dirname(__DIR__);
require $root . '/public/config/db.php'; // loads $pdo

$dir = $root . DIRECTORY_SEPARATOR . 'migrations';
$pattern = $argv[1] ?? '2025-10-05*';
$files = glob($dir . DIRECTORY_SEPARATOR . $pattern);
if (!$files) {
    echo "No matching migration files for pattern: $pattern\n";
    exit(0);
}

foreach ($files as $f) {
    $base = basename($f);
    echo "Applying specific migration: $base\n";
    $sql = file_get_contents($f);
    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        // record in migrations table (create if missing)
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(512) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $ins->execute([$base]);
        $pdo->commit();
        echo "Applied: $base\n";
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "Failed to apply $base: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";

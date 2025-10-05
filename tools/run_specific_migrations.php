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
        // execute the SQL directly. Some migration files include their own transaction control
        $pdo->exec($sql);

        // record in migrations table (create if missing)
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(512) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $ins->execute([$base]);

        echo "Applied: $base\n";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // treat common 'already exists' errors as non-fatal (idempotent)
        if (preg_match('/already exists|duplicate|1060/i', $msg)) {
            echo "Non-fatal schema error applying $base: $msg\n";
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(512) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
                $ins->execute([$base]);
                echo "Marked $base as applied (non-fatal).\n";
            } catch (PDOException $e2) {
                echo "Also failed to record migration $base: " . $e2->getMessage() . "\n";
            }
            continue;
        }

        echo "Failed to apply $base: $msg\n";
    }
}

echo "Done.\n";

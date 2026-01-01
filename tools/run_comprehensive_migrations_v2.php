<?php
/**
 * Comprehensive Migration Runner - Fixed Version
 * 
 * This script:
 * 1. Runs all .sql migrations from migrations/ folder
 * 2. Runs all .php migrations from migrations/ folder
 * 3. Tracks all migrations in the database
 * 4. Verifies all migrations ran successfully
 */

// First, let's load DB from root
$rootDir = __DIR__ . '/../';
require_once $rootDir . 'admin/includes/db.php';

// Force UTF-8 output
header('Content-Type: text/plain; charset=utf-8');
echo "=== HIGH-Q COMPREHENSIVE MIGRATION RUNNER ===\n\n";

try {
    // Step 1: Create migrations tracking table
    echo "[1/5] Setting up migrations tracking table...\n";
    
    // Check if status column exists, if not add it
    try {
        $pdo->query("SELECT status FROM migrations LIMIT 1");
    } catch (Exception $e) {
        // Column doesn't exist, add it
        try {
            $pdo->exec("ALTER TABLE migrations ADD COLUMN status ENUM('pending', 'success', 'failed') DEFAULT 'success' AFTER applied_at");
        } catch (Exception $e) {}
        try {
            $pdo->exec("ALTER TABLE migrations ADD COLUMN error_message LONGTEXT AFTER status");
        } catch (Exception $e) {}
        try {
            $pdo->exec("ALTER TABLE migrations ADD COLUMN execution_time FLOAT DEFAULT 0 AFTER error_message");
        } catch (Exception $e) {}
    }
    
    echo "✓ Migrations table ready\n\n";

    // Step 2: Get list of all migration files
    echo "[2/5] Scanning migrations folder...\n";
    $migrationsDir = $rootDir . '/migrations';
    
    if (!is_dir($migrationsDir)) {
        throw new Exception("Migrations directory not found: $migrationsDir");
    }

    $files = scandir($migrationsDir);
    $sqlMigrations = [];
    $phpMigrations = [];

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (substr($file, 0, 1) === '_') continue; // Skip files starting with _
        if ($file === 'run_migration.bat') continue;
        
        if (preg_match('/\.sql$/', $file)) {
            $sqlMigrations[] = $file;
        } elseif (preg_match('/\.php$/', $file)) {
            $phpMigrations[] = $file;
        }
    }

    sort($sqlMigrations);
    sort($phpMigrations);

    echo "Found " . count($sqlMigrations) . " SQL migrations and " . count($phpMigrations) . " PHP migrations\n\n";

    // Step 3: Run SQL migrations
    echo "[3/5] Running SQL migrations...\n";
    $sqlSuccess = 0;
    $sqlFailed = 0;

    foreach ($sqlMigrations as $filename) {
        $filepath = $migrationsDir . '/' . $filename;
        $start = microtime(true);
        
        // Create fresh PDO connection to avoid buffered query issues
        try {
            $tmpPdo = new PDO(
                "mysql:host=" . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ";dbname=" . ($_ENV['DB_NAME'] ?? 'highq') . ";charset=utf8mb4",
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            // Check if already applied
            $check = $tmpPdo->prepare("SELECT id FROM migrations WHERE filename = ?");
            $check->execute([$filename]);
            
            if ($check->fetch()) {
                echo "  ⊘ $filename (already applied)\n";
                unset($tmpPdo);
                continue;
            }

            $sql = file_get_contents($filepath);
            
            // Split by semicolon and clean
            $statements = array_filter(
                array_map('trim', preg_split('/;[\r\n]+/', $sql)),
                fn($s) => !empty($s) && !preg_match('/^--/', $s) && $s !== 'DELIMITER ;;' && $s !== 'DELIMITER ;'
            );

            $stmtCount = 0;
            foreach ($statements as $stmt) {
                if (!empty(trim($stmt))) {
                    $tmpPdo->exec($stmt);
                    $stmtCount++;
                }
            }

            $duration = microtime(true) - $start;
            
            // Mark as success
            try {
                $upd = $tmpPdo->prepare("UPDATE migrations SET status = 'success', execution_time = ? WHERE filename = ?");
                $upd->execute([$duration, $filename]);
                
                if ($upd->rowCount() === 0) {
                    $ins = $tmpPdo->prepare("INSERT IGNORE INTO migrations (filename, status, execution_time) VALUES (?, 'success', ?)");
                    $ins->execute([$filename, $duration]);
                }
            } catch (Exception $e) {}
            
            echo "  ✓ $filename ($stmtCount statements, " . number_format($duration, 3) . "s)\n";
            $sqlSuccess++;
            $tmpPdo = null;
            
        } catch (Exception $e) {
            $duration = microtime(true) - $start;
            
            // Mark as failed
            try {
                $upd = $tmpPdo->prepare("UPDATE migrations SET status = 'failed', error_message = ?, execution_time = ? WHERE filename = ?");
                $upd->execute([$e->getMessage(), $duration, $filename]);
                
                if ($upd->rowCount() === 0) {
                    $ins = $tmpPdo->prepare("INSERT IGNORE INTO migrations (filename, status, error_message, execution_time) VALUES (?, 'failed', ?, ?)");
                    $ins->execute([$filename, $e->getMessage(), $duration]);
                }
            } catch (Exception $err) {}
            
            $msg = substr($e->getMessage(), 0, 80);
            echo "  ✗ $filename - ERROR: $msg\n";
            $sqlFailed++;
            $tmpPdo = null;
        }
    }
    echo "\nSQL: $sqlSuccess succeeded, $sqlFailed failed\n\n";

    // Step 4: Run PHP migrations
    echo "[4/5] Running PHP migrations...\n";
    $phpSuccess = 0;
    $phpFailed = 0;

    foreach ($phpMigrations as $filename) {
        $filepath = $migrationsDir . '/' . $filename;
        $start = microtime(true);
        
        try {
            // Check if already applied
            $tmpPdo = new PDO(
                "mysql:host=" . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ";dbname=" . ($_ENV['DB_NAME'] ?? 'highq') . ";charset=utf8mb4",
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            $check = $tmpPdo->prepare("SELECT id FROM migrations WHERE filename = ?");
            $check->execute([$filename]);
            
            if ($check->fetch()) {
                echo "  ⊘ $filename (already applied)\n";
                $tmpPdo = null;
                continue;
            }
            $tmpPdo = null;

            // Capture output
            ob_start();
            
            // Include and run the migration (sets $pdo globally via db.php)
            include $filepath;
            
            $output = ob_get_clean();
            $duration = microtime(true) - $start;
            
            // Mark as success
            $tmpPdo = new PDO(
                "mysql:host=" . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ";dbname=" . ($_ENV['DB_NAME'] ?? 'highq') . ";charset=utf8mb4",
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            try {
                $upd = $tmpPdo->prepare("UPDATE migrations SET status = 'success', execution_time = ? WHERE filename = ?");
                $upd->execute([$duration, $filename]);
                
                if ($upd->rowCount() === 0) {
                    $ins = $tmpPdo->prepare("INSERT IGNORE INTO migrations (filename, status, execution_time) VALUES (?, 'success', ?)");
                    $ins->execute([$filename, $duration]);
                }
            } catch (Exception $e) {}
            
            echo "  ✓ $filename (" . number_format($duration, 3) . "s)\n";
            if (!empty($output)) {
                foreach (explode("\n", trim($output)) as $line) {
                    if (!empty($line)) {
                        echo "    > " . substr($line, 0, 70) . "\n";
                    }
                }
            }
            $phpSuccess++;
            $tmpPdo = null;
            
        } catch (Exception $e) {
            $duration = microtime(true) - $start;
            $output = ob_get_clean();
            
            // Mark as failed
            try {
                $tmpPdo = new PDO(
                    "mysql:host=" . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ";dbname=" . ($_ENV['DB_NAME'] ?? 'highq') . ";charset=utf8mb4",
                    $_ENV['DB_USER'] ?? 'root',
                    $_ENV['DB_PASS'] ?? '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                
                $upd = $tmpPdo->prepare("UPDATE migrations SET status = 'failed', error_message = ?, execution_time = ? WHERE filename = ?");
                $upd->execute([$e->getMessage(), $duration, $filename]);
                
                if ($upd->rowCount() === 0) {
                    $ins = $tmpPdo->prepare("INSERT IGNORE INTO migrations (filename, status, error_message, execution_time) VALUES (?, 'failed', ?, ?)");
                    $ins->execute([$filename, $e->getMessage(), $duration]);
                }
            } catch (Exception $err) {}
            
            $msg = substr($e->getMessage(), 0, 70);
            echo "  ✗ $filename - ERROR: $msg\n";
            $phpFailed++;
            $tmpPdo = null;
        }
    }
    echo "\nPHP: $phpSuccess succeeded, $phpFailed failed\n\n";

    // Step 5: Verify and report
    echo "[5/5] Verifying migrations in database...\n";
    
    $stmt = $pdo->query("SELECT * FROM migrations ORDER BY applied_at ASC");
    $migrations = $stmt->fetchAll();
    
    $successCount = 0;
    $failureCount = 0;
    $totalCount = count($migrations);

    echo "\nMigration Summary:\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($migrations as $m) {
        $status = isset($m['status']) ? strtoupper($m['status']) : 'UNKNOWN';
        $icon = $status === 'SUCCESS' ? '✓' : ($status === 'FAILED' ? '✗' : '⊘');
        $duration = isset($m['execution_time']) && $m['execution_time'] ? number_format($m['execution_time'], 3) . "s" : "—";
        
        printf("%s %-50s %-12s %-10s\n", 
            $icon, 
            substr($m['filename'], 0, 50), 
            $status, 
            $duration
        );
        
        if ($status === 'SUCCESS') {
            $successCount++;
        } elseif ($status === 'FAILED') {
            $failureCount++;
        }
    }
    echo str_repeat("-", 80) . "\n";
    echo "\n📊 Summary:\n";
    echo "  Total migrations: $totalCount\n";
    echo "  ✓ Successful: $successCount\n";
    echo "  ✗ Failed: $failureCount\n";
    echo "  ⊘ Pending: " . ($totalCount - $successCount - $failureCount) . "\n";
    
    if ($failureCount === 0) {
        echo "\n✅ All migrations completed!\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>

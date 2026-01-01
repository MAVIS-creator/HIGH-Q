<?php
/**
 * Comprehensive Migration Runner
 * 
 * This script:
 * 1. Creates the migrations tracking table (if missing)
 * 2. Runs all .sql migrations from migrations/ folder
 * 3. Runs all .php migrations from migrations/ folder
 * 4. Tracks all migrations in the database
 * 5. Verifies all migrations ran successfully
 */

require_once __DIR__ . '/../admin/includes/db.php';

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
        $pdo->exec("ALTER TABLE migrations ADD COLUMN status ENUM('pending', 'success', 'failed') DEFAULT 'success' AFTER applied_at");
        $pdo->exec("ALTER TABLE migrations ADD COLUMN error_message LONGTEXT AFTER status");
        $pdo->exec("ALTER TABLE migrations ADD COLUMN execution_time FLOAT DEFAULT 0 AFTER error_message");
    }
    
    echo "✓ Migrations table ready\n\n";

    // Step 2: Get list of all migration files
    echo "[2/5] Scanning migrations folder...\n";
    $migrationsDir = __DIR__ . '/../migrations';
    
    if (!is_dir($migrationsDir)) {
        throw new Exception("Migrations directory not found: $migrationsDir");
    }

    $files = scandir($migrationsDir);
    $sqlMigrations = [];
    $phpMigrations = [];

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (substr($file, 0, 1) === '_') continue; // Skip files starting with _
        
        if (preg_match('/\.sql$/', $file)) {
            $sqlMigrations[] = $file;
        } elseif (preg_match('/\.php$/', $file) && $file !== 'run_migration.bat') {
            $phpMigrations[] = $file;
        }
    }

    sort($sqlMigrations);
    sort($phpMigrations);

    echo "Found " . count($sqlMigrations) . " SQL migrations\n";
    echo "Found " . count($phpMigrations) . " PHP migrations\n";
    foreach ($sqlMigrations as $f) echo "  - $f\n";
    foreach ($phpMigrations as $f) echo "  - $f\n";
    echo "\n";

    // Step 3: Run SQL migrations
    echo "[3/5] Running SQL migrations...\n";
    $sqlSuccess = 0;
    $sqlFailed = 0;

    foreach ($sqlMigrations as $filename) {
        $filepath = $migrationsDir . '/' . $filename;
        $start = microtime(true);
        
        try {
            // Check if already applied
            $check = $pdo->prepare("SELECT id FROM migrations WHERE filename = ?");
            $check->execute([$filename]);
            
            if ($check->fetch()) {
                echo "  ⊘ $filename (already applied)\n";
                continue;
            }

            $sql = file_get_contents($filepath);
            
            // Split by semicolon but preserve statement integrity
            $statements = array_filter(
                array_map('trim', preg_split('/;[\r\n]+/', $sql)),
                fn($s) => !empty($s) && !preg_match('/^--/', $s)
            );

            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    $pdo->exec($stmt);
                }
            }

            $duration = microtime(true) - $start;
            
            // Mark as success in tracking table - use INSERT IGNORE for existing rows
            try {
                $upd = $pdo->prepare("UPDATE migrations SET status = 'success', execution_time = ? WHERE filename = ?");
                $upd->execute([$duration, $filename]);
                
                if ($upd->rowCount() === 0) {
                    $ins = $pdo->prepare("INSERT IGNORE INTO migrations (filename, status, execution_time) VALUES (?, 'success', ?)");
                    $ins->execute([$filename, $duration]);
                }
            } catch (Exception $e) {
                // Table might not have these columns yet
            }
            
            echo "  ✓ $filename (" . number_format($duration, 3) . "s)\n";
            $sqlSuccess++;
            
        } catch (Exception $e) {
            $duration = microtime(true) - $start;
            
            // Mark as failed in tracking table
            try {
                $upd = $pdo->prepare("UPDATE migrations SET status = 'failed', error_message = ?, execution_time = ? WHERE filename = ?");
                $upd->execute([$e->getMessage(), $duration, $filename]);
                
                if ($upd->rowCount() === 0) {
                    $ins = $pdo->prepare("INSERT IGNORE INTO migrations (filename, status, error_message, execution_time) VALUES (?, 'failed', ?, ?)");
                    $ins->execute([$filename, $e->getMessage(), $duration]);
                }
            } catch (Exception $err) {
                // Table might not have these columns yet
            }
            
            echo "  ✗ $filename - ERROR: " . $e->getMessage() . "\n";
            $sqlFailed++;
        }
    }
    echo "\nSQL Migrations: $sqlSuccess succeeded, $sqlFailed failed\n\n";

    // Step 4: Run PHP migrations
    echo "[4/5] Running PHP migrations...\n";
    $phpSuccess = 0;
    $phpFailed = 0;

    foreach ($phpMigrations as $filename) {
        $filepath = $migrationsDir . '/' . $filename;
        $start = microtime(true);
        
        try {
            // Check if already applied
            $check = $pdo->prepare("SELECT id FROM migrations WHERE filename = ?");
            $check->execute([$filename]);
            
            if ($check->fetch()) {
                echo "  ⊘ $filename (already applied)\n";
                continue;
            }

            // Capture output
            ob_start();
            
            // Include and run the migration
            include $filepath;
            
            $output = ob_get_clean();
            $duration = microtime(true) - $start;
            
            // Mark as success in tracking table
            try {
                $upd = $pdo->prepare("UPDATE migrations SET status = 'success', execution_time = ? WHERE filename = ?");
                $upd->execute([$duration, $filename]);
                
                if ($upd->rowCount() === 0) {
                    $ins = $pdo->prepare("INSERT IGNORE INTO migrations (filename, status, execution_time) VALUES (?, 'success', ?)");
                    $ins->execute([$filename, $duration]);
                }
            } catch (Exception $e) {
                // Table might not have these columns yet
            }
            
            echo "  ✓ $filename (" . number_format($duration, 3) . "s)\n";
            if (!empty($output)) {
                echo "    Output: " . trim(str_replace("\n", "\n    ", $output)) . "\n";
            }
            $phpSuccess++;
            
        } catch (Exception $e) {
            $duration = microtime(true) - $start;
            $output = ob_get_clean();
            
            // Mark as failed in tracking table
            try {
                $upd = $pdo->prepare("UPDATE migrations SET status = 'failed', error_message = ?, execution_time = ? WHERE filename = ?");
                $upd->execute([$e->getMessage(), $duration, $filename]);
                
                if ($upd->rowCount() === 0) {
                    $ins = $pdo->prepare("INSERT IGNORE INTO migrations (filename, status, error_message, execution_time) VALUES (?, 'failed', ?, ?)");
                    $ins->execute([$filename, $e->getMessage(), $duration]);
                }
            } catch (Exception $err) {
                // Table might not have these columns yet
            }
            
            echo "  ✗ $filename - ERROR: " . $e->getMessage() . "\n";
            $phpFailed++;
        }
    }
    echo "\nPHP Migrations: $phpSuccess succeeded, $phpFailed failed\n\n";

    // Step 5: Verify and report
    echo "[5/5] Verifying migrations in database...\n";
    
    $stmt = $pdo->query("SELECT * FROM migrations ORDER BY applied_at ASC");
    $migrations = $stmt->fetchAll();
    
    $successCount = 0;
    $failureCount = 0;
    $totalCount = count($migrations);

    echo "\nMigration Status Report:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-50s %-15s %-10s\n", "Filename", "Status", "Duration");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($migrations as $m) {
        $status = strtoupper($m['status']);
        $icon = $m['status'] === 'success' ? '✓' : ($m['status'] === 'failed' ? '✗' : '⊘');
        $duration = $m['execution_time'] ? number_format($m['execution_time'], 3) . "s" : "N/A";
        
        printf("%s %-48s %-15s %-10s\n", 
            $icon, 
            substr($m['filename'], 0, 48), 
            $status, 
            $duration
        );
        
        if ($m['status'] === 'success') {
            $successCount++;
        } elseif ($m['status'] === 'failed') {
            $failureCount++;
            if ($m['error_message']) {
                echo "  Error: " . substr($m['error_message'], 0, 70) . "\n";
            }
        }
    }
    echo str_repeat("-", 80) . "\n";
    echo "\nSummary:\n";
    echo "  Total migrations: $totalCount\n";
    echo "  ✓ Successful: $successCount\n";
    echo "  ✗ Failed: $failureCount\n";
    
    if ($failureCount === 0) {
        echo "\n🎉 All migrations completed successfully!\n";
    } else {
        echo "\n⚠️  Some migrations failed. Review errors above.\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>

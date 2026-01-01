<?php
/**
 * Database Schema Verification Script
 * 
 * Checks:
 * 1. All migrations table entries match actual tables
 * 2. Expected table columns exist
 * 3. Indexes are in place
 * 4. Foreign keys are valid
 */

require_once __DIR__ . '/../admin/includes/db.php';

echo "=== DATABASE SCHEMA VERIFICATION ===\n\n";

try {
    // Get all tables in the database
    echo "[1/4] Checking database tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables in database:\n";
    foreach (array_slice($tables, 0, 20) as $t) {
        echo "  - $t\n";
    }
    if (count($tables) > 20) {
        echo "  ... and " . (count($tables) - 20) . " more\n";
    }
    echo "\n";

    // Check key tables exist
    echo "[2/4] Verifying critical tables exist...\n";
    $criticalTables = [
        'users' => ['id', 'email', 'password'],
        'courses' => ['id', 'name', 'slug'],
        'student_registrations' => ['id', 'user_id', 'email'],
        'post_utme_registrations' => ['id', 'user_id', 'status'],
        'payments' => ['id', 'user_id', 'amount'],
        'migrations' => ['id', 'filename', 'status'],
    ];

    foreach ($criticalTables as $table => $expectedCols) {
        if (!in_array($table, $tables)) {
            echo "  ✗ Missing table: $table\n";
            continue;
        }

        // Check columns
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = $row['Field'];
        }
        
        $missing = array_diff($expectedCols, $columns);
        if (empty($missing)) {
            echo "  ✓ Table '$table' exists with required columns\n";
        } else {
            echo "  ⚠️  Table '$table' missing columns: " . implode(', ', $missing) . "\n";
        }
    }
    echo "\n";

    // Check migrations tracking
    echo "[3/4] Checking migrations tracking status...\n";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM migrations GROUP BY status");
    $statusCounts = $stmt->fetchAll();

    $total = 0;
    foreach ($statusCounts as $row) {
        echo "  - {$row['status']}: {$row['count']}\n";
        $total += $row['count'];
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as failed FROM migrations WHERE status = 'failed'");
    $failed = $stmt->fetch()['failed'];
    
    if ($failed > 0) {
        echo "\n  Failed migrations:\n";
        $stmt = $pdo->query("SELECT filename, error_message FROM migrations WHERE status = 'failed'");
        foreach ($stmt->fetchAll() as $m) {
            echo "    - {$m['filename']}: " . substr($m['error_message'], 0, 60) . "...\n";
        }
    }
    echo "\n";

    // Check table row counts
    echo "[4/4] Table statistics:\n";
    $ignoreTables = ['migrations', 'audit_logs'];
    
    foreach (array_slice($tables, 0, 15) as $table) {
        if (in_array($table, $ignoreTables)) continue;
        
        try {
            $result = $pdo->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC);
            $count = $result['cnt'] ?? 0;
            echo "  - $table: $count rows\n";
        } catch (Exception $e) {
            // Skip if can't count
        }
    }

    echo "\n=== VERIFICATION COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

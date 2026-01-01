<?php
/**
 * Comprehensive Migration and Testing Runner
 * Runs all migrations (.sql and .php) and tests database integrity
 */

$root = dirname(__DIR__);
require $root . '/public/config/db.php';

// Enable buffered queries to avoid "unbuffered queries" errors
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

echo "════════════════════════════════════════════════════════════════\n";
echo "HIGH-Q Database Migration & Testing Suite\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// ============= SECTION 1: SQL MIGRATIONS =============
echo "PHASE 1: Running SQL Migrations...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$dir = $root . DIRECTORY_SEPARATOR . 'migrations';
if (!is_dir($dir)) {
    echo "❌ No migrations directory found at: $dir\n";
    exit(1);
}

// Ensure migrations table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(512) NOT NULL UNIQUE,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    echo "⚠️  Could not create migrations table: " . $e->getMessage() . "\n";
}

$applied = [];
try {
    $stmt = $pdo->query("SELECT filename FROM migrations");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $applied[$r['filename']] = true;
    }
} catch (Exception $e) {
    echo "⚠️  Could not fetch applied migrations: " . $e->getMessage() . "\n";
}

$sql_files = glob($dir . DIRECTORY_SEPARATOR . '*.sql');
if (!$sql_files) {
    echo "⚠️  No SQL migration files found in $dir\n";
} else {
    sort($sql_files);
    $sql_count = 0;
    $sql_success = 0;
    $sql_failed = 0;

    foreach ($sql_files as $f) {
        $base = basename($f);
        if (isset($applied[$base])) {
            echo "⊘  Skipping already applied: $base\n";
            $sql_count++;
            continue;
        }
        
        $sql_count++;
        echo "▶  Applying: $base\n";
        
        $sql = file_get_contents($f);
        if ($sql === false) {
            echo "   ❌ Failed to read file\n";
            $sql_failed++;
            continue;
        }
        
        try {
            // Split statements properly (handle comments and semicolons)
            $lines = explode("\n", $sql);
            $statement = '';
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip empty lines and SQL comments
                if (empty($line) || substr($line, 0, 2) === '--' || substr($line, 0, 1) === '#') {
                    continue;
                }
                
                $statement .= $line . "\n";
                
                // If line ends with semicolon, execute the statement
                if (substr($line, -1) === ';') {
                    $statement = rtrim($statement, ';');
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                    $statement = '';
                }
            }
            
            // Execute any remaining statement
            if (!empty(trim($statement))) {
                $pdo->exec(trim($statement));
            }
            
            // Record migration as applied
            $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
            $ins->execute([$base]);
            echo "   ✓ Applied successfully\n";
            $sql_success++;
            
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            
            // Check if it's a non-fatal error (duplicate column, table exists, etc.)
            if (preg_match('/(Duplicate column|already exists|Duplicate key|column exists|key exists)/i', $msg)) {
                echo "   ⚠️  Non-fatal error (column/table already exists)\n";
                try {
                    $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
                    $ins->execute([$base]);
                    echo "   ✓ Marked as applied\n";
                    $sql_success++;
                } catch (Exception $ee) {
                    echo "   ❌ Failed to record: " . $ee->getMessage() . "\n";
                    $sql_failed++;
                }
            } else {
                echo "   ❌ Error: " . substr($msg, 0, 80) . "...\n";
                $sql_failed++;
            }
        }
    }
    
    echo "\n📊 SQL Migrations Summary: $sql_success applied, $sql_failed failed out of $sql_count total\n";
}

// ============= SECTION 2: PHP MIGRATIONS =============
echo "\nPHASE 2: Running PHP Migrations...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$php_files = [
    '2025-09-28-add-email-to-student-registrations.php',
    '2025-10-20-add-passport-path.php',
    'create_tutors_table.php',
    'migrate_course_features.php',
    'seed_icons.php'
];

$php_count = 0;
$php_success = 0;
$php_failed = 0;

foreach ($php_files as $php_file) {
    $file_path = $dir . DIRECTORY_SEPARATOR . $php_file;
    
    if (!file_exists($file_path)) {
        echo "⊘  File not found: $php_file\n";
        $php_count++;
        continue;
    }
    
    $php_count++;
    echo "▶  Running: $php_file\n";
    
    try {
        ob_start();
        require $file_path;
        $output = ob_get_clean();
        
        if ($output) {
            foreach (explode("\n", trim($output)) as $line) {
                if (!empty($line)) {
                    echo "   " . $line . "\n";
                }
            }
        }
        echo "   ✓ Completed successfully\n";
        $php_success++;
        
    } catch (Throwable $e) {
        ob_end_clean();
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $php_failed++;
    }
}

echo "\n📊 PHP Migrations Summary: $php_success completed, $php_failed failed out of $php_count total\n";

// ============= SECTION 3: VERIFY DATABASE INTEGRITY =============
echo "\nPHASE 3: Verifying Database Integrity...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$required_tables = [
    'users', 'roles', 'courses', 'student_registrations', 'payments',
    'posts', 'comments', 'notifications', 'forum_questions', 'forum_replies',
    'appointments', 'testimonials', 'universal_registrations'
];

$table_count = 0;
$table_found = 0;

foreach ($required_tables as $table) {
    $table_count++;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
            echo "✓ $table (rows: $count)\n";
            $table_found++;
        } else {
            echo "❌ $table - NOT FOUND\n";
        }
    } catch (Exception $e) {
        echo "⚠️  $table - Error checking: " . substr($e->getMessage(), 0, 50) . "\n";
    }
}

echo "\n📊 Table Summary: $table_found found out of $table_count expected\n";

// ============= SECTION 4: TEST DATABASE FUNCTIONS =============
echo "\nPHASE 4: Testing Database Functions...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$tests = [
    'Database Connection' => function($pdo) {
        return $pdo instanceof PDO;
    },
    'Users Table Accessible' => function($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
        return $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] >= 0;
    },
    'Student Registrations Accessible' => function($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM student_registrations");
        return $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] >= 0;
    },
    'Payments Table Accessible' => function($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM payments");
        return $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] >= 0;
    },
    'Courses Table Accessible' => function($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM courses");
        return $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] >= 0;
    },
    'Site Settings Accessible' => function($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM site_settings");
        return $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] >= 0;
    }
];

$test_passed = 0;
$test_failed = 0;

foreach ($tests as $name => $test) {
    try {
        if ($test($pdo)) {
            echo "✓ $name\n";
            $test_passed++;
        } else {
            echo "❌ $name - returned false\n";
            $test_failed++;
        }
    } catch (Exception $e) {
        echo "❌ $name - Error: " . substr($e->getMessage(), 0, 60) . "\n";
        $test_failed++;
    }
}

echo "\n📊 Tests Summary: $test_passed passed, $test_failed failed\n";

// ============= FINAL SUMMARY =============
echo "\n════════════════════════════════════════════════════════════════\n";
echo "FINAL SUMMARY\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "SQL Migrations:       $sql_success/$sql_count applied\n";
echo "PHP Migrations:       $php_success/$php_count completed\n";
echo "Tables Verified:      $table_found/$table_count found\n";
echo "Function Tests:       $test_passed/$test_failed passed\n";
echo "════════════════════════════════════════════════════════════════\n";

if ($sql_failed === 0 && $php_failed === 0 && $test_failed === 0) {
    echo "✓ ALL MIGRATIONS AND TESTS PASSED!\n";
    echo "════════════════════════════════════════════════════════════════\n";
    exit(0);
} else {
    echo "⚠️  Some issues detected. Review above for details.\n";
    echo "════════════════════════════════════════════════════════════════\n";
    exit(1);
}
?>

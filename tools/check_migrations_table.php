<?php
require_once __DIR__ . '/../admin/includes/db.php';

try {
    // Try to get table structure
    echo "Checking migrations table structure...\n";
    $stmt = $pdo->query("DESCRIBE migrations");
    $cols = $stmt->fetchAll();
    
    echo "Current columns:\n";
    foreach ($cols as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
    }
    
    // Try to see existing migrations
    echo "\nExisting migrations:\n";
    $stmt = $pdo->query("SELECT * FROM migrations LIMIT 5");
    $migs = $stmt->fetchAll();
    foreach ($migs as $m) {
        print_r($m);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

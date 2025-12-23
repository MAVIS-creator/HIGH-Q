<?php
// Test script to check comments table
require_once __DIR__ . '/admin/includes/db.php';

echo "Testing Comments Table\n";
echo str_repeat('=', 50) . "\n\n";

try {
    // Check if table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'comments'")->fetchAll();
    if (empty($tables)) {
        echo "❌ ERROR: 'comments' table does not exist!\n";
        echo "Please create the comments table first.\n";
        exit(1);
    }
    echo "✅ Comments table exists\n\n";
    
    // Get table structure
    echo "Table Structure:\n";
    $columns = $pdo->query("DESCRIBE comments")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
    echo "\n";
    
    // Count comments
    $count = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    echo "Total comments: {$count}\n\n";
    
    if ($count > 0) {
        echo "Sample comments:\n";
        $samples = $pdo->query("SELECT id, post_id, name, email, status, created_at, SUBSTRING(content, 1, 50) as preview FROM comments ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($samples as $s) {
            echo "  #{$s['id']} - {$s['name']} - {$s['status']} - {$s['preview']}...\n";
        }
    } else {
        echo "ℹ️  No comments in database yet.\n";
        echo "Comments will appear after visitors leave comments on posts.\n";
    }
    
    echo "\n✅ Test complete!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

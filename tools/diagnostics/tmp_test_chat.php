<?php
// Test script to check chat threads
require_once __DIR__ . '/admin/includes/db.php';

echo "Testing Chat Threads Table\n";
echo str_repeat('=', 50) . "\n\n";

try {
    // Check if table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'chat_threads'")->fetchAll();
    if (empty($tables)) {
        echo "❌ ERROR: 'chat_threads' table does not exist!\n";
        echo "Please create the chat_threads table first.\n";
        exit(1);
    }
    echo "✅ chat_threads table exists\n\n";
    
    // Check chat_messages table
    $tables2 = $pdo->query("SHOW TABLES LIKE 'chat_messages'")->fetchAll();
    if (empty($tables2)) {
        echo "❌ ERROR: 'chat_messages' table does not exist!\n";
        exit(1);
    }
    echo "✅ chat_messages table exists\n\n";
    
    // Count threads
    $count = $pdo->query("SELECT COUNT(*) FROM chat_threads")->fetchColumn();
    echo "Total chat threads: {$count}\n\n";
    
    if ($count > 0) {
        echo "Sample threads:\n";
        $samples = $pdo->query("SELECT id, visitor_name, visitor_email, status, assigned_admin_id, last_activity FROM chat_threads ORDER BY last_activity DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($samples as $s) {
            echo "  #{$s['id']} - {$s['visitor_name']} ({$s['visitor_email']}) - Status: {$s['status']} - Assigned: " . ($s['assigned_admin_id'] ?? 'None') . "\n";
        }
    } else {
        echo "ℹ️  No chat threads in database yet.\n";
        echo "Threads will appear after visitors start chatting.\n";
    }
    
    echo "\n✅ Test complete!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

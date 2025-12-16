<?php
// Check notifications table structure
require_once __DIR__ . '/../admin/includes/db.php';

try {
    $stmt = $pdo->query('SHOW CREATE TABLE notifications');
    $row = $stmt->fetch();
    echo "Current notifications table structure:\n\n";
    echo $row[1];
    echo "\n\n";
    
    // Check if unique key exists
    $stmt = $pdo->query("SHOW INDEXES FROM notifications WHERE Key_name = 'unique_user_notification'");
    $indexes = $stmt->fetchAll();
    
    if (empty($indexes)) {
        echo "❌ Missing unique key 'unique_user_notification'\n";
        echo "This key is required for notification persistence to work properly.\n\n";
        
        echo "Migration needed:\n";
        echo "ALTER TABLE notifications ADD UNIQUE KEY unique_user_notification (user_id, type, reference_id);\n";
    } else {
        echo "✅ Unique key 'unique_user_notification' exists\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

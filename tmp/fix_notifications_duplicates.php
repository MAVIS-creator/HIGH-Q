<?php
// Clean up duplicate notifications before adding unique key
require_once __DIR__ . '/../admin/includes/db.php';

try {
    echo "Checking for duplicate notifications...\n\n";
    
    // Find duplicates
    $stmt = $pdo->query("
        SELECT user_id, type, reference_id, COUNT(*) as count
        FROM notifications
        GROUP BY user_id, type, reference_id
        HAVING count > 1
    ");
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "✅ No duplicates found.\n\n";
    } else {
        echo "Found " . count($duplicates) . " sets of duplicates:\n";
        foreach ($duplicates as $dup) {
            echo "  - User {$dup['user_id']}, Type {$dup['type']}, Ref {$dup['reference_id']}: {$dup['count']} records\n";
        }
        echo "\n";
        
        echo "Cleaning up duplicates (keeping the most recent read status)...\n";
        
        // For each duplicate set, keep only one record (the most recently read one, or the oldest if none are read)
        foreach ($duplicates as $dup) {
            $pdo->beginTransaction();
            
            // Get all IDs for this duplicate set
            $stmt = $pdo->prepare("
                SELECT id, is_read, read_at
                FROM notifications
                WHERE user_id = ? AND type = ? AND reference_id = ?
                ORDER BY is_read DESC, read_at DESC, id ASC
            ");
            $stmt->execute([$dup['user_id'], $dup['type'], $dup['reference_id']]);
            $records = $stmt->fetchAll();
            
            // Keep the first one (most recently read, or oldest)
            $keepId = $records[0]['id'];
            
            // Delete the rest
            $deleteIds = array_slice(array_column($records, 'id'), 1);
            if (!empty($deleteIds)) {
                $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id IN ($placeholders)");
                $stmt->execute($deleteIds);
                echo "  Deleted " . count($deleteIds) . " duplicate(s) for User {$dup['user_id']}, Type {$dup['type']}, Ref {$dup['reference_id']}\n";
            }
            
            $pdo->commit();
        }
        
        echo "\n✅ Duplicates cleaned up successfully!\n\n";
    }
    
    // Now add the unique key
    echo "Adding unique key to notifications table...\n";
    $sql = "ALTER TABLE notifications 
            ADD UNIQUE KEY unique_user_notification (user_id, type, reference_id)";
    
    $pdo->exec($sql);
    
    echo "✅ Migration completed successfully!\n";
    echo "The notifications table now has a unique key on (user_id, type, reference_id).\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "ℹ️  Unique key already exists. No action needed.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

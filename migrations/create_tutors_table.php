<?php
/**
 * Create tutors table migration
 * Run this file once to create the tutors table
 */

require_once __DIR__ . '/../admin/includes/db.php';

try {
    echo "Creating tutors table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS tutors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        subjects TEXT,
        experience INT DEFAULT 0,
        email VARCHAR(255),
        phone VARCHAR(50),
        qualifications TEXT,
        bio TEXT,
        photo VARCHAR(500),
        is_active TINYINT(1) DEFAULT 1,
        is_featured TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active (is_active),
        INDEX idx_featured (is_featured)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    echo "✓ Tutors table created successfully!\n\n";
    
    // Verify table structure
    $stmt = $pdo->query('DESCRIBE tutors');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table structure:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

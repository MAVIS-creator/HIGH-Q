<?php
require_once __DIR__ . '/admin/includes/db.php';

try {
    $stmt = $pdo->query('DESCRIBE tutors');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Tutors table exists with columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nCreating tutors table...\n";
    
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tutors (
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "✓ Tutors table created successfully!\n";
    } catch (Exception $e2) {
        echo "✗ Failed to create table: " . $e2->getMessage() . "\n";
    }
}

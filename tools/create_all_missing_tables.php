<?php
require 'public/config/db.php';

echo "Creating missing tables...\n\n";

// 1. menus table
try {
    $sql1 = <<<SQL
    CREATE TABLE IF NOT EXISTS menus (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      slug VARCHAR(80) NOT NULL,
      title VARCHAR(150) NOT NULL,
      icon VARCHAR(80) DEFAULT NULL,
      url VARCHAR(255) NOT NULL,
      sort_order INT NOT NULL DEFAULT 100,
      enabled TINYINT(1) NOT NULL DEFAULT 1,
      created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uniq_menus_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    SQL;
    $pdo->exec($sql1);
    echo "✓ menus table created\n";
} catch (Exception $e) {
    echo "✗ menus: " . $e->getMessage() . "\n";
}

// 2. testimonials table
try {
    $sql2 = <<<SQL
    CREATE TABLE IF NOT EXISTS testimonials (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      role_institution VARCHAR(255) DEFAULT NULL,
      testimonial_text TEXT NOT NULL,
      image_path VARCHAR(500) DEFAULT NULL,
      outcome_badge VARCHAR(100) DEFAULT NULL,
      display_order INT DEFAULT 0,
      is_active TINYINT(1) DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_active_order (is_active, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    SQL;
    $pdo->exec($sql2);
    echo "✓ testimonials table created\n";
} catch (Exception $e) {
    echo "✗ testimonials: " . $e->getMessage() . "\n";
}

// 3. universal_registrations table
try {
    $sql3 = <<<SQL
    CREATE TABLE IF NOT EXISTS universal_registrations (
      id INT AUTO_INCREMENT PRIMARY KEY,
      program_type VARCHAR(50) NOT NULL,
      first_name VARCHAR(150) NOT NULL,
      last_name VARCHAR(150) DEFAULT NULL,
      email VARCHAR(190) DEFAULT NULL,
      phone VARCHAR(50) DEFAULT NULL,
      status VARCHAR(50) NOT NULL DEFAULT 'pending',
      payment_reference VARCHAR(100) DEFAULT NULL,
      payment_status VARCHAR(50) DEFAULT 'pending',
      amount DECIMAL(12,2) DEFAULT 0.00,
      payment_method VARCHAR(50) DEFAULT 'online',
      payload JSON NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_program_status (program_type, status),
      INDEX idx_payment_ref (payment_reference),
      INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    SQL;
    $pdo->exec($sql3);
    echo "✓ universal_registrations table created\n";
} catch (Exception $e) {
    echo "✗ universal_registrations: " . $e->getMessage() . "\n";
}

echo "\n✅ All missing tables created\n";

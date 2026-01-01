<?php
// migrations/seed_icons.php
require_once __DIR__ . '/../admin/includes/db.php';

// Ensure icons table has the required columns
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS icons (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) DEFAULT NULL,
    filename VARCHAR(255) DEFAULT NULL,
    class VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )");
  
  // Add missing columns if they don't exist
  $pdo->exec("ALTER TABLE icons ADD COLUMN IF NOT EXISTS name VARCHAR(255)");
  $pdo->exec("ALTER TABLE icons ADD COLUMN IF NOT EXISTS filename VARCHAR(255)");
  $pdo->exec("ALTER TABLE icons ADD COLUMN IF NOT EXISTS class VARCHAR(255)");
} catch (Exception $e) {
  // Table may already exist with all columns
}

$icons = [
  ['Target','target.svg','bx bxs-bullseye'],
  ['Book Stack','book-stack.svg','bx bxs-book-bookmark'],
  ['Book Open','book-open.svg','bx bxs-book-open'],
  ['Trophy','trophy.svg','bx bxs-trophy'],
  ['Star','star.svg','bx bxs-star'],
  ['Laptop','laptop.svg','bx bxs-laptop'],
  ['Teacher','teacher.svg','bx bxs-user'],
  ['Results','results.svg','bx bxs-bar-chart-alt-2'],
  ['Graduation','graduation.svg','bx bxs-graduation']
];

$stmt = $pdo->prepare("INSERT INTO icons (name, filename, class) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE class = VALUES(class), name = VALUES(name)");
$count = 0;
foreach ($icons as $ic) {
  $stmt->execute($ic);
  $count++;
}
echo "Seeded $count icons.\n";

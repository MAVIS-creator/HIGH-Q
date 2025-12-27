<?php
require_once __DIR__ . '/admin/includes/db.php';

try {
    $stmt = $pdo->prepare("UPDATE menus SET url = ? WHERE slug = ?");
    $stmt->execute(['index.php?pages=patcher', 'patcher']);
    echo "Updated patcher URL in database to: index.php?pages=patcher\n";
    echo "Rows affected: " . $stmt->rowCount() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

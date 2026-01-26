<?php
// Quick debug script
require_once __DIR__ . '/config/db.php';

echo "Site Settings columns:\n";
$stmt = $pdo->query("DESCRIBE site_settings");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($cols);

echo "\n\nSite Settings values:\n";
$stmt = $pdo->query("SELECT * FROM site_settings LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($row);

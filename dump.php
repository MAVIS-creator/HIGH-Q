<?php
require 'C:\xampp\htdocs\HIGH-Q\admin\includes\db.php';
echo "=== payments columns ===\n";
$cols = $pdo->query('DESCRIBE payments')->fetchAll(PDO::FETCH_COLUMN);
print_r($cols);

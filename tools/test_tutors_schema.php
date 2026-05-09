<?php
require 'admin/includes/db.php';

// Get table schema
$stmt = $pdo->query('SHOW CREATE TABLE tutors');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo $result['Create Table'] . PHP_EOL;

// Get column info
echo "\n\nCOLUMN INFO:\n";
$stmt = $pdo->query('DESCRIBE tutors');
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . ' - ' . $col['Type'] . ' - Null: ' . $col['Null'] . ' - Key: ' . $col['Key'] . PHP_EOL;
}
?>

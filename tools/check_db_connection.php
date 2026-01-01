<?php
require 'public/config/db.php';
echo "Database: " . $pdo->query('SELECT DATABASE() as db')->fetch()['db'] . "\n";
echo "Tables: ";
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo count($tables) . " total\n";
echo "site_settings exists: " . (in_array('site_settings', $tables) ? 'YES' : 'NO') . "\n";
echo "course_features exists: " . (in_array('course_features', $tables) ? 'YES' : 'NO') . "\n";

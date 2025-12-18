<?php
require 'public/config/db.php';

// Check table structure
$result = $pdo->query("DESCRIBE courses");
$columns = $result->fetchAll(PDO::FETCH_ASSOC);
echo "Courses table columns:\n";
foreach ($columns as $col) {
	echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n\nNow testing query with available columns:\n";
$slug = 'jamb-post-utme';
$stmt = $pdo->prepare("SELECT id, title, slug, description, price, duration FROM courses WHERE slug = ? AND is_active = 1");
$result = $stmt->execute([$slug]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Query result for slug '$slug':\n";
var_dump($program);

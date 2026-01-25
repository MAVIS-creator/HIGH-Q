<?php
// Script to update testimonials with image paths from CSV
require_once __DIR__ . '/public/config/db.php';

// Map student names to their image paths
$imageMap = [
    'ADEDUNYE KINGSLEY OLUWAPELUMI' => 'uploads/wall-of-fame/kingsley.jpeg',
    'Fadele Oluwanifemi Abigail' => 'uploads/wall-of-fame/oluwanifemi.jpg',
    'Adeyemi Wahab Ayoade' => 'uploads/wall-of-fame/wahab.jpg',
    'Ogunsanya Zainab Olayinka' => 'uploads/wall-of-fame/zainab.jpg',
];

echo "Fetching testimonials...\n";

$stmt = $pdo->query('SELECT id, name, image_path FROM testimonials WHERE is_active = 1');
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($testimonials) . " testimonials\n\n";

foreach ($testimonials as $t) {
    echo "ID: {$t['id']} | Name: {$t['name']} | Image: " . ($t['image_path'] ?: 'NONE') . "\n";
    
    // Check if we have an image for this person (case-insensitive match)
    foreach ($imageMap as $name => $imagePath) {
        if (stripos($t['name'], substr($name, 0, 10)) !== false || 
            stripos($name, substr($t['name'], 0, 10)) !== false) {
            
            echo "  -> Matched to: $name\n";
            echo "  -> Setting image to: $imagePath\n";
            
            $upd = $pdo->prepare('UPDATE testimonials SET image_path = ? WHERE id = ?');
            $upd->execute([$imagePath, $t['id']]);
            echo "  -> UPDATED!\n";
            break;
        }
    }
}

echo "\nDone!\n";

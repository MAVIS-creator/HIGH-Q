<?php
require 'public/config/db.php';
$slug = 'jamb-post-utme';
$stmt = $pdo->prepare("SELECT id, title, slug, description, image_url, price, duration FROM courses WHERE slug = ? AND is_active = 1");
$result = $stmt->execute([$slug]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Query result for slug '$slug':\n";
var_dump($program);

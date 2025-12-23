<?php
include 'admin/includes/db.php';

echo "=== Users table structure ===\n";
$stmt = $pdo->query("DESCRIBE users");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== Sample user data ===\n";
$stmt = $pdo->query("SELECT id, name, email, role_id FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);

echo "\n=== Check role_permissions for role_id {$user['role_id']} ===\n";
$stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id = ?");
$stmt->execute([$user['role_id']]);
echo "Permissions:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  - " . $row['menu_slug'] . "\n";
}

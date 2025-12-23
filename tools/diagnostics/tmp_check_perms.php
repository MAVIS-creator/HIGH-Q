<?php
session_start();
include 'admin/includes/db.php';

if (empty($_SESSION['user'])) {
    die("Not logged in");
}

$userId = $_SESSION['user']['id'];
$roleId = $_SESSION['user']['role'];

echo "User ID: $userId\n";
echo "User Role: {$_SESSION['user']['role']}\n";
echo "User Name: {$_SESSION['user']['name']}\n";

// Check if user has permission for 'chat'
$stmt = $pdo->prepare("SELECT 1 FROM role_permissions WHERE role_id=? AND menu_slug=?");
$stmt->execute([$roleId, 'chat']);
$hasChatPerms = $stmt->fetch() ? "YES" : "NO";

// Check if user has permission for 'comments'
$stmt = $pdo->prepare("SELECT 1 FROM role_permissions WHERE role_id=? AND menu_slug=?");
$stmt->execute([$roleId, 'comments']);
$hasCommentPerms = $stmt->fetch() ? "YES" : "NO";

echo "Has chat permission: $hasChatPerms\n";
echo "Has comments permission: $hasCommentPerms\n";

// Check what permissions this role has
$stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id=? ORDER BY menu_slug");
$stmt->execute([$roleId]);
echo "\nAll permissions for this role:\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  - " . $row['menu_slug'] . "\n";
}

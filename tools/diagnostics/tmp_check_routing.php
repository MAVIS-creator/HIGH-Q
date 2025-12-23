<?php
// Quick test of index.php routing

// Test what happens when we access with pages parameter
echo "Testing index.php routing...\n\n";

// Simulate accessing /admin/index.php?pages=chat
$_GET['pages'] = 'chat';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

// Check if the file would be found
$page = $_GET['pages'];
$pageFile = __DIR__ . '/admin/pages/' . $page . '.php';

echo "Looking for: $pageFile\n";
echo "File exists: " . (file_exists($pageFile) ? "YES" : "NO") . "\n";
echo "Regex match: " . (preg_match('/^[a-zA-Z0-9_-]+$/', $page) ? "YES" : "NO") . "\n\n";

// Check what __DIR__ is in index.php context
echo "When index.php uses __DIR__ . '/pages/' . page:\n";
$testDir = __DIR__ . '/admin';
echo "Base would be: $testDir\n";
echo "Full path would be: $testDir/pages/chat.php\n";
echo "That file exists: " . (file_exists("$testDir/pages/chat.php") ? "YES" : "NO") . "\n";

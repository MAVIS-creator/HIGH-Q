<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting tests...\n";

// Test if files exist
$files = [
    'admin/includes/db.php',
    'admin/includes/auth.php',
    'admin/includes/csrf.php',
    'admin/includes/functions.php',
    'admin/pages/comments.php',
    'admin/pages/chat.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file NOT FOUND\n";
    }
}

echo "\nTesting requirePermission function...\n";

session_start();
include 'admin/includes/db.php';

// Get an admin user
$stmt = $pdo->query("SELECT id, name, email, role, role_id FROM users WHERE role = 'Admin' LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("No admin user found\n");
}

$_SESSION['user'] = $user;
echo "✓ Session set for: {$user['name']}\n";

// Now test if requirePermission is defined
include_once 'admin/includes/auth.php';
include_once 'admin/includes/functions.php';

echo "\nChecking if requirePermission function exists...\n";
if (function_exists('requirePermission')) {
    echo "✓ requirePermission function exists\n";
    
    // Test calling it
    try {
        requirePermission('comments');
        echo "✓ requirePermission('comments') succeeded\n";
    } catch (Exception $e) {
        echo "✗ requirePermission('comments') failed: " . $e->getMessage() . "\n";
    }
    
    try {
        requirePermission('chat');
        echo "✓ requirePermission('chat') succeeded\n";
    } catch (Exception $e) {
        echo "✗ requirePermission('chat') failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ requirePermission function NOT FOUND\n";
}

echo "\n=== Now testing actual AJAX call ===\n";

// Simulate AJAX request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_POST = [
    'action' => 'approve',
    'id' => 1
];

// Include CSRF
include_once 'admin/includes/csrf.php';
$_POST['_csrf'] = generateToken('comments_form');

// Capture output
ob_start();
include 'admin/pages/comments.php';
$output = ob_get_clean();

echo "Output length: " . strlen($output) . " bytes\n";
echo "First 200 chars:\n";
echo substr($output, 0, 200) . "\n";

if (strlen($output) > 200) {
    echo "\n...truncated...\n";
    echo "Last 200 chars:\n";
    echo substr($output, -200) . "\n";
}

// Check if JSON
$json = @json_decode($output, true);
if ($json !== null) {
    echo "\n✓ Valid JSON:\n";
    print_r($json);
} else {
    echo "\n✗ NOT JSON\n";
}

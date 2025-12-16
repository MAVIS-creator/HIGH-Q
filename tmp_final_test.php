<?php
// Final comprehensive test of admin AJAX routing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== FINAL AJAX ROUTING TEST ===\n\n";

// Simulate logged-in session
session_start();
include 'admin/includes/db.php';
$stmt = $pdo->query("SELECT id, name, email, role_id FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['user'] = $user;

echo "User: {$user['name']} (ID: {$user['id']}, Role ID: {$user['role_id']})\n\n";

// Test 1: Access /admin/index.php?pages=comments as AJAX
echo "Test 1: AJAX POST to /admin/index.php?pages=comments\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_GET['pages'] = 'comments';
$_POST['action'] = 'approve';
$_POST['id'] = 1;

include_once 'admin/includes/csrf.php';
$_POST['_csrf'] = generateToken('comments_form');

ob_start();
include 'admin/index.php';
$output1 = ob_get_clean();

echo "Response length: " . strlen($output1) . " bytes\n";
echo "First 200 chars: " . substr($output1, 0, 200) . "\n";

if (strpos($output1, 'landing-page') !== false) {
    echo "✗ ERROR: Got landing page HTML!\n";
} elseif (strpos($output1, '{"status"') !== false) {
    echo "✓ SUCCESS: Got JSON response!\n";
    $json = @json_decode(trim($output1), true);
    if ($json) {
        echo "JSON: " . json_encode($json) . "\n";
    }
} else {
    echo "? Unknown response type\n";
}

echo "\n";

// Clean up
unset($_GET);
unset($_POST);
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($_SERVER['HTTP_X_REQUESTED_WITH']);

// Test 2: Access /admin/index.php?pages=chat as AJAX  
echo "Test 2: AJAX POST to /admin/index.php?pages=chat\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_GET['pages'] = 'chat';
$_POST['action'] = 'claim';
$_POST['thread_id'] = 1;
$_POST['_csrf'] = generateToken('chat_form');

ob_start();
include 'admin/index.php';
$output2 = ob_get_clean();

echo "Response length: " . strlen($output2) . " bytes\n";
echo "First 200 chars: " . substr($output2, 0, 200) . "\n";

if (strpos($output2, 'landing-page') !== false) {
    echo "✗ ERROR: Got landing page HTML!\n";
} elseif (strpos($output2, '{"status"') !== false) {
    echo "✓ SUCCESS: Got JSON response!\n";
    $json = @json_decode(trim($output2), true);
    if ($json) {
        echo "JSON: " . json_encode($json) . "\n";
    }
} else {
    echo "? Unknown response type\n";
}

echo "\n=== TESTS COMPLETE ===\n";

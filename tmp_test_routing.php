<?php
// Test the new routing
session_start();

// Set up test session
include 'admin/includes/db.php';
$stmt = $pdo->query("SELECT id, name, email, role_id FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['user'] = $user;

echo "Testing routing with AJAX simulation...\n\n";

// Test 1: Comments page with AJAX
echo "=== Test 1: Comments AJAX (POST + X-Requested-With) ===\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_GET['pages'] = 'comments';
$_POST['action'] = 'approve';
$_POST['id'] = 1;

// Generate CSRF
include_once 'admin/includes/csrf.php';
$_POST['_csrf'] = generateToken('comments_form');

ob_start();
include 'admin/index.php';
$output = ob_get_clean();

echo "Response length: " . strlen($output) . " bytes\n";
echo "First 200 chars: " . substr($output, 0, 200) . "\n";

$json = @json_decode($output, true);
if ($json !== null) {
    echo "✓ Valid JSON response!\n";
    echo "JSON: " . json_encode($json) . "\n";
} else {
    echo "✗ NOT JSON - got HTML\n";
    if (strpos($output, '<!doctype') !== false) {
        echo "✗ ERROR: Got landing page HTML!\n";
    }
}

echo "\n";

// Clean up for next test
unset($_POST);
unset($_GET);
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($_SERVER['HTTP_X_REQUESTED_WITH']);

// Test 2: Chat page with AJAX
echo "=== Test 2: Chat AJAX (POST + X-Requested-With) ===\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_GET['pages'] = 'chat';
$_POST['action'] = 'claim';
$_POST['thread_id'] = 1;
$_POST['_csrf'] = generateToken('chat_form');

ob_start();
include 'admin/index.php';
$output = ob_get_clean();

echo "Response length: " . strlen($output) . " bytes\n";
echo "First 200 chars: " . substr($output, 0, 200) . "\n";

$json = @json_decode($output, true);
if ($json !== null) {
    echo "✓ Valid JSON response!\n";
    echo "JSON: " . json_encode($json) . "\n";
} else {
    echo "✗ NOT JSON - got HTML\n";
    if (strpos($output, '<!doctype') !== false) {
        echo "✗ ERROR: Got landing page HTML!\n";
    }
}

echo "\n=== Tests complete ===\n";

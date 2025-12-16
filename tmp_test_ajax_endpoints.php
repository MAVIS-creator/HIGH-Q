<?php
// Test AJAX endpoints for chat and comments
session_start();

// Simulate being logged in as admin
if (empty($_SESSION['user'])) {
    // For testing, set up a fake admin session
    include 'admin/includes/db.php';
    
    // Get first admin user
    $stmt = $pdo->query("SELECT id, name, email, role, role_id FROM users WHERE role = 'Admin' LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['user'] = $user;
        echo "✓ Set up test session for user: {$user['name']} (Role: {$user['role']})\n\n";
    } else {
        die("✗ No admin user found in database\n");
    }
}

echo "=== SESSION DATA ===\n";
echo "User ID: " . $_SESSION['user']['id'] . "\n";
echo "User Role: " . $_SESSION['user']['role'] . "\n";
echo "User Name: " . $_SESSION['user']['name'] . "\n";

// Generate CSRF token
include 'admin/includes/csrf.php';
$csrfToken = generateToken('comments_form');
echo "CSRF Token: $csrfToken\n\n";

// Test 1: Comments endpoint
echo "=== TEST 1: Comments Approve (AJAX) ===\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_POST['action'] = 'approve';
$_POST['id'] = '1';
$_POST['_csrf'] = $csrfToken;

ob_start();
try {
    include 'admin/pages/comments.php';
    $response = ob_get_clean();
    
    echo "Response Length: " . strlen($response) . " bytes\n";
    echo "First 500 chars:\n";
    echo substr($response, 0, 500) . "\n\n";
    
    // Try to parse as JSON
    $json = json_decode($response, true);
    if ($json !== null) {
        echo "✓ Valid JSON Response:\n";
        echo json_encode($json, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "✗ NOT JSON! Full response:\n";
        echo $response . "\n\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "✗ Exception: " . $e->getMessage() . "\n\n";
}

// Reset for next test
unset($_POST);
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($_SERVER['HTTP_X_REQUESTED_WITH']);

// Test 2: Chat endpoint
echo "=== TEST 2: Chat Claim (AJAX) ===\n";
$csrfTokenChat = generateToken('chat_form');
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_POST['action'] = 'claim';
$_POST['thread_id'] = '1';
$_POST['_csrf'] = $csrfTokenChat;

ob_start();
try {
    include 'admin/pages/chat.php';
    $response = ob_get_clean();
    
    echo "Response Length: " . strlen($response) . " bytes\n";
    echo "First 500 chars:\n";
    echo substr($response, 0, 500) . "\n\n";
    
    // Try to parse as JSON
    $json = json_decode($response, true);
    if ($json !== null) {
        echo "✓ Valid JSON Response:\n";
        echo json_encode($json, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "✗ NOT JSON! Full response:\n";
        echo $response . "\n\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "✗ Exception: " . $e->getMessage() . "\n\n";
}

echo "=== TESTS COMPLETE ===\n";

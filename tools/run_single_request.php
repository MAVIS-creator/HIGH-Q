<?php
// tools/run_single_request.php
// Usage: php run_single_request.php --page=payments --action=confirm --id=1
chdir(__DIR__ . '/..'); // project root
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simple CLI arg parsing
$options = getopt('', ['page:', 'action::', 'id::']);
$page = $options['page'] ?? null;
action:
$action = $options['action'] ?? null;
$id = isset($options['id']) ? (int)$options['id'] : 0;

if (!$page) {
    echo "Usage: php run_single_request.php --page=payments|comments --action=<action> --id=<id>\n";
    exit(1);
}

// Emulate a logged-in admin user in session
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = $_SESSION ?? [];
// Minimal user object sufficient for admin AJAX handlers
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'CLI Admin',
    'email' => 'cli@example.local',
];

// Ensure CSRF helper is available
$csrfPath = __DIR__ . '/../admin/includes/csrf.php';
if (file_exists($csrfPath)) require_once $csrfPath;

// Prepare superglobals as an AJAX POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_POST = [];

switch ($page) {
    case 'payments':
        // generate token for payments_form
        $token = function_exists('generateToken') ? generateToken('payments_form') : '';
        $_POST['_csrf'] = $token;
        $_POST['action'] = $action ?? 'confirm';
        $_POST['id'] = $id ?: 1;
        // include the admin payments endpoint which handles AJAX at top
        include __DIR__ . '/../admin/pages/payments.php';
        break;

    case 'comments':
        $token = function_exists('generateToken') ? generateToken('comments_form') : '';
        $_POST['_csrf'] = $token;
        $_POST['action'] = $action ?? 'approve';
        $_POST['id'] = $id ?: 1;
        include __DIR__ . '/../admin/pages/comments.php';
        break;

    default:
        echo "Unsupported page: {$page}\n";
        exit(2);
}

// Note: included admin pages may call exit() after sending JSON; that's expected.


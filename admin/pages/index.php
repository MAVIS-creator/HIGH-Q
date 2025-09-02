<?php
// admin/pages/index.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';

// Show all errors - for development only
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use the 'pages' query param (you said you want 'pages' all through)
$page = isset($_GET['pages']) ? basename($_GET['pages']) : 'dashboard';
// sanitize page slug
$page = preg_replace('/[^a-z0-9_-]/i', '', $page);
$pageTitle = ucwords(str_replace(['-', '_'], ' ', $page));

// Fetch allowed pages for the current user's role
$userRoleId = $_SESSION['user']['role_id'] ?? null;
$allowed_pages = [];

if ($userRoleId) {
    $stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$userRoleId]);
    $allowed_pages = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Normalize allowed slugs (strip any ".php" or weird chars)
$allowed_pages = array_map(function($slug) {
    return preg_replace('/[^a-z0-9_-]/i', '', basename($slug, '.php'));
}, $allowed_pages);

// Make sure dashboard is always reachable
if (!in_array('dashboard', $allowed_pages)) {
    $allowed_pages[] = 'dashboard';
}

// Security: if page not allowed, fallback to dashboard
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
    $pageTitle = 'Dashboard';
}

// Include layout parts (paths relative to this file)
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// Try sensible locations for the page file (avoids pages/pages/ double-nesting)
$candidates = [
    __DIR__ . "/{$page}.php",           // admin/pages/{page}.php
    __DIR__ . "/pages/{$page}.php",     // admin/pages/pages/{page}.php (if that exists)
    __DIR__ . "/../pages/{$page}.php",  // admin/pages/../pages/{page}.php
];

$found = false;
foreach ($candidates as $file) {
    if (file_exists($file)) {
        include $file;
        $found = true;
        break;
    }
}

if (!$found) {
    // Friendly debug output to show where it's looking
    echo "<div class='container'><h2>Page not found</h2>";
    echo "<p>Looking for: <strong>" . htmlspecialchars($page) . "</strong></p>";
    echo "<p>Checked paths:</p><ul>";
    foreach ($candidates as $c) {
        echo "<li>" . htmlspecialchars($c) . "</li>";
    }
    echo "</ul></div>";
}

include __DIR__ . '/../includes/footer.php';

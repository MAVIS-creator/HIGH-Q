<?php
// admin/pages/index.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';


// Use the 'pages' query param (you said you want 'pages' all through)
$page = isset($_GET['pages']) ? basename($_GET['pages']) : 'dashboard';
// sanitize page slug
$page = preg_replace('/[^a-z0-9_-]/i', '', $page);
$pageTitle = ucwords(str_replace(['-', '_'], ' ', $page));

// Provide sensible default subtitles and optional per-page CSS links
$pageMeta = [
    'dashboard' => [
        'subtitle' => 'Overview and quick stats for your site',
    ],
    'users' => [
        'subtitle' => 'Manage user accounts, roles, and permissions',
        'css' => '<link rel="stylesheet" href="../assets/css/users.css">',
    ],
    'roles' => [
        'subtitle' => 'Manage roles and permissions',
    ],
    'courses' => [
        'subtitle' => 'Manage courses and programs offered on the site',
    ],
    'tutors' => [
        'subtitle' => 'Manage tutor profiles and listings',
    ],
    'posts' => [
        'subtitle' => 'Create and manage news articles and blog posts',
    ],
];

// If the page hasn't set its own pageTitle/subtitle/css, use defaults from mapping
if (empty($pageTitle)) {
    $pageTitle = ucwords(str_replace(['-', '_'], ' ', $page));
}
if (!empty($pageMeta[$page])) {
    if (!isset($pageSubtitle)) $pageSubtitle = $pageMeta[$page]['subtitle'] ?? '';
    if (!isset($pageCss) && !empty($pageMeta[$page]['css'])) $pageCss = $pageMeta[$page]['css'];
}

// Ensure the user is authenticated before proceeding to header/sidebar
ensureAuthenticated();

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

// If the role has the 'settings' permission, allow access to audit_logs (convenience)
if (in_array('settings', $allowed_pages) && !in_array('audit_logs', $allowed_pages)) {
    $allowed_pages[] = 'audit_logs';
}

// Security: allow exact matches or logical subpages (e.g. 'chat_view') if base permission exists.
$pageAllowed = false;
if (in_array($page, $allowed_pages)) {
    $pageAllowed = true;
} else {
    // allow pages that start with an allowed slug + separator (underscore or dash)
    foreach ($allowed_pages as $ap) {
        if ($ap === '') continue;
        if (stripos($page, $ap . '_') === 0 || stripos($page, $ap . '-') === 0) {
            $pageAllowed = true;
            break;
        }
    }
}

// If this is a POST that includes an action, allow the requested page to be included so
// the page's own permission checks and POST handlers can run. This avoids returning the
// dashboard HTML for background POSTs where the role-permissions mapping might not match
// (the page itself will still call requirePermission()).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $pageAllowed = true;
}

if (!$pageAllowed) {
    // fallback to dashboard
    // If requested page file exists and the role has the 'settings' permission, allow it (convenience for admins)
    $candidatesExplicit = [__DIR__ . "/{$page}.php", __DIR__ . "/pages/{$page}.php", __DIR__ . "/../pages/{$page}.php"];
    $fileExists = false;
    foreach ($candidatesExplicit as $f) { if (file_exists($f)) { $fileExists = true; break; } }
    if ($fileExists && in_array('settings', $allowed_pages)) {
        $pageAllowed = true;
    }

    if (!$pageAllowed) {
        $page = 'dashboard';
        $pageTitle = 'Dashboard';
    }
}

// Include layout parts (paths relative to this file)
// If this is an AJAX/JSON request (background POST with action or X-Requested-With), avoid rendering the full admin chrome
$isAjaxRequest = false;
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $isAjaxRequest = true;
}
if (!$isAjaxRequest && !empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    $isAjaxRequest = true;
}
if (!$isAjaxRequest && !empty($_REQUEST['action'])) {
    // treat POST/GET with an action param as AJAX API-style request
    $isAjaxRequest = true;
}

if (!$isAjaxRequest) {
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/sidebar.php';
}

// Try sensible locations for the page file (avoids pages/pages/ double-nesting)
$candidates = [
    __DIR__ . "/{$page}.php",         
    __DIR__ . "/pages/{$page}.php",    
    __DIR__ . "/../pages/{$page}.php", 
];



$found = false;
foreach ($candidates as $file) {
    if (file_exists($file)) {
        include $file;
        $found = true;
        break;
    }
}

// If not found, try a simple singular fallback (e.g., 'posts' -> 'post.php')
if (!$found && substr($page, -1) === 's') {
    $sing = rtrim($page, 's');
    $singCandidates = [
        __DIR__ . "/{$sing}.php",
        __DIR__ . "/pages/{$sing}.php",
        __DIR__ . "/../pages/{$sing}.php",
    ];
    foreach ($singCandidates as $file) {
        if (file_exists($file)) {
            include $file;
            $found = true;
            break;
        }
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

require_once __DIR__ . '/../includes/footer.php';

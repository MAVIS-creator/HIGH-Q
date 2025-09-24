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

if (!$pageAllowed) {
    // fallback to dashboard
    $page = 'dashboard';
    $pageTitle = 'Dashboard';
}

// Include layout parts (paths relative to this file)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

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

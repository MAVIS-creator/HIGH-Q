<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ './includes/db.php';

// Get requested page or default to dashboard
$page = isset($_GET['page']) ? basename($_GET['page']) : 'dashboard';
$pageTitle = ucfirst($page);

// Fetch all allowed pages for the current user's role
$userRoleId = $_SESSION['user']['role_id'];
$stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id = ?");
$stmt->execute([$userRoleId]);
$allowed_pages = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Security: if page not allowed, fallback to dashboard
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
    $pageTitle = 'Dashboard';
}

include '../includes/header.php';
include '../includes/sidebar.php';

// Include the requested page safely
$pageFile = __DIR__ . "/{$page}.php";
if (file_exists($pageFile)) {
    include $pageFile;
} else {
    echo "<div class='container'><h2>Page not found!</h2></div>";
}

include '../includes/footer.php';

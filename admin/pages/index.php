<?php
require './includes/auth.php';
require '.../config/db.php';

// Get requested page or default to dashboard
$page = isset($_GET['page']) ? basename($_GET['page']) : 'dashboard';

// Whitelist allowed pages
$allowed_pages = [
    'dashboard' => ['admin','sub-admin','moderator'],
    'users'     => ['admin'],
    'roles'     => ['admin'],
    'courses'   => ['admin','sub-admin'],
    'tutors'    => ['admin','sub-admin'],
    'students'  => ['admin','sub-admin'],
    'payments'  => ['admin','sub-admin'],
    'posts'     => ['admin','sub-admin','moderator'],
    'comments'  => ['admin','sub-admin','moderator'],
    'chat'      => ['admin','sub-admin','moderator'],
    'settings'  => ['admin']
];

// Security: if page not allowed or role not permitted, fallback to dashboard
if (!array_key_exists($page, $allowed_pages) || 
    !in_array($_SESSION['user']['role_slug'], $allowed_pages[$page])) {
    $page = 'dashboard';
}

$pageTitle = ucfirst($page);

include '../includes/header.php';
include '../includes/sidebar.php';

// Include the requested page
include __DIR__ . "/{$page}.php";

include '../includes/footer.php';

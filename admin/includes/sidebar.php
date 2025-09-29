<?php
// admin/includes/sidebar.php
require_once __DIR__ . '/db.php';

$current = $_GET['pages'] ?? 'dashboard'; // ✅ match index.php

// Defensive: if no session user present, show a minimal sidebar
$userRoleId = $_SESSION['user']['role_id'] ?? null;
$permissions = [];
if ($userRoleId) {
    $stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$userRoleId]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$menuItems = [
    'dashboard' => ['title' => 'Dashboard', 'icon' => 'bx bxs-dashboard', 'url' => 'index.php?pages=dashboard'],
    'users'     => ['title' => 'Manage Users', 'icon' => 'bx bxs-user-detail', 'url' => 'index.php?pages=users'],
    'roles'     => ['title' => 'Roles Management', 'icon' => 'bx bxs-shield', 'url' => 'index.php?pages=roles'],
    'settings'  => ['title' => 'Site Settings', 'icon' => 'bx bxs-cog', 'url' => 'index.php?pages=settings'],
    'courses'   => ['title' => 'Courses', 'icon' => 'bx bxs-book', 'url' => 'index.php?pages=courses'],
    'tutors'    => ['title' => 'Tutors', 'icon' => 'bx bxs-chalkboard', 'url' => 'index.php?pages=tutors'],
    'students'  => ['title' => 'Students', 'icon' => 'bx bxs-graduation', 'url' => 'index.php?pages=students'],
        'payments'  => ['title' => 'Payments', 'icon' => 'bx bxs-credit-card', 'url' => 'index.php?pages=payments'],
        'icons'     => ['title' => 'Icons', 'icon' => 'bx bx-image', 'url' => 'index.php?pages=icons'],
    'post'      => ['title' => 'News / Blog', 'icon' => 'bx bxs-news', 'url' => 'index.php?pages=post'],
    'comments'  => ['title' => 'Comments', 'icon' => 'bx bxs-comment-detail', 'url' => 'index.php?pages=comments'],
    'chat'      => ['title' => 'Chat Support', 'icon' => 'bx bxs-message-dots', 'url' => 'index.php?pages=chat'],
    'audit_logs' => ['title' => 'Audit Logs', 'icon' => 'bx bxs-report', 'url' => 'index.php?pages=audit_logs'],
];
?>

<aside class="admin-sidebar">
    <a href="index.php?pages=dashboard" class="sidebar-logo" style="text-decoration:none;">
        <img src="../assets/img/hq-logo.jpeg" alt="Academy Logo" class="brand-logo">
        <h3 style="color:#fff;">HIGH Q SOLID ACADEMY</h3>
        <small style="color:#bbb;"><?= htmlspecialchars($_SESSION['user']['role_name'] ?? ''); ?></small>
    </a>
    <nav class="sidebar-nav">
        <ul>
            <?php foreach ($menuItems as $slug => $item): ?>
                <?php if (in_array($slug, $permissions)): ?>
                    <li><a href="<?= $item['url']; ?>" class="<?= $current === $slug ? 'active' : ''; ?>">
                            <i class='<?= $item['icon']; ?>'></i> <?= $item['title']; ?>
                        </a></li>
                <?php endif; ?>
            <?php endforeach; ?>
            <li><a href="../logout.php" class="logout-link"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </nav>
</aside>
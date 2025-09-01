<?php
// admin/includes/sidebar.php
require __DIR__ . '/db.php';

$current = $_GET['pages'] ?? 'dashboard'; // ✅ match index.php
$userRoleId = $_SESSION['user']['role_id'];

$stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id = ?");
$stmt->execute([$userRoleId]);
$permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

$menuItems = [
    'dashboard' => ['title' => 'Dashboard', 'icon' => 'bx bxs-dashboard', 'url' => 'index.php?pages=dashboard'],
    'users'     => ['title' => 'Manage Users', 'icon' => 'bx bxs-user-detail', 'url' => 'index.php?pages=users'],
    'roles'     => ['title' => 'Roles', 'icon' => 'bx bxs-shield', 'url' => 'index.php?pages=roles'],
    'settings'  => ['title' => 'Site Settings', 'icon' => 'bx bxs-cog', 'url' => 'index.php?pages=settings'],
    'courses'   => ['title' => 'Courses', 'icon' => 'bx bxs-book', 'url' => 'index.php?pages=courses'],
    'tutors'    => ['title' => 'Tutors', 'icon' => 'bx bxs-chalkboard', 'url' => 'index.php?pages=tutors'],
    'students'  => ['title' => 'Students', 'icon' => 'bx bxs-graduation', 'url' => 'index.php?pages=students'],
    'payments'  => ['title' => 'Payments', 'icon' => 'bx bxs-credit-card', 'url' => 'index.php?pages=payments'],
    'post'      => ['title' => 'News / Blog', 'icon' => 'bx bxs-news', 'url' => 'index.php?pages=post'],
    'comments'  => ['title' => 'Comments', 'icon' => 'bx bxs-comment-detail', 'url' => 'index.php?pages=comments'],
    'chat'      => ['title' => 'Chat Support', 'icon' => 'bx bxs-message-dots', 'url' => 'index.php?pages=chat'],
];
?>

<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <img src="../public/assets/images/logo.png" alt="Academy Logo">
        <h3>HIGH Q SOLID ACADEMY</h3>
        <small><?= htmlspecialchars($_SESSION['user']['role_name']); ?></small>
    </div>
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
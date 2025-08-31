<?php
// admin/includes/sidebar.php
require __DIR__ . '/db.php';

// Current page
$current = $_GET['page'] ?? 'dashboard';
$userRoleId = $_SESSION['user']['role_id'];

// Fetch allowed menu slugs for this role
$stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id = ?");
$stmt->execute([$userRoleId]);
$permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Define all menu items
$menuItems = [
    'dashboard' => ['title' => 'Dashboard', 'icon' => 'bx bxs-dashboard', 'url' => 'index.php?page=dashboard'],
    'users'     => ['title' => 'Manage Users', 'icon' => 'bx bxs-user-detail', 'url' => 'index.php?page=users'],
    'roles'     => ['title' => 'Roles', 'icon' => 'bx bxs-shield', 'url' => 'index.php?page=roles'],
    'settings'  => ['title' => 'Site Settings', 'icon' => 'bx bxs-cog', 'url' => 'index.php?page=settings'],
    'courses'   => ['title' => 'Courses', 'icon' => 'bx bxs-book', 'url' => 'index.php?page=courses'],
    'tutors'    => ['title' => 'Tutors', 'icon' => 'bx bxs-chalkboard', 'url' => 'index.php?page=tutors'],
    'students'  => ['title' => 'Students', 'icon' => 'bx bxs-graduation', 'url' => 'index.php?page=students'],
    'payments'  => ['title' => 'Payments', 'icon' => 'bx bxs-credit-card', 'url' => 'index.php?page=payments'],
    'posts'     => ['title' => 'News / Blog', 'icon' => 'bx bxs-news', 'url' => 'index.php?page=posts'],
    'comments'  => ['title' => 'Comments', 'icon' => 'bx bxs-comment-detail', 'url' => 'index.php?page=comments'],
    'chat'      => ['title' => 'Chat Support', 'icon' => 'bx bxs-message-dots', 'url' => 'index.php?page=chat'],
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
                    <li>
                        <a href="<?= $item['url'] ?>" class="<?= $current === $slug ? 'active' : '' ?>">
                            <i class='<?= $item['icon'] ?>'></i> <?= $item['title'] ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
            <li>
                <a href="../logout.php" class="logout-link">
                    <i class='bx bx-log-out'></i> Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>

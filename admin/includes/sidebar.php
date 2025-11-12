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
// Load menu items from centralized config so sidebar and Roles UI stay in sync
$menuItems = require __DIR__ . '/menu.php';
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
                <?php
                    // Show item if role_permissions contains the menu slug
                    $show = in_array($slug, $permissions);
                    // Also show audit_logs to users who have the general 'settings' permission (common admin role)
                    if (!$show && $slug === 'audit_logs' && in_array('settings', $permissions)) $show = true;
                    // Ensure the Create Payment Link menu is shown to users with 'payments' permission
                    if (!$show && $slug === 'create_payment_link' && in_array('payments', $permissions)) $show = true;
                    // Show appointments to users with 'settings' or 'students' permission
                    if (!$show && $slug === 'appointments' && (in_array('settings', $permissions) || in_array('students', $permissions))) $show = true;
                ?>
                <?php if ($show): ?>
                    <li><a href="<?= $item['url']; ?>" class="<?= $current === $slug ? 'active' : ''; ?>">
                            <i class='<?= $item['icon']; ?>'></i> <?= $item['title']; ?>
                        </a></li>
                <?php endif; ?>
            <?php endforeach; ?>
            <li><a href="../logout.php" class="logout-link"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </nav>
</aside>
<?php
// admin/includes/sidebar.php
?>
<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <img src="../public/assets/images/logo.png" alt="Academy Logo">
        <h3>HIGH Q SOLID ACADEMY</h3>
        <small><?= htmlspecialchars($_SESSION['user']['role_name']); ?></small>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <!-- Dashboard: All roles -->
            <li><a href="index.php?page=dashboard">📊 Dashboard</a></li>

            <!-- Admin only -->
            <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
                <li><a href="index.php?page=users">👥 Manage Users</a></li>
                <li><a href="index.php?page=roles">🛡 Roles</a></li>
                <li><a href="index.php?page=settings">⚙ Site Settings</a></li>
            <?php endif; ?>

            <!-- Admin + Sub-Admin -->
            <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])): ?>
                <li><a href="index.php?page=courses">📚 Courses / Programs</a></li>
                <li><a href="index.php?page=tutors">👨‍🏫 Tutors</a></li>
                <li><a href="index.php?page=students">🎓 Students</a></li>
                <li><a href="index.php?page=payments">💳 Payments</a></li>
            <?php endif; ?>

            <!-- Admin + Sub-Admin + Moderator -->
            <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin','moderator'])): ?>
                <li><a href="index.php?page=posts">📰 News / Blog</a></li>
                <li><a href="index.php?page=comments">💬 Comments</a></li>
                <li><a href="index.php?page=chat">💻 Chat Support</a></li>
            <?php endif; ?>

            <!-- Logout -->
            <li><a href="../logout.php" class="logout-link">🚪 Logout</a></li>
        </ul>
    </nav>
</aside>

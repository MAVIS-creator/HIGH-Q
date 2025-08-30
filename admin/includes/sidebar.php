<!-- Boxicons CDN -->
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<aside class="admin-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class='bx bxs-graduation'></i>
        <h3>HIGH Q SOLID ACADEMY</h3>
        <small><?= htmlspecialchars($_SESSION['user']['role_name']); ?></small>
        <i class='bx bx-x close-btn' id="closeSidebar"></i>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li><a href="index.php?page=dashboard"><i class='bx bxs-dashboard'></i> Dashboard</a></li>

            <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
                <li><a href="index.php?page=users"><i class='bx bxs-user-detail'></i> Manage Users</a></li>
                <li><a href="index.php?page=roles"><i class='bx bxs-shield'></i> Roles</a></li>
                <li><a href="index.php?page=settings"><i class='bx bxs-cog'></i> Site Settings</a></li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])): ?>
                <li><a href="index.php?page=courses"><i class='bx bxs-book'></i> Courses</a></li>
                <li><a href="index.php?page=tutors"><i class='bx bxs-chalkboard'></i> Tutors</a></li>
                <li><a href="index.php?page=students"><i class='bx bxs-graduation'></i> Students</a></li>
                <li><a href="index.php?page=payments"><i class='bx bxs-credit-card'></i> Payments</a></li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin','moderator'])): ?>
                <li><a href="index.php?page=posts"><i class='bx bxs-news'></i> News / Blog</a></li>
                <li><a href="index.php?page=comments"><i class='bx bxs-comment-detail'></i> Comments</a></li>
                <li><a href="index.php?page=chat"><i class='bx bxs-message-dots'></i> Chat Support</a></li>
            <?php endif; ?>

            <li><a href="../logout.php" class="logout-link"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </nav>
</aside>

<!-- Overlay for outside click -->
<div id="sidebarOverlay"></div>

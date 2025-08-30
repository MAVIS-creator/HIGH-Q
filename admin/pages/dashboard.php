<?php
// admin/pages/dashboard.php
?>
<div class="dashboard">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user']['name']); ?>!</h1>
    <p class="role-label">Role: <?= htmlspecialchars($_SESSION['user']['role_name']); ?></p>

    <div class="dashboard-widgets">
        <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
            <div class="widget-card red">
                <i class='bx bxs-user-detail'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="widget-card yellow">
                <i class='bx bxs-cog'></i>
                <div>
                    <h3>Settings</h3>
                    <p>Manage Site</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])): ?>
            <div class="widget-card black">
                <i class='bx bxs-book'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(); ?></h3>
                    <p>Courses</p>
                </div>
            </div>
            <div class="widget-card red">
                <i class='bx bxs-graduation'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(); ?></h3>
                    <p>Students</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin','moderator'])): ?>
            <div class="widget-card yellow">
                <i class='bx bxs-news'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(); ?></h3>
                    <p>Posts</p>
                </div>
            </div>
            <div class="widget-card black">
                <i class='bx bxs-comment-detail'></i>
                <div>
                    <h3><?= $pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(); ?></h3>
                    <p>Pending Comments</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

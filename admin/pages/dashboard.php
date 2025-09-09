<?php
// admin/pages/dashboard.php

// Fetch allowed menus for current role
$userRoleId = $_SESSION['user']['role_id'];
$stmt = $pdo->prepare("SELECT menu_slug FROM role_permissions WHERE role_id = ?");
$stmt->execute([$userRoleId]);
$permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="dashboard">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user']['name']); ?>!</h1>
    <p class="role-label">Role: <?= htmlspecialchars($_SESSION['user']['role_name']); ?></p>

    <div class="dashboard-widgets">
        <div class="widget-card yellow">
            <i class='bx bxs-graduation'></i>
            <div>
                <h3><?= $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(); ?></h3>
                <p>Student applications</p>
            </div>
        </div>
        <div class="widget-card red">
            <i class='bx bxs-chalkboard'></i>
            <div>
                <h3><?= $pdo->query("SELECT COUNT(*) FROM tutors WHERE is_active=1")->fetchColumn(); ?></h3>
                <p>Currently teaching</p>
            </div>
        </div>
        <div class="widget-card yellow">
            <i class='bx bxs-book'></i>
            <div>
                <h3><?= $pdo->query("SELECT COUNT(*) FROM programs")->fetchColumn(); ?></h3>
                <p>Available programs</p>
            </div>
        </div>
        <div class="widget-card yellow">
            <i class='bx bxs-news'></i>
            <div>
                <h3><?= $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(); ?></h3>
                <p>Published articles</p>
            </div>
        </div>
        <div class="widget-card black">
            <i class='bx bxs-comment-detail'></i>
            <div>
                <h3><?= $pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(); ?></h3>
                <p>Awaiting approval</p>
            </div>
        </div>
        <div class="widget-card yellow">
            <i class='bx bxs-shield'></i>
            <div>
                <h3><?= $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=0")->fetchColumn(); ?></h3>
                <p>User approvals needed</p>
            </div>
        </div>
        <div class="widget-card red" style="grid-column: span 2;">
            <i class='bx bxs-error'></i>
            <div>
                <h3>All caught up! 🦾</h3>
                <p>Attention Required</p>
            </div>
        </div>
        <div class="widget-card black" style="grid-column: span 2;">
            <i class='bx bxs-bar-chart-alt-2'></i>
            <div>
                <h3>System Status</h3>
                <p>
                    Database <span style="color:green;">✓ Online</span><br>
                    Website <span style="color:green;">✓ Active</span><br>
                    Admin Panel <span style="color:green;">✓ Running</span>
                </p>
            </div>
        </div>
    </div>
</div>

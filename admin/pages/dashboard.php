<?php
// admin/pages/dashboard.php
?>
<div class="dashboard">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user']['name']); ?>!</h1>
    <p>Role: <?= htmlspecialchars($_SESSION['user']['role_name']); ?></p>

    <div class="widgets">
        <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
            <div class="widget">👥 Users: <?= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></div>
            <div class="widget">⚙ Settings Access</div>
        <?php endif; ?>

        <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])): ?>
            <div class="widget">📚 Courses: <?= $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(); ?></div>
            <div class="widget">🎓 Students: <?= $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(); ?></div>
        <?php endif; ?>

        <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin','moderator'])): ?>
            <div class="widget">💬 Pending Comments: <?= $pdo->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn(); ?></div>
            <div class="widget">📰 Posts: <?= $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(); ?></div>
        <?php endif; ?>
    </div>
</div>

<?php
// admin/includes/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= isset($pageTitle) ? $pageTitle : 'Admin Panel'; ?> - HIGH Q SOLID ACADEMY</title>
<link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
<header class="admin-header">
    <div class="logo">HIGH Q SOLID ACADEMY</div>
    <nav>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
                <li><a href="users.php">Users</a></li>
                <li><a href="settings.php">Settings</a></li>
            <?php endif; ?>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>
</header>
<main class="admin-main">

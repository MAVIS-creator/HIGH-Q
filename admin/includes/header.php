<?php
// admin/includes/header.php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle : 'Admin Panel'; ?> - HIGH Q SOLID ACADEMY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <header class="admin-header">
        <div class="header-left">
            <!-- Hamburger Menu Button -->
            <i class='bx bx-menu' id="menuToggle"></i>
            <span class="header-title"><?= isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></span>
        </div>
        <div class="header-right">
            <span>Welcome, <?= htmlspecialchars($_SESSION['user']['name']); ?></span>
            <div class="header-avatar">
                <img src="<?= $_SESSION['user']['avatar'] ?? '../public/assets/images/avatar-placeholder.png'; ?>" alt="Avatar">
            </div>
        </div>
    </header>
    <main class="admin-main">

        <script>
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.querySelector('.admin-sidebar');
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                document.querySelector('.admin-main').classList.toggle('expanded');
            });
        </script>
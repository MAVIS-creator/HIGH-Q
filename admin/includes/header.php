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
    <?php
    // Build a reliable path to the admin assets directory by locating the 'admin' segment
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $parts = explode('/', trim($script, '/'));
    $adminBase = '';
    $idx = array_search('admin', $parts, true);
    if ($idx !== false) {
        $adminBase = '/' . implode('/', array_slice($parts, 0, $idx + 1));
    } else {
        // fallback to dirname
        $adminBase = rtrim(dirname($script), '/');
        if ($adminBase === '') $adminBase = '/';
    }
    ?>
    <link rel="stylesheet" href="<?= $adminBase ?>/assets/css/admin.css">
    <?php if (!empty($pageCss)) echo $pageCss; ?>
</head>

<body>
    <header class="admin-header">
        <div class="header-left">
            <!-- Hamburger Menu Button -->
            <i class='bx bx-menu' id="menuToggle"></i>
            <div>
                <span class="header-title"><?= isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></span>
                <?php if (!empty($pageSubtitle)): ?>
                    <p class="header-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-right">
            <?php if (empty($_SESSION['user'])): ?>
                <a href="../signup.php" class="header-cta">Sign up</a>
            <?php else: ?>
                <div class="header-avatar">
                    <img src="<?= $_SESSION['user']['avatar'] ?? '../public/assets/images/avatar-placeholder.png'; ?>" alt="Avatar">
                </div>
            <?php endif; ?>
        </div>
    </header>
    <?php
    // Render flash messages (if any)
    if (function_exists('getFlash')) {
        $flash = getFlash();
        if (!empty($flash)) {
            $type = $flash['type'] ?? 'info';
            $msg  = $flash['message'] ?? '';
            echo "<div class=\"admin-flash admin-flash-{$type}\">" . htmlspecialchars($msg) . "</div>";
        }
    }
    ?>
    <main class="admin-main">
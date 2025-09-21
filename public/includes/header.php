<?php
// public/includes/header.php - lightweight public header
// Attempt to load site settings (contact phone) from database
$contact_phone = '0807 208 8794';
// include DB connection if available
if (file_exists(__DIR__ . '/../config/db.php')) {
    try {
        require_once __DIR__ . '/../config/db.php';
        if (isset($pdo)) {
            $stmt = $pdo->query("SELECT contact_phone FROM site_settings LIMIT 1");
            $row = $stmt->fetch();
            if ($row && !empty($row['contact_phone'])) {
                $contact_phone = $row['contact_phone'];
            }
        }
    } catch (Throwable $e) {
        // ignore DB errors and fall back to default
    }
}
?>
<!DOCTYPE html>
<html lang="en">

    <head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle : 'HIGH Q SOLID ACADEMY'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/public.css">
    <link rel="shortcut icon" href="/HIGH-Q/admin/assets/img/favicon.ico" type="image/x-icon">
</head>

<body>
    <div class="topbar">
        <div class="topbar-inner">
            <div class="contact-left">📞 0807 208 8794 &nbsp; | &nbsp; ✉️ info@hqacademy.com</div>
            <div class="tagline">"Always Ahead of Others"</div>
        </div>
    </div>

    <header class="site-header">
        <div class="site-header-inner">
            <a href="/" class="site-logo">
                <img src="/HIGH-Q/admin/assets/img/hq%20logo.jpeg" alt="HIGH Q" />
                <span class="site-name">HIGH Q SOLID ACADEMY</span>
            </a>

            <nav class="main-nav">
                <a href="/" class="nav-link">Home</a>
                <a href="programs.php" class="nav-link">About Us</a>
                <a href="programs.php" class="nav-link">Programs</a>
                <a href="register.php" class="nav-link cta">Register Now</a>
            </nav>
        </div>
    </header>

    <main class="public-main">
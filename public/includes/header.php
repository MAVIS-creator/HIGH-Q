<?php
// public/includes/header.php - lightweight public header
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? $pageTitle : 'HIGH Q SOLID ACADEMY'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/courses.css">
    <link rel="shortcut icon" href="/HIGH-Q/public/assets/images/hq-favicon.ico" type="image/x-icon">
</head>

<body>
    <header class="site-header" style="background:#fff;border-bottom:1px solid #eee;padding:0.6rem 1rem;">
        <div style="display:flex;align-items:center;gap:1rem;">
            <a href="/" class="site-logo" style="font-weight:700;color:#d62828;text-decoration:none;display:flex;align-items:center;gap:0.5rem;">
                <img src="/HIGH-Q/public/assets/images/hq-logo.jpeg" alt="HIGH Q" style="height:36px;object-fit:cover;border-radius:6px;">
            </a>
            <nav style="margin-left:1rem;">
                <a href="/" style="margin-right:0.6rem;color:#333;text-decoration:none;">Home</a>
                <a href="programs.php" style="margin-right:0.6rem;color:#333;text-decoration:none;">Programs</a>
                <a href="tutors.php" style="color:#333;text-decoration:none;">Tutors</a>
            </nav>
        </div>
    </header>
    <main class="public-main">
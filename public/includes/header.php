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
    <link rel="stylesheet" href="../assets/css/public.css">
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
</head>

<body>
<header>
  <!-- Top bar -->
  <div class="top-bar">
    <div class="container">
      <span>📞 <?= htmlentities($contact_phone) ?></span>
      <span>✉️ info@hqacademy.com</span>
      <span class="motto">"Always Ahead of Others"</span>
    </div>
  </div>

  <!-- Main nav -->
  <div class="main-header">
    <div class="container">
      <!-- Logo + Name -->
      <div class="logo">
        <img src="../assets/images/hq-logo.jpeg" alt="HQ Logo">
        <div>
          <h1>HIGH Q SOLID ACADEMY</h1>
          <small>Limited</small>
        </div>
      </div>

      <!-- Navigation -->
      <nav>
        <a href="home.php" class="active">Home</a>
        <a href="about.php">About Us</a>
        <a href="programs.php">Programs</a>
        <a href="register.php">Admission</a>
        <a href="contact.php">Contact</a>
      </nav>

      <!-- Button -->
      <a href="#" class="btn">Register Now</a>
    </div>
  </div>
</header>

    <main class="public-main">
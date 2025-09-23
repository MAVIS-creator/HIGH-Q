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
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="./assets/css/public.css">
  <link rel="shortcut icon" href="./assets/images/favicon.ico" type="image/x-icon">
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
          <img src="./assets/images/hq-logo.jpeg" alt="HQ Logo" class="brand-logo" href="index.php">
          <div>
            <h1>HIGH Q SOLID ACADEMY</h1>
            <small>Limited</small>
          </div>
        </div>


        <!-- Navigation -->
        <nav>
          <a href="index.php" class="active">Home</a>
          <a href="about.php">About Us</a>

          <!-- Combined dropdown: a single, general label that reveals Programs and News on click -->
          <div class="nav-dropdown">
            <a href="#" class="drop-toggle">Programs & News</a>
            <div class="nav-dropdown-content">
              <a href="programs.php">Programs</a>
              <a href="news.php">News</a>
            </div>
          </div>

          <a href="register.php">Admission</a>
          <a href="contact.php">Contact</a>
        </nav>

        <!-- Button -->
        <a href="register.php" class="btn">Register Now</a>
      </div>
    </div>
  </header>

  <script>
    // Toggle nav dropdown open/close on click and close when clicking outside
    (function(){
      document.addEventListener('DOMContentLoaded', function(){
        var dropToggles = document.querySelectorAll('.nav-dropdown .drop-toggle');
        dropToggles.forEach(function(toggle){
          toggle.addEventListener('click', function(e){
            e.preventDefault();
            var parent = toggle.closest('.nav-dropdown');
            // toggle open on this parent, close others
            document.querySelectorAll('.nav-dropdown.open').forEach(function(n){ if(n !== parent) n.classList.remove('open'); });
            parent.classList.toggle('open');
          });
        });

        // close dropdowns on outside click
        document.addEventListener('click', function(e){
          if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown.open').forEach(function(n){ n.classList.remove('open'); });
          }
        });
      });
    })();
  </script>

  <main class="public-main">
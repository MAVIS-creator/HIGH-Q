<?php
// Admin routing - forward ?pages parameter requests to pages/index.php
if (isset($_GET['pages'])) {
    // Include the actual admin router (don't redirect, just include it)
    include __DIR__ . '/pages/index.php';
    exit;
}

// If no page parameter, show landing page
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="Admin access panel for HIGH Q SOLID ACADEMY — manage users, courses, admissions, and content.">
    <title>HIGH Q SOLID ACADEMY — Admin Access</title>

    <!-- Favicon -->
        <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">

    <!-- Boxicons & FontAwesome -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/admin.css">
    <!-- Landing overrides: ensure landing gradient and single logo (override admin.css) -->
    <style>
        body.landing-page { 
            background: linear-gradient(135deg, #ffd600 0%, #ffe566 40%, #ff9a4d 65%, #ff4b2b 100%) !important;
            display: flex; justify-content: center; align-items: flex-start;
        }
        /* Remove admin.css background/padding that can wrap the logo image */
        body.landing-page .logo-circle { background: transparent !important; }
        body.landing-page .brand-logo { background: none !important; padding: 0 !important; box-shadow: none !important; }
        /* Ensure the logo image fits the circle and no duplicate styling appears */
        body.landing-page .logo-circle .brand-logo { width:64px; height:64px; object-fit:cover; border-radius:50%; display:block; }
        /* Accent colors for title/subtitle on gradient */
        body.landing-page .site-title { color: #0a0a0a; }
        body.landing-page .site-sub { color: #e63946; }
    </style>
</head>

<body  class="landing-page">
    <div class="container">

        <!-- Header -->
        <header class="site-header" role="banner">
            <div class="logo-circle" aria-hidden="true">
                    <img src="./assets/img/hq-logo.jpeg" alt="HQ Logo" class="brand-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="site-title">HIGH Q SOLID ACADEMY</h1>
            <p class="site-sub">LIMITED</p>
            <p class="site-tagline">Always Ahead of Others</p>
        </header>

        <!-- Card -->
        <div class="card-wrap">
            <section class="card" aria-labelledby="admin-title">
                <div class="card-header">
                    <div class="shield-circle" aria-hidden="true">
                        <i class='bx bx-shield'></i>
                    </div>
                    <h2 id="admin-title" class="card-title">Admin Panel Access</h2>
                    <p class="card-desc">Manage your academy with our comprehensive admin dashboard</p>
                </div>

                <!-- Features -->
                <div class="features" role="list">
                    <div class="feature" role="listitem">
                        <i class="bx bx-group feat-icon-red"></i>
                        <h4>User Management</h4>
                        <p>Manage staff and students</p>
                    </div>

                    <div class="feature" role="listitem">
                        <i class="bx bx-book-open feat-icon-red"></i>
                        <h4>Content Management</h4>
                        <p>Courses, news & tutorials</p>
                    </div>

                    <div class="feature" role="listitem">
                        <i class="fas fa-graduation-cap feat-icon-red"></i>
                        <h4>Admissions</h4>
                        <p>Student applications</p>
                    </div>
                </div>

                <!-- Buttons -->
                    <!-- Desktop Mode Recommendation -->
                    <div class="alert alert-warning mb-3" style="text-align:center;">
                        <strong>Highly recommended:</strong> For best experience, <span style="color:#e63946">enable Desktop Mode</span> on your mobile browser.<br>
                        Some admin features are optimized for desktop screens.
                    </div>
                    <div class="btn-row">
                        <a href="login.php" class="btn btn-primary">Access Admin Panel</a>
                        <a href="signup.php" class="btn btn-outline">Register New Admin</a>
                    </div>


                <!-- Role box -->
                <div class="role-box">
                    <strong>Role-Based Access Control</strong>
                    <ul>
                        <li><strong>Admin:</strong> Full system access and management</li>
                        <li><strong>Sub-Admin:</strong> Content and user management</li>
                        <li><strong>Moderator:</strong> Comment and content moderation</li>
                    </ul>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="page-footer">
            <p>© <?php echo date("Y"); ?> HIGH Q SOLID ACADEMY LIMITED. All rights reserved.</p>
            <p>Empowering students with quality education since 2018</p>
        </footer>
    </div>
</body>

</html>
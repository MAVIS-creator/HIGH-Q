<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="Admin access panel for HIGH Q SOLID ACADEMY — manage users, courses, admissions, and content.">
    <title>HIGH Q SOLID ACADEMY — Admin Access</title>

    <!-- Favicon -->
        <link rel="shortcut icon" href="/HIGH-Q/public/assets/images/hq-favicon.ico" type="image/x-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">

    <!-- Boxicons & FontAwesome -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container">

        <!-- Header -->
        <header class="site-header" role="banner">
            <div class="logo-circle" aria-hidden="true">
                    <img src="../public/assets/images/hq-logo.jpeg" alt="HQ Logo" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
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
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
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

        <!-- Dashboard (show real stats if logged in) -->
        <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
        <?php if (!empty($_SESSION['user'])): ?>
            <?php
            // lightweight counts; wrap in try for DB availability
            try {
                require_once __DIR__ . '/includes/db.php';
                $totalUsers = (int)($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() ?: 0);
                $totalStudents = (int)($pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.slug='student'")->execute() ? $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.slug='student'")->fetchColumn() : 0);
                $pendingComments = (int)($pdo->query('SELECT COUNT(*) FROM comments WHERE is_approved = 0')->fetchColumn() ?: 0);
                $pendingPayments = (int)($pdo->query("SELECT COUNT(*) FROM payments WHERE status IN ('pending','uploaded')")->fetchColumn() ?: 0);
            } catch (Exception $e) {
                $totalUsers = $totalStudents = $pendingComments = $pendingPayments = 0;
            }
            ?>

            <div class="card-wrap">
                <section class="card" aria-labelledby="admin-title">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <h2 id="admin-title" class="card-title">Dashboard</h2>
                            <p class="card-desc">Overview and quick stats for your site</p>
                        </div>
                        <div style="text-align:right;color:#666;font-size:0.95rem;">Role: <?= htmlspecialchars($_SESSION['user']['role_slug'] ?? ($_SESSION['user']['role_name'] ?? 'Admin')) ?></div>
                    </div>

                    <div class="dash-grid">
                        <div class="dash-card" onclick="location.href='index.php?pages=users'">
                            <div style="display:flex;align-items:center;width:100%"><span class="dash-icon"><i class='bx bx-user'></i></span><h3><?= $totalUsers ?></h3></div>
                            <p>Total Users</p>
                        </div>

                        <div class="dash-card" onclick="location.href='index.php?pages=settings'">
                            <div style="display:flex;align-items:center;width:100%"><span class="dash-icon"><i class='bx bx-cog'></i></span><h3>Settings</h3></div>
                            <p>Manage Site</p>
                        </div>

                        <div class="dash-card" onclick="location.href='index.php?pages=courses'">
                            <div style="display:flex;align-items:center;width:100%"><span class="dash-icon"><i class='bx bx-book'></i></span><h3>0</h3></div>
                            <p>Courses</p>
                        </div>

                        <div class="dash-card" onclick="location.href='index.php?pages=students'">
                            <div style="display:flex;align-items:center;width:100%"><span class="dash-icon"><i class='bx bx-graduation'></i></span><h3><?= $totalStudents ?></h3></div>
                            <p>Students</p>
                        </div>

                        <div class="dash-card" onclick="location.href='index.php?pages=comments'">
                            <div style="display:flex;align-items:center;width:100%"><span class="dash-icon"><i class='bx bx-comment'></i></span><h3><?= $pendingComments ?></h3></div>
                            <p>Pending Comments</p>
                        </div>

                        <div class="dash-card" onclick="location.href='index.php?pages=payments'">
                            <div style="display:flex;align-items:center;width:100%"><span class="dash-icon"><i class='bx bx-credit-card'></i></span><h3><?= $pendingPayments ?></h3></div>
                            <p>Pending Payments</p>
                        </div>

                        <div class="dash-card" onclick="location.href='index.php?pages=settings'">
                            <div style="display:flex;align-items:center;width:100%"><span class="dash-icon"><i class='bx bx-stats'></i></span><h3>System Status</h3></div>
                            <p>
                                <span style="display:block;color:green">Database: Online</span>
                                <span style="display:block;color:green">Website: Online (200)</span>
                                <span style="display:block;color:red">Admin Panel: Reachable</span>
                            </p>
                        </div>
                    </div>

                </section>
            </div>
        <?php else: ?>
            <div class="card-wrap">
                <section class="card" aria-labelledby="admin-title">
                    <div class="card-header">
                        <div class="shield-circle" aria-hidden="true">
                            <i class='bx bx-shield'></i>
                        </div>
                        <h2 id="admin-title" class="card-title">Admin Panel Access</h2>
                        <p class="card-desc">Manage your academy with our comprehensive admin dashboard</p>
                    </div>

                    <!-- Features (static) -->
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
        <?php endif; ?>

        <!-- Footer -->
        <footer class="page-footer">
            <p>© <?php echo date("Y"); ?> HIGH Q SOLID ACADEMY LIMITED. All rights reserved.</p>
            <p>Empowering students with quality education since 2018</p>
        </footer>
    </div>
</body>

</html>
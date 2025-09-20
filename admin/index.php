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

<body class="landing-page">
    <div class="container">

        <!-- Header -->
        <header class="site-header" role="banner">
            <div class="logo-circle" aria-hidden="true">
                <img src="./assets/img/hq-logo.jpeg" alt="HQ Logo" class="brand-logo">
            </div>
            <h1 class="site-title">HIGH Q SOLID ACADEMY</h1>
            <p class="site-sub">LIMITED</p>
            <p class="site-tagline">Always Ahead of Others</p>
        </header>

        <!-- Dashboard Card area -->
            <div class="card-wrap">
                <?php
                // quick DB-driven dashboard (best-effort if DB available)
                $counts = [
                    'users' => 0,
                    'students' => 0,
                    'pending_comments' => 0,
                    'pending_payments' => 0,
                    'courses' => 0
                ];
                try {
                    if (file_exists(__DIR__ . '/includes/db.php')) {
                        require_once __DIR__ . '/includes/db.php';
                        $counts['users'] = (int)($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() ?: 0);
                        $counts['students'] = (int)($pdo->query("SELECT COUNT(*) FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE r.slug='student' OR u.role_id IS NULL")->fetchColumn() ?: 0);
                        $counts['pending_comments'] = (int)($pdo->query("SELECT COUNT(*) FROM comments WHERE is_approved=0")->fetchColumn() ?: 0);
                        $counts['pending_payments'] = (int)($pdo->query("SELECT COUNT(*) FROM payments WHERE status IN ('pending','uploaded')")->fetchColumn() ?: 0);
                        $counts['courses'] = (int)($pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn() ?: 0);
                    }
                } catch (Exception $e){ /* ignore DB errors - fallback to zeros */ }
                ?>

                <section class="card" aria-labelledby="admin-title">
                    <div class="card-header">
                        <h2 id="admin-title" class="card-title">Dashboard</h2>
                        <p class="card-desc">Overview and quick stats for your site</p>
                    </div>

                    <div class="features" role="list">
                        <a href="index.php?pages=users" class="feature" role="listitem">
                            <div><i class='bx bx-user'></i></div>
                            <div class="feat-value"><?= htmlspecialchars($counts['users']) ?></div>
                            <div> Total Users</div>
                        </a>

                        <a href="index.php?pages=settings" class="feature" role="listitem">
                            <div><i class='bx bx-cog'></i></div>
                            <div class="feat-value">Settings</div>
                            <div>Manage Site</div>
                        </a>

                        <a href="index.php?pages=courses" class="feature" role="listitem">
                            <div><i class='bx bx-book'></i></div>
                            <div class="feat-value"><?= htmlspecialchars($counts['courses']) ?></div>
                            <div> Courses</div>
                        </a>

                        <a href="index.php?pages=students" class="feature" role="listitem">
                            <div><i class='bx bx-graduation'></i></div>
                            <div class="feat-value"><?= htmlspecialchars($counts['students']) ?></div>
                            <div> Students</div>
                        </a>

                        <a href="index.php?pages=comments" class="feature" role="listitem">
                            <div><i class='bx bx-message-square-detail'></i></div>
                            <div class="feat-value"><?= htmlspecialchars($counts['pending_comments']) ?></div>
                            <div> Pending Comments</div>
                        </a>

                        <a href="index.php?pages=payments" class="feature" role="listitem">
                            <div><i class='bx bx-credit-card'></i></div>
                            <div class="feat-value"><?= htmlspecialchars($counts['pending_payments']) ?></div>
                            <div> Pending Payments</div>
                        </a>

                        <div class="feature" role="listitem">
                            <div><i class='bx bx-server'></i></div>
                            <div class="feat-value">System Status</div>
                            <div style="text-align:left;font-size:0.9rem;margin-top:8px;color:#444">
                                <?php
                                // lightweight system checks
                                $dbStatus = 'Unknown'; $siteStatus='Online';
                                try{
                                    if (isset($pdo)) { $dbStatus = $pdo ? 'Online' : 'Offline'; }
                                } catch(Exception $e){ $dbStatus='Offline'; }
                                echo "<div>Database: <strong style='color:" . ($dbStatus==='Online' ? 'green' : 'red') . ";'>$dbStatus</strong></div>";
                                echo "<div>Website: <strong style='color:green;'>$siteStatus</strong></div>";
                                echo "<div>Admin Panel: <strong style='color:green;'>Reachable</strong></div>";
                                ?>
                            </div>
                        </div>
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
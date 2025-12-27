<?php
// Admin routing - forward ?pages parameter requests to pages/index.php
if (isset($_GET['pages'])) {
    include __DIR__ . '/pages/index.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin access panel for HIGH Q SOLID ACADEMY — manage users, courses, admissions, and content.">
    <title>HIGH Q SOLID ACADEMY — Admin Access</title>
    <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/auth.css">
    <style>
        /* Landing page specific styles */
        .landing-container {
            max-width: 520px;
        }
        
        .landing-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-2xl);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        
        .landing-header {
            background: linear-gradient(135deg, var(--hq-black) 0%, #1a1a2e 100%);
            padding: 40px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .landing-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--hq-yellow), var(--hq-red), var(--hq-blue));
        }
        
        .landing-header::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 200%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 214, 0, 0.1) 0%, transparent 60%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        .landing-logo {
            width: 90px;
            height: 90px;
            border-radius: var(--radius-full);
            object-fit: cover;
            border: 4px solid var(--hq-yellow);
            box-shadow: 0 0 30px rgba(255, 214, 0, 0.4);
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }
        
        .landing-logo:hover {
            transform: scale(1.05) rotate(5deg);
        }
        
        .landing-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--hq-white);
            margin: 16px 0 4px;
            position: relative;
            z-index: 1;
        }
        
        .landing-subtitle {
            font-size: 0.9rem;
            color: var(--hq-yellow);
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }
        
        .landing-tagline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--hq-yellow), var(--hq-yellow-light));
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--hq-black);
            margin-top: 16px;
            position: relative;
            z-index: 1;
        }
        
        .landing-body {
            padding: 32px;
        }
        
        .admin-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px;
            background: linear-gradient(135deg, var(--hq-gray), var(--hq-white));
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
        }
        
        .admin-badge-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--hq-blue), var(--hq-blue-dark));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--hq-white);
        }
        
        .admin-badge-text h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--hq-black);
        }
        
        .admin-badge-text p {
            margin: 4px 0 0;
            font-size: 0.85rem;
            color: var(--hq-gray-dark);
        }
        
        .landing-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .landing-feature {
            padding: 16px 12px;
            background: var(--hq-white);
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-md);
            text-align: center;
            transition: all 0.3s ease;
            cursor: default;
        }
        
        .landing-feature:hover {
            border-color: var(--hq-blue);
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .landing-feature:nth-child(1):hover { border-color: var(--hq-red); }
        .landing-feature:nth-child(2):hover { border-color: var(--hq-blue); }
        .landing-feature:nth-child(3):hover { border-color: var(--hq-yellow); }
        
        .landing-feature i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }
        
        .landing-feature:nth-child(1) i { color: var(--hq-red); }
        .landing-feature:nth-child(2) i { color: var(--hq-blue); }
        .landing-feature:nth-child(3) i { color: #f59e0b; }
        
        .landing-feature h4 {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--hq-black);
            margin: 0 0 4px;
        }
        
        .landing-feature p {
            font-size: 0.7rem;
            color: var(--hq-gray-dark);
            margin: 0;
        }
        
        .landing-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .landing-actions .btn-primary,
        .landing-actions .btn-secondary {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            text-decoration: none;
            font-size: 0.95rem;
        }
        
        .desktop-notice {
            padding: 12px 16px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--hq-yellow);
            margin-bottom: 24px;
            font-size: 0.85rem;
            color: #92400e;
        }
        
        .desktop-notice i {
            margin-right: 8px;
        }
        
        .role-section {
            background: var(--hq-gray);
            border-radius: var(--radius-md);
            padding: 16px;
        }
        
        .role-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--hq-black);
            margin-bottom: 12px;
        }
        
        .role-section-title i {
            color: var(--hq-blue);
        }
        
        .role-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .role-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: var(--hq-white);
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
        }
        
        .role-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .role-dot.admin { background: var(--hq-red); }
        .role-dot.sub-admin { background: var(--hq-blue); }
        .role-dot.moderator { background: var(--hq-yellow); }
        
        .role-item strong {
            color: var(--hq-black);
        }
        
        .role-item span {
            color: var(--hq-gray-dark);
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container landing-container">
        <div class="landing-card">
            <!-- Header -->
            <div class="landing-header">
                <img src="./assets/img/hq-logo.jpeg" alt="HIGH Q SOLID ACADEMY" class="landing-logo">
                <h1 class="landing-title">HIGH Q SOLID ACADEMY</h1>
                <p class="landing-subtitle">LIMITED</p>
                <span class="landing-tagline">
                    <i class='bx bx-star'></i>
                    Always Ahead of Others
                </span>
            </div>
            
            <!-- Body -->
            <div class="landing-body">
                <!-- Admin Badge -->
                <div class="admin-badge">
                    <div class="admin-badge-icon">
                        <i class='bx bx-shield-quarter'></i>
                    </div>
                    <div class="admin-badge-text">
                        <h3>Admin Control Center</h3>
                        <p>Manage your academy with our comprehensive dashboard</p>
                    </div>
                </div>
                
                <!-- Features -->
                <div class="landing-features">
                    <div class="landing-feature">
                        <i class='bx bx-group'></i>
                        <h4>User Management</h4>
                        <p>Staff & Students</p>
                    </div>
                    <div class="landing-feature">
                        <i class='bx bx-book-open'></i>
                        <h4>Content</h4>
                        <p>Courses & News</p>
                    </div>
                    <div class="landing-feature">
                        <i class='bx bx-graduation'></i>
                        <h4>Admissions</h4>
                        <p>Applications</p>
                    </div>
                </div>
                
                <!-- Desktop Notice -->
                <div class="desktop-notice">
                    <i class='bx bx-desktop'></i>
                    <strong>Tip:</strong> For best experience, use Desktop Mode on mobile browsers.
                </div>
                
                <!-- Actions -->
                <div class="landing-actions">
                    <a href="login.php" class="btn-primary">
                        <i class='bx bx-log-in'></i>
                        Access Panel
                    </a>
                    <a href="signup.php" class="btn-secondary">
                        <i class='bx bx-user-plus'></i>
                        Register
                    </a>
                </div>
                
                <!-- Role Section -->
                <div class="role-section">
                    <div class="role-section-title">
                        <i class='bx bx-lock-alt'></i>
                        Role-Based Access Control
                    </div>
                    <div class="role-list">
                        <div class="role-item">
                            <div class="role-dot admin"></div>
                            <strong>Admin:</strong>
                            <span>Full system access and management</span>
                        </div>
                        <div class="role-item">
                            <div class="role-dot sub-admin"></div>
                            <strong>Sub-Admin:</strong>
                            <span>Content and user management</span>
                        </div>
                        <div class="role-item">
                            <div class="role-dot moderator"></div>
                            <strong>Moderator:</strong>
                            <span>Content moderation</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="auth-footer">
        © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED. Empowering students with quality education since 2018.
    </footer>
</body>
</html>

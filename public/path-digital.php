<?php
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'career');
$match_score = intval($_GET['match'] ?? 0);

$page_title = 'Digital Skills & Tech Training - Your Personalized Path | High Q Tutorial';
$page_description = 'Hands-on digital skills training. Learn practical tech skills like coding, graphic design, and Microsoft Office with project-based learning.';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="digital skills, coding, graphic design, Excel, web development, tech training Nigeria">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Digital Skills & Tech Training | High Q Tutorial">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo current_url(); ?>">
    
    <!-- Canonical Tag -->
    <link rel="canonical" href="<?php echo current_url(); ?>">
    
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE === true): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="<?php echo app_url('assets/css/style.css'); ?>">
    
    <style>
        :root {
            --digital-primary: #2563eb;
            --digital-secondary: #1d4ed8;
            --digital-light: #eff6ff;
        }

        .path-header {
            background: linear-gradient(135deg, var(--digital-primary) 0%, var(--digital-secondary) 100%);
            color: white;
            padding: 3rem 1rem;
            text-align: center;
        }

        .path-header h1 {
            font-size: 2.5rem;
            margin: 0 0 0.5rem 0;
            font-weight: 700;
        }

        .path-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .section h2 {
            color: var(--digital-primary);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .track-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .track-card {
            background: var(--digital-light);
            padding: 2rem;
            border-radius: 8px;
            border-top: 4px solid var(--digital-primary);
        }

        .track-card h3 {
            color: var(--digital-primary);
            font-size: 1.3rem;
            margin: 0 0 1rem 0;
        }

        .track-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .track-card li {
            padding: 0.5rem 0;
            color: #555;
        }

        .track-card li::before {
            content: '✓ ';
            color: var(--digital-primary);
            font-weight: 700;
            margin-right: 0.5rem;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--digital-primary) 0%, var(--digital-secondary) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: white;
            color: var(--digital-primary);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: var(--digital-light);
            border-radius: 8px;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--digital-primary);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="path-header">
        <h1>💻 Your Digital Skills Path</h1>
        <p class="subtitle">Master modern technology and launch your tech career</p>
    </div>

    <div class="path-content">
        <div class="section">
            <h2><i class="fas fa-code"></i> Career-Ready Tech Skills</h2>
            <p>Your quiz indicates a <strong>career and hands-on learning focus</strong>. Our Digital Skills program is perfect for building practical technology abilities that employers value. Learn from real-world projects and gain certifications that boost your career prospects.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number">8+</div>
                    <div class="label">Tech Courses</div>
                </div>
                <div class="stat-card">
                    <div class="number">4-10</div>
                    <div class="label">Weeks Per Course</div>
                </div>
                <div class="stat-card">
                    <div class="number">100%</div>
                    <div class="label">Project-Based</div>
                </div>
                <div class="stat-card">
                    <div class="number">95%</div>
                    <div class="label">Placement Rate</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-graduation-cap"></i> Available Learning Tracks</h2>
            
            <div class="track-grid">
                <div class="track-card">
                    <h3><i class="fas fa-file-excel"></i> Microsoft Office Mastery</h3>
                    <ul>
                        <li>Word: Advanced formatting & templates</li>
                        <li>Excel: Formulas, pivot tables, data analysis</li>
                        <li>PowerPoint: Professional presentations</li>
                        <li>Real-world business projects</li>
                    </ul>
                </div>

                <div class="track-card">
                    <h3><i class="fas fa-palette"></i> Graphic Design</h3>
                    <ul>
                        <li>Adobe Photoshop fundamentals</li>
                        <li>Canva for quick designs</li>
                        <li>Logo and branding design</li>
                        <li>Design portfolio creation</li>
                    </ul>
                </div>

                <div class="track-card">
                    <h3><i class="fas fa-code"></i> Programming Basics</h3>
                    <ul>
                        <li>Python for beginners</li>
                        <li>JavaScript fundamentals</li>
                        <li>Logic and problem-solving</li>
                        <li>Build mini projects</li>
                    </ul>
                </div>

                <div class="track-card">
                    <h3><i class="fas fa-globe"></i> Web Development</h3>
                    <ul>
                        <li>HTML & CSS fundamentals</li>
                        <li>Responsive design</li>
                        <li>JavaScript interactivity</li>
                        <li>Build live websites</li>
                    </ul>
                </div>

                <div class="track-card">
                    <h3><i class="fas fa-database"></i> Data Analysis</h3>
                    <ul>
                        <li>Excel for data analysis</li>
                        <li>Google Sheets mastery</li>
                        <li>Data visualization</li>
                        <li>Business intelligence basics</li>
                    </ul>
                </div>

                <div class="track-card">
                    <h3><i class="fas fa-camera"></i> Digital Content Creation</h3>
                    <ul>
                        <li>Photography basics</li>
                        <li>Video editing</li>
                        <li>Social media content</li>
                        <li>Multimedia projects</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-star"></i> Why Choose Our Program?</h2>
            <ul style="font-size: 1.05rem; line-height: 2;">
                <li>✓ <strong>100% Hands-On</strong> - Build real projects, not just theory</li>
                <li>✓ <strong>Portfolio Building</strong> - Create work samples for job interviews</li>
                <li>✓ <strong>Industry Tools</strong> - Learn the same tools used in professional environments</li>
                <li>✓ <strong>Career Support</strong> - Job placement assistance and CV review</li>
                <li>✓ <strong>Certifications</strong> - Recognized digital skills certificates</li>
                <li>✓ <strong>Flexible Schedule</strong> - Learn at your own pace</li>
            </ul>
        </div>

        <div class="section">
            <h2><i class="fas fa-briefcase"></i> Career Outcomes</h2>
            <p>Our graduates work as:</p>
            <ul style="font-size: 1rem; line-height: 2; column-count: 2;">
                <li>Graphic Designers</li>
                <li>Web Developers</li>
                <li>Data Analysts</li>
                <li>Content Creators</li>
                <li>Virtual Assistants</li>
                <li>Freelance Specialists</li>
                <li>IT Support Specialists</li>
                <li>Digital Marketing Specialists</li>
            </ul>
        </div>

        <div class="section cta-section">
            <h3>Ready to Launch Your Tech Career?</h3>
            <p>Start with any track and build the skills employers want.</p>
            <a href="<?php echo app_url('register-new.php?path=digital'); ?>" class="btn">
                Choose Your Tech Track →
            </a>
            <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.9;">First course includes free career counseling session</p>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

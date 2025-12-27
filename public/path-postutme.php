<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'university');
$match_score = intval($_GET['match'] ?? 0);

$page_title = 'Post-UTME Preparation - Your Personalized Path | High Q Tutorial';
$page_description = 'Advanced Post-UTME exam coaching. Prepare for university screening exams with expert tutors and proven strategies for maximum performance.';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="Post-UTME preparation, university screening, JAMB score, admission coaching">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Post-UTME Preparation Path | High Q Tutorial">
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
            --postutme-primary: #dc2626;
            --postutme-secondary: #b91c1c;
            --postutme-light: #fee2e2;
        }

        .path-header {
            background: linear-gradient(135deg, var(--postutme-primary) 0%, var(--postutme-secondary) 100%);
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
            color: var(--postutme-primary);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .feature-card {
            background: var(--postutme-light);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--postutme-primary);
        }

        .feature-card h3 {
            color: var(--postutme-primary);
            margin: 0 0 0.5rem 0;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--postutme-primary) 0%, var(--postutme-secondary) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: white;
            color: var(--postutme-primary);
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
            background: var(--postutme-light);
            border-radius: 8px;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--postutme-primary);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="path-header">
        <h1>🏆 Your Post-UTME Path</h1>
        <p class="subtitle">Final step to your dream university admission</p>
    </div>

    <div class="path-content">
        <div class="section">
            <h2><i class="fas fa-star"></i> Advanced University Preparation</h2>
            <p>After passing JAMB, the Post-UTME examination is your critical final step. Our specialized program ensures you ace university screening exams and secure that coveted admission letter.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number">97%</div>
                    <div class="label">Admission Rate</div>
                </div>
                <div class="stat-card">
                    <div class="number">3-6</div>
                    <div class="label">Weeks Duration</div>
                </div>
                <div class="stat-card">
                    <div class="number">30+</div>
                    <div class="label">Mock Exams</div>
                </div>
                <div class="stat-card">
                    <div class="number">40+</div>
                    <div class="label">Universities</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-check-circle"></i> Program Highlights</h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3><i class="fas fa-university"></i> University-Specific Prep</h3>
                    <p>Customized coaching for different universities' Post-UTME formats and cut-off marks.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-brain"></i> Advanced Topics</h3>
                    <p>Deep-level questions that go beyond JAMB to test advanced understanding.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-rocket"></i> Speed & Accuracy</h3>
                    <p>Master time management and develop strategies for high-pressure exam conditions.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-handshake"></i> Admission Strategy</h3>
                    <p>Expert guidance on university choice, application timing, and admission requirements.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-award"></i> Scholarship Info</h3>
                    <p>Information on merit scholarships and how your Post-UTME score impacts eligibility.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-clock"></i> Fast-Track Option</h3>
                    <p>Condensed 3-week intensive for students who already have solid JAMB foundations.</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-trophy"></i> Success Stories</h2>
            <p><strong>Our Post-UTME students consistently achieve:</strong></p>
            <ul style="font-size: 1.05rem; line-height: 2;">
                <li>✓ <strong>Admission to top universities</strong> (UI, OAU, UNILAG, etc.)</li>
                <li>✓ <strong>Merit scholarships</strong> through excellent Post-UTME scores</li>
                <li>✓ <strong>Desired course choices</strong> secured through strategic planning</li>
                <li>✓ <strong>Smooth transition</strong> to university life</li>
            </ul>
        </div>

        <div class="section cta-section">
            <h3>Ready to Secure Your University Admission?</h3>
            <p>Your Post-UTME success starts here. Enroll now and join the ranks of successful students.</p>
            <a href="<?php echo app_url('register-new.php?path=postutme'); ?>" class="btn">
                Begin Post-UTME Preparation →
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

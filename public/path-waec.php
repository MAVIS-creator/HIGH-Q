<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'university');
$match_score = intval($_GET['match'] ?? 0);

$page_title = 'WAEC & GCE Exams - Your Personalized Path | High Q Tutorial';
$page_description = 'Your personalized WAEC/GCE preparation path. Complete exam coaching, past questions, and intensive tutoring for O-Level success.';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="WAEC preparation, GCE exam, O-Level coaching, NECO, Nigeria">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="WAEC & GCE Exams Path | High Q Tutorial">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo current_url(); ?>">
    
    <!-- Canonical Tag -->
    <link rel="canonical" href="<?php echo current_url(); ?>">
    
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="<?php echo app_url('assets/css/style.css'); ?>">
    
    <style>
        :root {
            --waec-primary: #059669;
            --waec-secondary: #047857;
            --waec-light: #ecfdf5;
        }

        .path-header {
            background: linear-gradient(135deg, var(--waec-primary) 0%, var(--waec-secondary) 100%);
            color: white;
            padding: 3rem 1rem;
            text-align: center;
            border-bottom: 4px solid #fff;
        }

        .path-header h1 {
            font-size: 2.5rem;
            margin: 0 0 0.5rem 0;
            font-weight: 700;
        }

        .path-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            margin: 0.5rem 0 1rem 0;
        }

        .match-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.95rem;
            margin-top: 1rem;
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
            color: var(--waec-primary);
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
            margin-bottom: 2rem;
        }

        .feature-card {
            background: var(--waec-light);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--waec-primary);
        }

        .feature-card h3 {
            color: var(--waec-primary);
            margin: 0 0 0.5rem 0;
        }

        .timeline {
            position: relative;
            padding: 2rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--waec-primary);
        }

        .timeline-item {
            margin-bottom: 2rem;
            margin-left: 60px;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -50px;
            top: 5px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--waec-primary);
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--waec-primary);
        }

        .timeline-item h3 {
            color: var(--waec-primary);
            margin: 0 0 0.5rem 0;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--waec-primary) 0%, var(--waec-secondary) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 2rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: white;
            color: var(--waec-primary);
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
            background: var(--waec-light);
            border-radius: 8px;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--waec-primary);
        }

        @media (max-width: 768px) {
            .path-header h1 {
                font-size: 1.8rem;
            }

            .timeline::before {
                left: 8px;
            }

            .timeline-item {
                margin-left: 40px;
            }

            .timeline-item::before {
                left: -30px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="path-header">
        <h1>📚 Your WAEC/GCE Path</h1>
        <p class="subtitle">Complete preparation for O-Level and GCE examinations</p>
        <?php if ($match_score > 0): ?>
        <div class="match-badge">
            <strong><?php echo $match_score; ?>% Match</strong> with your learning style
        </div>
        <?php endif; ?>
    </div>

    <div class="path-content">
        <div class="section">
            <h2><i class="fas fa-lightbulb"></i> Why This Path for You?</h2>
            <p>Your quiz results indicate a strong focus on <strong>academic foundation and mastery</strong>. Our WAEC/GCE program is designed to give you comprehensive coverage of O-Level subjects with intensive coaching, past questions practice, and exam strategies that maximize your grades.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number">92%</div>
                    <div class="label">Pass Rate</div>
                </div>
                <div class="stat-card">
                    <div class="number">8-24</div>
                    <div class="label">Weeks Duration</div>
                </div>
                <div class="stat-card">
                    <div class="number">100+</div>
                    <div class="label">Past Questions</div>
                </div>
                <div class="stat-card">
                    <div class="number">15+</div>
                    <div class="label">Mock Exams</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-check-circle"></i> What's Included</h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3><i class="fas fa-book"></i> Complete Subject Coverage</h3>
                    <p>All WAEC/NECO subjects with expert instructors covering syllabus from basics to advanced topics.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-tasks"></i> Past Questions Library</h3>
                    <p>Access to 100+ past exam questions with worked solutions and explanations for self-study.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-users"></i> Small Group Classes</h3>
                    <p>Maximum 8 students per class for personalized attention and quick doubt resolution.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-chart-line"></i> Mock Examinations</h3>
                    <p>15+ mock exams under real exam conditions with detailed feedback on performance.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-hourglass-half"></i> Exam Strategies</h3>
                    <p>Learn time management, question prioritization, and exam techniques from experienced coaches.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-calendar-check"></i> Flexible Timing</h3>
                    <p>Classes aligned with exam calendar (May/June school candidates or anytime for private candidates).</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-road"></i> Your Learning Roadmap</h2>
            
            <div class="timeline">
                <div class="timeline-item">
                    <h3>Weeks 1-2: Foundation Assessment</h3>
                    <p>Evaluate current knowledge level, identify weak areas, and establish personalized learning plan.</p>
                </div>

                <div class="timeline-item">
                    <h3>Weeks 3-6: Core Concepts</h3>
                    <p>Intensive teaching of key concepts, formulas, and theories across all subjects.</p>
                </div>

                <div class="timeline-item">
                    <h3>Weeks 7-10: Application & Practice</h3>
                    <p>Solve past questions, apply concepts to different scenarios, and master problem-solving.</p>
                </div>

                <div class="timeline-item">
                    <h3>Weeks 11-16: Mock Exam Series</h3>
                    <p>Weekly mock exams with instant feedback, remediation of weak areas, and confidence building.</p>
                </div>

                <div class="timeline-item">
                    <h3>Weeks 17-24: Final Polish</h3>
                    <p>Speed work, difficult questions review, exam psychology, and final exam preparation.</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-trophy"></i> Expected Outcomes</h2>
            <ul style="font-size: 1.05rem; line-height: 2;">
                <li>✓ <strong>Distinction in core subjects</strong> (A1-B3 grades)</li>
                <li>✓ <strong>Improved confidence</strong> in exam-taking</li>
                <li>✓ <strong>Better grades across all subjects</strong> (average improvement of 2-3 grade points)</li>
                <li>✓ <strong>Readiness for university entrance</strong> (JAMB or Post-UTME)</li>
                <li>✓ <strong>Comprehensive subject mastery</strong> (applicable to future learning)</li>
            </ul>
        </div>

        <div class="section cta-section">
            <h3>Ready to Excel in WAEC/GCE?</h3>
            <p>Join our proven program and achieve the grades you deserve.</p>
            <a href="<?php echo app_url('register-new.php?path=waec'); ?>" class="btn">
                Start Your Preparation Now →
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

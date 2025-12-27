<?php
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'international');
$match_score = intval($_GET['match'] ?? 0);

$page_title = 'International Education Path - Study Abroad Preparation | High Q Tutorial';
$page_description = 'Comprehensive international exam preparation. Master SAT, TOEFL, IELTS, GMAT, GRE and A-Levels with expert coaches for global academic success.';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="SAT, IELTS, TOEFL, GMAT, GRE, A-Levels, study abroad, international education">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="International Education Path | High Q Tutorial">
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
            --intl-primary: #9333ea;
            --intl-secondary: #7e22ce;
            --intl-light: #faf5ff;
        }

        .path-header {
            background: linear-gradient(135deg, var(--intl-primary) 0%, var(--intl-secondary) 100%);
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
            color: var(--intl-primary);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .exam-card {
            background: var(--intl-light);
            padding: 1.5rem;
            border-radius: 8px;
            border-top: 4px solid var(--intl-primary);
        }

        .exam-card h3 {
            color: var(--intl-primary);
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
        }

        .exam-card .badge {
            display: inline-block;
            background: var(--intl-primary);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .exam-card p {
            margin: 0.5rem 0;
            color: #555;
            font-size: 0.95rem;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--intl-primary) 0%, var(--intl-secondary) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: white;
            color: var(--intl-primary);
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
            background: var(--intl-light);
            border-radius: 8px;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--intl-primary);
        }

        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .feature-list li {
            padding: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feature-list li::before {
            content: '✓';
            color: var(--intl-primary);
            font-weight: 700;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="path-header">
        <h1>🌍 Your International Education Path</h1>
        <p class="subtitle">Global credentials for international academic opportunities</p>
    </div>

    <div class="path-content">
        <div class="section">
            <h2><i class="fas fa-globe"></i> Global Academic Excellence</h2>
            <p>Your quiz indicates <strong>ambition for international education and advanced studies</strong>. This path is designed to prepare you for international examinations, global universities, and advanced academic challenges. Whether you're aiming for study abroad or pursuing direct entry to Nigerian universities via A-Levels, we've got you covered.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number">12+</div>
                    <div class="label">International Exams</div>
                </div>
                <div class="stat-card">
                    <div class="number">10-24</div>
                    <div class="label">Weeks Duration</div>
                </div>
                <div class="stat-card">
                    <div class="number">150+</div>
                    <div class="label">Hours Training</div>
                </div>
                <div class="stat-card">
                    <div class="number">100%</div>
                    <div class="label">Expert Coaches</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-certificate"></i> International Exam Programs</h2>
            
            <div class="exam-grid">
                <div class="exam-card">
                    <h3>SAT</h3>
                    <span class="badge">University Admission</span>
                    <p><strong>For:</strong> US/International universities</p>
                    <p><strong>Coverage:</strong> Math, Evidence-Based Reading & Writing</p>
                    <p><strong>Duration:</strong> 10-12 weeks</p>
                </div>

                <div class="exam-card">
                    <h3>TOEFL</h3>
                    <span class="badge">English Proficiency</span>
                    <p><strong>For:</strong> English-speaking countries</p>
                    <p><strong>Coverage:</strong> Reading, Writing, Listening, Speaking</p>
                    <p><strong>Duration:</strong> 6-8 weeks</p>
                </div>

                <div class="exam-card">
                    <h3>IELTS</h3>
                    <span class="badge">English Proficiency</span>
                    <p><strong>For:</strong> UK, Australia, Canada</p>
                    <p><strong>Coverage:</strong> Academic & General modules</p>
                    <p><strong>Duration:</strong> 6-8 weeks</p>
                </div>

                <div class="exam-card">
                    <h3>GMAT</h3>
                    <span class="badge">Graduate Business</span>
                    <p><strong>For:</strong> MBA/Business graduate programs</p>
                    <p><strong>Coverage:</strong> Quantitative, Verbal, Analytical Writing</p>
                    <p><strong>Duration:</strong> 10-12 weeks</p>
                </div>

                <div class="exam-card">
                    <h3>GRE</h3>
                    <span class="badge">Graduate Programs</span>
                    <p><strong>For:</strong> Master's & PhD programs</p>
                    <p><strong>Coverage:</strong> Verbal, Quantitative, Analytical Writing</p>
                    <p><strong>Duration:</strong> 10-12 weeks</p>
                </div>

                <div class="exam-card">
                    <h3>A-Levels</h3>
                    <span class="badge">Advanced Qualifications</span>
                    <p><strong>For:</strong> Direct Entry universities</p>
                    <p><strong>Coverage:</strong> Cambridge/IJMB syllabi</p>
                    <p><strong>Duration:</strong> 12-18 weeks</p>
                </div>

                <div class="exam-card">
                    <h3>IGCSE</h3>
                    <span class="badge">International O-Levels</span>
                    <p><strong>For:</strong> Global universities</p>
                    <p><strong>Coverage:</strong> Cambridge O-Level subjects</p>
                    <p><strong>Duration:</strong> 8-16 weeks</p>
                </div>

                <div class="exam-card">
                    <h3>JUPEB</h3>
                    <span class="badge">Direct Entry</span>
                    <p><strong>For:</strong> Nigerian university Direct Entry</p>
                    <p><strong>Coverage:</strong> Advanced academics</p>
                    <p><strong>Duration:</strong> 12-18 weeks</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-check-circle"></i> Complete Program Includes</h2>
            <ul class="feature-list" style="font-size: 1rem;">
                <li>Expert instructors with international exam experience</li>
                <li>Official practice materials & past papers</li>
                <li>Full-length mock exams under timed conditions</li>
                <li>One-on-one coaching sessions</li>
                <li>Writing review and feedback</li>
                <li>Speaking practice & confidence building</li>
                <li>Study resources & access to online platforms</li>
                <li>Career counseling for international universities</li>
                <li>Visa & study abroad guidance</li>
                <li>Flexible scheduling for working professionals</li>
            </ul>
        </div>

        <div class="section">
            <h2><i class="fas fa-trophy"></i> Success Outcomes</h2>
            <p><strong>Our international exam students typically achieve:</strong></p>
            <ul style="font-size: 1.05rem; line-height: 2;">
                <li>✓ <strong>SAT:</strong> 1400+ scores (qualified for top universities)</li>
                <li>✓ <strong>IELTS/TOEFL:</strong> 7.0+/100+ (competitive for university admission)</li>
                <li>✓ <strong>GMAT/GRE:</strong> 160+ percentile in respective sections</li>
                <li>✓ <strong>A-Levels:</strong> Strong grades for Direct Entry programs</li>
                <li>✓ <strong>Admission to international universities</strong> across the globe</li>
                <li>✓ <strong>Scholarship opportunities</strong> from merit-based programs</li>
            </ul>
        </div>

        <div class="section">
            <h2><i class="fas fa-lightbulb"></i> Study Abroad Support</h2>
            <p>Beyond exam preparation, we provide:</p>
            <ul style="font-size: 1.05rem; line-height: 2;">
                <li>✓ University selection guidance based on your profile</li>
                <li>✓ Application essay coaching</li>
                <li>✓ Interview preparation for international universities</li>
                <li>✓ Visa documentation and interview support</li>
                <li>✓ Financial aid and scholarship guidance</li>
                <li>✓ Pre-departure orientation</li>
            </ul>
        </div>

        <div class="section cta-section">
            <h3>Ready to Achieve Your Global Academic Dreams?</h3>
            <p>Start your international education journey with comprehensive exam prep and global guidance.</p>
            <a href="<?php echo app_url('register-new.php?path=international'); ?>" class="btn">
                Begin International Preparation →
            </a>
            <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.9;">
                Free consultation: Schedule a call to discuss your international education goals
            </p>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

<?php
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/learning-roadmap.php';
require_once __DIR__ . '/includes/outcome-dashboard.php';
require_once __DIR__ . '/includes/program-tutors.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'university');
$match_score = intval($_GET['match'] ?? 0);

// Initialize page variables
$page_title = 'JAMB & University Admission - Your Personalized Path | High Q Tutorial';
$page_description = 'Your personalized JAMB preparation path designed specifically for your goals. CBT training, mock exams, and university admission guidance.';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="JAMB preparation, CBT training, university admission, JAMB coaching, Nigeria">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="JAMB & University Admission Path | High Q Tutorial">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo current_url(); ?>">
    <meta property="og:image" content="<?php echo app_url('assets/images/jamb-path.jpg'); ?>">
    
    <!-- Canonical Tag -->
    <link rel="canonical" href="<?php echo current_url(); ?>">
    
    <!-- Prevent Indexing if Debug Mode -->
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE === true): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="<?php echo app_url('assets/css/style.css'); ?>">
    
    <style>
        :root {
            --jamb-primary: #4f46e5;
            --jamb-secondary: #7c3aed;
            --jamb-light: #f0f4ff;
        }

        .path-header {
            background: linear-gradient(135deg, var(--jamb-primary) 0%, var(--jamb-secondary) 100%);
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

        .match-badge strong {
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
            color: var(--jamb-primary);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section h2 i {
            font-size: 1.5rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .feature-card {
            background: var(--jamb-light);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--jamb-primary);
        }

        .feature-card h3 {
            color: var(--jamb-primary);
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
        }

        .feature-card p {
            margin: 0;
            color: #555;
            font-size: 0.95rem;
            line-height: 1.5;
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
            background: var(--jamb-primary);
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
            background: var(--jamb-primary);
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--jamb-primary);
        }

        .timeline-item h3 {
            color: var(--jamb-primary);
            margin: 0 0 0.5rem 0;
        }

        .timeline-item p {
            margin: 0;
            color: #555;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--jamb-primary) 0%, var(--jamb-secondary) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 2rem;
        }

        .cta-section h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }

        .cta-section p {
            margin: 0.5rem 0 1.5rem 0;
            font-size: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: white;
            color: var(--jamb-primary);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
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
            background: var(--jamb-light);
            border-radius: 8px;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--jamb-primary);
        }

        .stat-card .label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .path-header h1 {
                font-size: 1.8rem;
            }

            .path-header .subtitle {
                font-size: 1rem;
            }

            .section {
                padding: 1.5rem;
            }

            .section h2 {
                font-size: 1.5rem;
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

    <!-- Path Header -->
    <div class="path-header">
        <h1>🎯 Your JAMB Path</h1>
        <p class="subtitle">Personalized preparation for Nigerian university admission</p>
        <?php if ($match_score > 0): ?>
        <div class="match-badge">
            <strong><?php echo $match_score; ?>% Match</strong> with your goals & learning style
        </div>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="path-content">
        <!-- Overview Section -->
        <div class="section">
            <h2><i class="fas fa-lightbulb"></i> Why This Path for You?</h2>
            <p>Based on your quiz responses indicating a goal of <strong>university admission</strong>, our JAMB-focused program is perfectly suited to your needs. This comprehensive path provides everything you need to excel in the JAMB/UTME examination and gain admission to your chosen Nigerian university.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number">305</div>
                    <div class="label">Highest Score 2025</div>
                </div>
                <div class="stat-card">
                    <div class="number">94%</div>
                    <div class="label">Success Rate</div>
                </div>
                <div class="stat-card">
                    <div class="number">6-12</div>
                    <div class="label">Weeks Duration</div>
                </div>
                <div class="stat-card">
                    <div class="number">50+</div>
                    <div class="label">Mock Exams</div>
                </div>
            </div>
        </div>

        <!-- What's Included Section -->
        <div class="section">
            <h2><i class="fas fa-check-circle"></i> What's Included in Your Program</h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3><i class="fas fa-laptop"></i> CBT Training</h3>
                    <p>Computer-Based Test simulations identical to the real JAMB exam environment to build confidence and speed.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-book"></i> Full Syllabus Coverage</h3>
                    <p>Complete coverage of all JAMB/UTME subjects with expert instructors specializing in each subject area.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-chart-bar"></i> Mock Exams</h3>
                    <p>50+ practice tests under timed, exam-like conditions with detailed performance analytics and feedback.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-chalkboard-user"></i> Expert Tutoring</h3>
                    <p>One-on-one sessions with experienced JAMB coaches to strengthen weak areas and boost confidence.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-graduation-cap"></i> Admission Guidance</h3>
                    <p>Expert guidance on university choice, application strategies, and Post-UTME preparation.</p>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-clock"></i> Flexible Schedule</h3>
                    <p>Classes during weekdays and weekends to fit your school/work commitments perfectly.</p>
                </div>
            </div>
        </div>

        <!-- Learning Roadmap Section -->
        <div class="section">
            <h2><i class="fas fa-road"></i> Your 12-Week Learning Roadmap</h2>
            
            <div class="timeline">
                <div class="timeline-item">
                    <h3>Weeks 1-2: Foundation & Registration</h3>
                    <p>Complete JAMB registration, establish baseline knowledge, introduce CBT environment, and set achievement targets.</p>
                </div>

                <div class="timeline-item">
                    <h3>Weeks 3-5: Core Subject Mastery</h3>
                    <p>Intensive teaching of all JAMB subjects with focus on core concepts, formulas, and problem-solving techniques.</p>
                </div>

                <div class="timeline-item">
                    <h3>Weeks 6-8: Advanced Topics & Applications</h3>
                    <p>Deep dive into complex topics, application of concepts to real-world scenarios, and practice with difficult questions.</p>
                </div>

                <div class="timeline-item">
                    <h3>Weeks 9-10: Mock Exam Series</h3>
                    <p>Full-length mock exams every 2-3 days with detailed analysis, individual feedback, and remediation of weak areas.</p>
                </div>

                <div class="timeline-item">
                    <h3>Week 11: Final Review & Strategy</h3>
                    <p>Quick review of key concepts, test-taking strategies, time management, and psychological preparation for exam day.</p>
                </div>

                <div class="timeline-item">
                    <h3>Week 12: Post-UTME Prep</h3>
                    <p>Begin preparation for university Post-UTME screening exams and learn admission application strategies.</p>
                </div>
            </div>
        </div>

        <!-- Success Metrics Section -->
        <div class="section">
            <h2><i class="fas fa-trophy"></i> Success Metrics & Outcomes</h2>
            <p>Students in our JAMB program typically achieve:</p>
            <ul style="font-size: 1.05rem; line-height: 2;">
                <li>✓ <strong>250+ JAMB scores</strong> (excellent range for most universities)</li>
                <li>✓ <strong>300+ for top universities</strong> (with intensive preparation)</li>
                <li>✓ <strong>100% registration success</strong> (no delays or missing documents)</li>
                <li>✓ <strong>Post-UTME readiness</strong> (prepared for university screening exams)</li>
                <li>✓ <strong>Confidence & reduced exam anxiety</strong> (thorough preparation builds mental resilience)</li>
            </ul>
        </div>

        <!-- Next Steps Section -->
        <div class="section">
            <h2><i class="fas fa-arrow-right"></i> Next Steps</h2>
            <p>You're ready to start your JAMB journey! Here's what happens next:</p>
            
            <ol style="font-size: 1.05rem; line-height: 2;">
                <li><strong>Register Today</strong> - Complete your enrollment and choose your preferred class schedule</li>
                <li><strong>Schedule Your Orientation</strong> - Meet your assigned JAMB coach and take a diagnostic test</li>
                <li><strong>Begin Your Program</strong> - Start with foundational concepts and build toward exam success</li>
                <li><strong>Track Your Progress</strong> - Monitor your improvement through our analytics dashboard</li>
                <li><strong>Ace Your Exam</strong> - Walk into the exam room with confidence and achieve your university dreams</li>
            </ol>
        </div>

        <!-- CTA Section -->
        <div class="section cta-section">
            <h3>Ready to Start Your JAMB Journey?</h3>
            <p>Join hundreds of successful students who've achieved their university dreams through our proven JAMB program.</p>
            <a href="<?php echo app_url('register-new.php?path=jamb&goal=university'); ?>" class="btn">
                Enroll Now & Start Preparing →
            </a>
            <p style="font-size: 0.9rem; margin-top: 1rem; opacity: 0.9;">
                Questions? <a href="<?php echo app_url('contact.php'); ?>" style="color: white; text-decoration: underline;">Contact us</a> or call <strong>+234 XXX XXX XXXX</strong>
            </p>
        </div>

        <!-- FAQ Section -->
        <div class="section">
            <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
            
            <div style="margin-bottom: 1.5rem;">
                <h3 style="color: var(--jamb-primary); margin-bottom: 0.5rem;">When should I start my JAMB preparation?</h3>
                <p>Ideally 6-12 weeks before the JAMB exam. However, we can accelerate or customize the pace based on your readiness. The earlier you start, the more practice you'll get.</p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <h3 style="color: var(--jamb-primary); margin-bottom: 0.5rem;">What are your class schedules?</h3>
                <p>We offer flexible schedules including weekday afternoon/evening classes and weekend intensive sessions. You can choose what works best with your school schedule.</p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <h3 style="color: var(--jamb-primary); margin-bottom: 0.5rem;">How much does the program cost?</h3>
                <p>Our JAMB program is competitively priced. Contact us for current packages, payment plans, and any scholarships that may apply to your situation.</p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <h3 style="color: var(--jamb-primary); margin-bottom: 0.5rem;">Do you offer Post-UTME preparation?</h3>
                <p>Yes! We include Post-UTME preparation as part of your JAMB program. This ensures you're ready for university screening exams after your JAMB result.</p>
            </div>

            <div>
                <h3 style="color: var(--jamb-primary); margin-bottom: 0.5rem;">What if I'm not ready after 12 weeks?</h3>
                <p>We provide extension options and ongoing support. Our goal is your success, not just completing a program. We'll work with you until you feel confident.</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        // Track recommendation source
        document.addEventListener('DOMContentLoaded', function() {
            // Send event to analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'view_path', {
                    'path_type': 'jamb',
                    'match_score': <?php echo $match_score; ?>,
                    'user_goal': '<?php echo $goal; ?>'
                });
            }
        });
    </script>
</body>
</html>

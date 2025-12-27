<?php
/**
 * Path: JAMB/UTME
 * Personalized landing page for students who match the JAMB preparation path
 */
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'university');
$match_score = intval($_GET['match'] ?? 0);

// SEO Meta Tags
$pageTitle = 'JAMB/UTME Preparation - Your Personalized Path | High Q Tutorial';
$pageDescription = 'Comprehensive JAMB preparation with expert tutors. Achieve 300+ scores with our proven CBT training methodology.';
$pageKeywords = 'JAMB preparation, UTME coaching, CBT training, university admission Nigeria';

include __DIR__ . '/includes/header.php';
?>

<style>
/* Path Page Styles - JAMB Theme (Purple) */
:root {
    --path-primary: #7c3aed;
    --path-secondary: #6d28d9;
    --path-light: #f5f3ff;
}

.path-hero {
    background: linear-gradient(135deg, var(--path-primary) 0%, var(--path-secondary) 100%);
    color: white;
    padding: 4rem 1rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.path-hero h1 {
    font-size: 2.5rem;
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.path-hero .subtitle {
    font-size: 1.2rem;
    opacity: 0.95;
    margin: 0;
}

.match-badge {
    display: inline-block;
    background: var(--hq-yellow, #ffd600);
    color: #0b1a2c;
    padding: 0.5rem 1.5rem;
    border-radius: 50px;
    font-weight: 700;
    margin-top: 1.5rem;
}

.path-content {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.path-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.path-section h2 {
    color: var(--hq-navy, #0b1a2c);
    font-size: 1.8rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.path-section h2 i {
    color: var(--hq-yellow, #ffd600);
    font-size: 1.5rem;
}

.path-section p {
    color: #4b5563;
    line-height: 1.7;
    margin-bottom: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    text-align: center;
    padding: 1.5rem 1rem;
    background: #f9fafb;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.stat-card .number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--hq-navy, #0b1a2c);
    display: block;
}

.stat-card .label {
    font-size: 0.9rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.feature-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.feature-card:hover {
    border-color: var(--hq-yellow, #ffd600);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.feature-card h3 {
    color: var(--hq-navy, #0b1a2c);
    font-size: 1.25rem;
    margin: 0 0 0.75rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.feature-card h3 i {
    color: var(--hq-yellow, #ffd600);
}

.feature-card p {
    margin: 0;
    color: #4b5563;
    font-size: 0.95rem;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, var(--hq-yellow, #ffd600), var(--path-primary));
    border-radius: 3px;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
    padding-left: 1.5rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -2rem;
    top: 0;
    width: 16px;
    height: 16px;
    background: var(--hq-yellow, #ffd600);
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-item h4 {
    color: var(--hq-navy, #0b1a2c);
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.timeline-item p {
    margin: 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.cta-section {
    background: linear-gradient(135deg, var(--hq-navy, #0b1a2c) 0%, #1e3a5f 100%);
    color: white;
    padding: 2.5rem;
    border-radius: 16px;
    text-align: center;
}

.cta-section h3 {
    font-size: 1.8rem;
    margin: 0 0 0.75rem 0;
}

.cta-section p {
    color: rgba(255,255,255,0.9);
    margin-bottom: 1.5rem;
}

.btn-cta {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: var(--hq-yellow, #ffd600);
    color: #0b1a2c;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 214, 0, 0.4);
    color: #0b1a2c;
}

@media (max-width: 768px) {
    .path-hero h1 {
        font-size: 1.8rem;
    }
    
    .path-section {
        padding: 1.5rem;
    }
    
    .path-section h2 {
        font-size: 1.4rem;
    }
    
    .stat-card .number {
        font-size: 1.5rem;
    }
}
</style>

<!-- Path Hero Section -->
<section class="path-hero">
    <div class="container">
        <h1><i class='bx bx-target-lock'></i> Your JAMB Path</h1>
        <p class="subtitle">Personalized preparation for Nigerian university admission</p>
        <?php if ($match_score > 0): ?>
        <div class="match-badge">
            <strong><?php echo $match_score; ?>% Match</strong> with your goals
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Main Content -->
<div class="path-content">
    <!-- Overview Section -->
    <div class="path-section">
        <h2><i class='bx bx-bulb'></i> Why This Path for You?</h2>
        <p>Based on your quiz responses indicating a goal of <strong>university admission</strong>, our JAMB-focused program is perfectly suited to your needs. This comprehensive path provides everything you need to excel in the JAMB/UTME examination and gain admission to your chosen Nigerian university.</p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="number">305</span>
                <span class="label">Highest Score 2025</span>
            </div>
            <div class="stat-card">
                <span class="number">94%</span>
                <span class="label">Success Rate</span>
            </div>
            <div class="stat-card">
                <span class="number">6-12</span>
                <span class="label">Weeks Duration</span>
            </div>
            <div class="stat-card">
                <span class="number">1000+</span>
                <span class="label">Students Coached</span>
            </div>
        </div>
    </div>

    <!-- What's Included -->
    <div class="path-section">
        <h2><i class='bx bx-package'></i> What's Included</h2>
        
        <div class="feature-grid">
            <div class="feature-card">
                <h3><i class='bx bx-desktop'></i> CBT Simulation Lab</h3>
                <p>Practice on our state-of-the-art Computer-Based Test center with real JAMB interface simulation</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-book-open'></i> All 4 JAMB Subjects</h3>
                <p>Expert tutoring in Use of English plus your 3 chosen subjects with comprehensive study materials</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-time'></i> Flexible Schedule</h3>
                <p>Morning, afternoon, or evening classes to fit around your school or work commitments</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-chart'></i> Progress Tracking</h3>
                <p>Weekly mock tests with detailed performance analysis and personalized improvement plans</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-edit'></i> JAMB Registration</h3>
                <p>Full assistance with biometric capture, profile creation, and exam registration</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-support'></i> Mentor Support</h3>
                <p>Dedicated academic mentor to guide you through challenges and keep you motivated</p>
            </div>
        </div>
    </div>

    <!-- Preparation Timeline -->
    <div class="path-section">
        <h2><i class='bx bx-calendar'></i> Your Preparation Timeline</h2>
        
        <div class="timeline">
            <div class="timeline-item">
                <h4>Weeks 1-2: Foundation</h4>
                <p>Diagnostic tests, syllabus overview, and study plan development. Identify strengths and areas needing improvement.</p>
            </div>
            <div class="timeline-item">
                <h4>Weeks 3-6: Core Content</h4>
                <p>Intensive subject teaching with focus on JAMB-specific topics and exam techniques.</p>
            </div>
            <div class="timeline-item">
                <h4>Weeks 7-10: Practice Phase</h4>
                <p>Daily past questions practice, CBT drills, and time management training.</p>
            </div>
            <div class="timeline-item">
                <h4>Weeks 11-12: Final Prep</h4>
                <p>Full mock exams, revision of weak areas, and mental preparation for exam day.</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="path-section cta-section">
        <h3>Ready to Secure Your University Admission?</h3>
        <p>Join hundreds of successful students who achieved their JAMB goals with HQ Academy.</p>
        <a href="<?php echo app_url('register-new.php?path=jamb'); ?>" class="btn-cta">
            Start Your JAMB Journey <i class='bx bx-right-arrow-alt'></i>
        </a>
        <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">Limited slots available for 2025 JAMB session</p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

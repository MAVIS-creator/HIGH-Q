<?php
/**
 * Path: WAEC/NECO/GCE
 * Personalized landing page for students who match the WAEC preparation path
 */
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'olevel');
$match_score = intval($_GET['match'] ?? 0);

// SEO Meta Tags
$pageTitle = 'WAEC/NECO/GCE Preparation - Your Personalized Path | High Q Tutorial';
$pageDescription = 'Excel in WAEC, NECO, and GCE exams with expert tutoring. Achieve A1s and Bs with our proven teaching methodology.';
$pageKeywords = 'WAEC preparation, NECO coaching, GCE tutoring, O-Level exams Nigeria';

include __DIR__ . '/includes/header.php';
?>

<style>
/* Path Page Styles - WAEC Theme (Green) */
:root {
    --path-primary: #059669;
    --path-secondary: #047857;
    --path-light: #ecfdf5;
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

.subjects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.subject-card {
    background: var(--path-light);
    padding: 1.25rem;
    border-radius: 10px;
    border-left: 4px solid var(--path-primary);
    transition: all 0.3s ease;
}

.subject-card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.subject-card h4 {
    color: var(--hq-navy, #0b1a2c);
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
}

.subject-card p {
    margin: 0;
    font-size: 0.85rem;
    color: #6b7280;
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
        <h1><i class='bx bx-book-bookmark'></i> Your WAEC/NECO Path</h1>
        <p class="subtitle">Excel in O-Level examinations with expert guidance</p>
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
        <p>Your quiz results indicate you're focused on <strong>O-Level excellence</strong>. Our WAEC/NECO/GCE preparation program is designed to help you achieve outstanding grades in your senior secondary examinations, setting a strong foundation for university admission.</p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="number">89%</span>
                <span class="label">A1-B3 Rate</span>
            </div>
            <div class="stat-card">
                <span class="number">500+</span>
                <span class="label">Students Coached</span>
            </div>
            <div class="stat-card">
                <span class="number">9</span>
                <span class="label">Subject Options</span>
            </div>
            <div class="stat-card">
                <span class="number">Expert</span>
                <span class="label">Tutors</span>
            </div>
        </div>
    </div>

    <!-- Available Subjects -->
    <div class="path-section">
        <h2><i class='bx bx-book-reader'></i> Available Subjects</h2>
        <p>We offer comprehensive tutoring in all major WAEC/NECO subjects:</p>
        
        <div class="subjects-grid">
            <div class="subject-card">
                <h4>English Language</h4>
                <p>Grammar, Comprehension, Essays</p>
            </div>
            <div class="subject-card">
                <h4>Mathematics</h4>
                <p>Core Math & Further Math</p>
            </div>
            <div class="subject-card">
                <h4>Physics</h4>
                <p>Theory & Practicals</p>
            </div>
            <div class="subject-card">
                <h4>Chemistry</h4>
                <p>Theory & Practicals</p>
            </div>
            <div class="subject-card">
                <h4>Biology</h4>
                <p>Theory & Practicals</p>
            </div>
            <div class="subject-card">
                <h4>Economics</h4>
                <p>Micro & Macro Economics</p>
            </div>
            <div class="subject-card">
                <h4>Government</h4>
                <p>Nigerian & Comparative Govt</p>
            </div>
            <div class="subject-card">
                <h4>Literature</h4>
                <p>Prose, Drama & Poetry</p>
            </div>
        </div>
    </div>

    <!-- What's Included -->
    <div class="path-section">
        <h2><i class='bx bx-package'></i> What's Included</h2>
        
        <div class="feature-grid">
            <div class="feature-card">
                <h3><i class='bx bx-chalkboard'></i> Expert Teaching</h3>
                <p>Experienced teachers who understand WAEC marking schemes and exam patterns</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-file'></i> Past Questions</h3>
                <p>Comprehensive past question database with detailed solutions and explanations</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-flask'></i> Practical Sessions</h3>
                <p>Hands-on practical classes for science subjects with real lab equipment</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-edit-alt'></i> Mock Exams</h3>
                <p>Regular mock examinations that simulate actual WAEC/NECO conditions</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="path-section cta-section">
        <h3>Ready to Excel in Your O-Levels?</h3>
        <p>Join our WAEC/NECO preparation program and achieve the grades you deserve.</p>
        <a href="<?php echo app_url('register-new.php?path=waec'); ?>" class="btn-cta">
            Start Your O-Level Journey <i class='bx bx-right-arrow-alt'></i>
        </a>
        <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">Enrolling now for 2025 WAEC/NECO sessions</p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

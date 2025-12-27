<?php
/**
 * Path: Post-UTME
 * Personalized landing page for students who match the Post-UTME preparation path
 */
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'admission');
$match_score = intval($_GET['match'] ?? 0);

// SEO Meta Tags
$pageTitle = 'Post-UTME Preparation - Your Personalized Path | High Q Tutorial';
$pageDescription = 'Comprehensive Post-UTME screening preparation for Nigerian universities. Pass your institutional screening with confidence.';
$pageKeywords = 'Post-UTME preparation, university screening, LAUTECH Post-UTME, admission coaching Nigeria';

include __DIR__ . '/includes/header.php';
?>

<style>
/* Path Page Styles - Post-UTME Theme (Red) */
:root {
    --path-primary: #dc2626;
    --path-secondary: #b91c1c;
    --path-light: #fef2f2;
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

.university-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}

.university-card {
    background: white;
    padding: 1.25rem;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    text-align: center;
    transition: all 0.3s ease;
}

.university-card:hover {
    border-color: var(--hq-yellow, #ffd600);
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}

.university-card h4 {
    color: var(--hq-navy, #0b1a2c);
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
}

.university-card p {
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
        <h1><i class='bx bx-building'></i> Your Post-UTME Path</h1>
        <p class="subtitle">Pass your university screening and secure admission</p>
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
        <p>You've already passed JAMB - congratulations! Now you need to pass your university's Post-UTME screening. Our program is specifically designed to help you <strong>navigate institution-specific requirements</strong> and secure your admission.</p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="number">96%</span>
                <span class="label">Pass Rate</span>
            </div>
            <div class="stat-card">
                <span class="number">15+</span>
                <span class="label">Universities Covered</span>
            </div>
            <div class="stat-card">
                <span class="number">2-4</span>
                <span class="label">Weeks Duration</span>
            </div>
            <div class="stat-card">
                <span class="number">Intensive</span>
                <span class="label">Training Mode</span>
            </div>
        </div>
    </div>

    <!-- Universities Covered -->
    <div class="path-section">
        <h2><i class='bx bx-buildings'></i> Universities We Prepare For</h2>
        <p>We have specialized preparation materials for major Nigerian universities:</p>
        
        <div class="university-grid">
            <div class="university-card">
                <h4>LAUTECH</h4>
                <p>Ladoke Akintola University</p>
            </div>
            <div class="university-card">
                <h4>UI</h4>
                <p>University of Ibadan</p>
            </div>
            <div class="university-card">
                <h4>UNILAG</h4>
                <p>University of Lagos</p>
            </div>
            <div class="university-card">
                <h4>OAU</h4>
                <p>Obafemi Awolowo University</p>
            </div>
            <div class="university-card">
                <h4>FUTA</h4>
                <p>Federal University of Technology, Akure</p>
            </div>
            <div class="university-card">
                <h4>FUNAAB</h4>
                <p>Federal University of Agriculture</p>
            </div>
            <div class="university-card">
                <h4>LASU</h4>
                <p>Lagos State University</p>
            </div>
            <div class="university-card">
                <h4>Others</h4>
                <p>All Federal & State Universities</p>
            </div>
        </div>
    </div>

    <!-- What's Included -->
    <div class="path-section">
        <h2><i class='bx bx-package'></i> What's Included</h2>
        
        <div class="feature-grid">
            <div class="feature-card">
                <h3><i class='bx bx-file-find'></i> University-Specific Materials</h3>
                <p>Customized study materials based on your chosen university's exam pattern and past questions</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-desktop'></i> CBT Practice</h3>
                <p>Computer-based test simulation for universities that use digital screening</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-user-voice'></i> Interview Prep</h3>
                <p>For universities with oral interviews, we prepare you with mock sessions</p>
            </div>
            
            <div class="feature-card">
                <h3><i class='bx bx-support'></i> Admission Guidance</h3>
                <p>Support with document submission, deadline tracking, and admission processes</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="path-section cta-section">
        <h3>Ready to Secure Your University Admission?</h3>
        <p>Don't let Post-UTME screening stand between you and your dream university.</p>
        <a href="<?php echo app_url('register-new.php?path=postutme'); ?>" class="btn-cta">
            Start Post-UTME Prep <i class='bx bx-right-arrow-alt'></i>
        </a>
        <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">Fast-track preparation available for urgent cases</p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

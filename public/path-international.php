<?php
/**
 * Path: International Studies
 * Personalized landing page for students interested in studying abroad
 */
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'abroad');
$match_score = intval($_GET['match'] ?? 0);

// SEO Meta Tags
$pageTitle = 'International Studies - Your Personalized Path | High Q Tutorial';
$pageDescription = 'SAT, IELTS, TOEFL preparation for studying abroad. Expert coaching for international university admissions.';
$pageKeywords = 'SAT preparation Nigeria, IELTS coaching, TOEFL training, study abroad, international university admission';

include __DIR__ . '/includes/header.php';
?>

<style>
/* Path Page Styles - International Theme (Magenta) */
:root {
    --path-primary: #c026d3;
    --path-secondary: #a21caf;
    --path-light: #fdf4ff;
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

.exam-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.exam-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.exam-card:hover {
    border-color: var(--hq-yellow, #ffd600);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.exam-card h3 {
    color: var(--hq-navy, #0b1a2c);
    font-size: 1.25rem;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.exam-card h3 i {
    color: var(--hq-yellow, #ffd600);
}

.exam-card .description {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.exam-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.exam-card li {
    padding: 0.35rem 0;
    color: #4b5563;
    font-size: 0.9rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.exam-card li i {
    color: #10b981;
    margin-top: 3px;
    flex-shrink: 0;
}

.destination-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.destination-card {
    background: var(--path-light);
    padding: 1.25rem;
    border-radius: 10px;
    text-align: center;
    transition: all 0.3s ease;
}

.destination-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.destination-card .flag {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.destination-card h4 {
    color: var(--hq-navy, #0b1a2c);
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
}

.destination-card p {
    margin: 0;
    font-size: 0.85rem;
    color: #6b7280;
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
        <h1><i class='bx bx-world'></i> Your International Path</h1>
        <p class="subtitle">Prepare for global education opportunities</p>
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
        <p>Your quiz results indicate you're interested in <strong>studying abroad</strong>. Our international exam preparation program equips you with the scores and skills needed to gain admission to universities in the USA, UK, Canada, and other countries.</p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="number">1400+</span>
                <span class="label">Average SAT Score</span>
            </div>
            <div class="stat-card">
                <span class="number">7.5+</span>
                <span class="label">Average IELTS Band</span>
            </div>
            <div class="stat-card">
                <span class="number">50+</span>
                <span class="label">Students Abroad</span>
            </div>
            <div class="stat-card">
                <span class="number">Expert</span>
                <span class="label">Certified Tutors</span>
            </div>
        </div>
    </div>

    <!-- Exam Preparation -->
    <div class="path-section">
        <h2><i class='bx bx-certification'></i> Exam Preparation Options</h2>
        
        <div class="exam-grid">
            <div class="exam-card">
                <h3><i class='bx bx-book-content'></i> SAT Preparation</h3>
                <p class="description">For US university admissions</p>
                <ul>
                    <li><i class='bx bx-check'></i> Evidence-Based Reading & Writing</li>
                    <li><i class='bx bx-check'></i> Mathematics (Calculator & No-Calc)</li>
                    <li><i class='bx bx-check'></i> Essay Writing (Optional)</li>
                    <li><i class='bx bx-check'></i> Full-length Practice Tests</li>
                </ul>
            </div>
            
            <div class="exam-card">
                <h3><i class='bx bx-conversation'></i> IELTS Preparation</h3>
                <p class="description">For UK/Australia/Canada admissions</p>
                <ul>
                    <li><i class='bx bx-check'></i> Listening Skills</li>
                    <li><i class='bx bx-check'></i> Reading Comprehension</li>
                    <li><i class='bx bx-check'></i> Academic Writing (Task 1 & 2)</li>
                    <li><i class='bx bx-check'></i> Speaking Practice with Feedback</li>
                </ul>
            </div>
            
            <div class="exam-card">
                <h3><i class='bx bx-microphone'></i> TOEFL Preparation</h3>
                <p class="description">For US/Canada university admissions</p>
                <ul>
                    <li><i class='bx bx-check'></i> Integrated Reading/Listening</li>
                    <li><i class='bx bx-check'></i> Academic Speaking</li>
                    <li><i class='bx bx-check'></i> Essay Writing</li>
                    <li><i class='bx bx-check'></i> iBT Computer Practice</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Popular Destinations -->
    <div class="path-section">
        <h2><i class='bx bx-map-pin'></i> Popular Study Destinations</h2>
        <p>Our students have gained admission to universities in:</p>
        
        <div class="destination-grid">
            <div class="destination-card">
                <div class="flag">🇺🇸</div>
                <h4>United States</h4>
                <p>Ivy League & State Universities</p>
            </div>
            <div class="destination-card">
                <div class="flag">🇬🇧</div>
                <h4>United Kingdom</h4>
                <p>Russell Group Universities</p>
            </div>
            <div class="destination-card">
                <div class="flag">🇨🇦</div>
                <h4>Canada</h4>
                <p>Top Canadian Universities</p>
            </div>
            <div class="destination-card">
                <div class="flag">🇦🇺</div>
                <h4>Australia</h4>
                <p>Group of Eight Universities</p>
            </div>
            <div class="destination-card">
                <div class="flag">🇩🇪</div>
                <h4>Germany</h4>
                <p>TU9 Universities</p>
            </div>
            <div class="destination-card">
                <div class="flag">🌍</div>
                <h4>Others</h4>
                <p>Europe, Asia & More</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="path-section cta-section">
        <h3>Ready to Study Abroad?</h3>
        <p>Begin your journey to international education with our expert preparation programs.</p>
        <a href="<?php echo app_url('register-new.php?path=international'); ?>" class="btn-cta">
            Start International Prep <i class='bx bx-right-arrow-alt'></i>
        </a>
        <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">Free consultation on study abroad options included</p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

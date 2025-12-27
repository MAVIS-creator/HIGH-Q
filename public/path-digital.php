<?php
/**
 * Path: Digital Skills
 * Personalized landing page for students who match the Digital Skills path
 */
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$goal = htmlspecialchars($_GET['goal'] ?? 'career');
$match_score = intval($_GET['match'] ?? 0);

// SEO Meta Tags
$pageTitle = 'Digital Skills & Tech Training - Your Personalized Path | High Q Tutorial';
$pageDescription = 'Hands-on digital skills training. Learn practical tech skills like coding, graphic design, and Microsoft Office with project-based learning.';
$pageKeywords = 'digital skills, coding, graphic design, Excel, web development, tech training Nigeria';

include __DIR__ . '/includes/header.php';
?>

<style>
/* Path Page Styles - Digital Skills Theme */
:root {
    --path-primary: #2563eb;
    --path-secondary: #1d4ed8;
    --path-light: #eff6ff;
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

.track-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.track-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.track-card:hover {
    border-color: var(--hq-yellow, #ffd600);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.track-card h3 {
    color: var(--hq-navy, #0b1a2c);
    font-size: 1.25rem;
    margin: 0 0 0.75rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.track-card h3 i {
    color: var(--hq-yellow, #ffd600);
}

.track-card .duration {
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

.track-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.track-card li {
    padding: 0.4rem 0;
    color: #4b5563;
    font-size: 0.95rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.track-card li i {
    color: #10b981;
    margin-top: 3px;
    flex-shrink: 0;
}

.career-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

.career-list li {
    background: #f9fafb;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border-left: 3px solid var(--hq-yellow, #ffd600);
    color: #374151;
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
        <h1><i class='bx bx-laptop'></i> Your Digital Skills Path</h1>
        <p class="subtitle">Master modern technology and launch your tech career</p>
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
        <h2><i class='bx bx-code-alt'></i> Career-Ready Tech Skills</h2>
        <p>Your quiz indicates a <strong>career and hands-on learning focus</strong>. Our Digital Skills program is perfect for building practical technology abilities that employers value. Learn from real-world projects and gain certifications that boost your career prospects.</p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="number">8+</span>
                <span class="label">Tech Courses</span>
            </div>
            <div class="stat-card">
                <span class="number">4-10</span>
                <span class="label">Weeks Per Course</span>
            </div>
            <div class="stat-card">
                <span class="number">100%</span>
                <span class="label">Project-Based</span>
            </div>
            <div class="stat-card">
                <span class="number">95%</span>
                <span class="label">Placement Rate</span>
            </div>
        </div>
    </div>

    <!-- Available Tracks -->
    <div class="path-section">
        <h2><i class='bx bx-book-reader'></i> Available Learning Tracks</h2>
        
        <div class="track-grid">
            <div class="track-card">
                <h3><i class='bx bx-desktop'></i> Microsoft Office Suite</h3>
                <div class="duration">4-6 weeks • Beginner Friendly</div>
                <ul>
                    <li><i class='bx bx-check'></i> Word Processing & Document Design</li>
                    <li><i class='bx bx-check'></i> Excel Data Analysis & Formulas</li>
                    <li><i class='bx bx-check'></i> PowerPoint Presentations</li>
                    <li><i class='bx bx-check'></i> Email & Calendar Management</li>
                </ul>
            </div>
            
            <div class="track-card">
                <h3><i class='bx bx-palette'></i> Graphic Design</h3>
                <div class="duration">6-8 weeks • Creative Focus</div>
                <ul>
                    <li><i class='bx bx-check'></i> CorelDRAW Mastery</li>
                    <li><i class='bx bx-check'></i> Adobe Photoshop Basics</li>
                    <li><i class='bx bx-check'></i> Logo & Brand Design</li>
                    <li><i class='bx bx-check'></i> Social Media Graphics</li>
                </ul>
            </div>
            
            <div class="track-card">
                <h3><i class='bx bx-code-block'></i> Web Development</h3>
                <div class="duration">8-10 weeks • High Demand</div>
                <ul>
                    <li><i class='bx bx-check'></i> HTML5 & CSS3 Fundamentals</li>
                    <li><i class='bx bx-check'></i> JavaScript Programming</li>
                    <li><i class='bx bx-check'></i> Responsive Website Design</li>
                    <li><i class='bx bx-check'></i> Portfolio Projects</li>
                </ul>
            </div>
            
            <div class="track-card">
                <h3><i class='bx bx-line-chart'></i> Data & Analytics</h3>
                <div class="duration">6-8 weeks • Business Skills</div>
                <ul>
                    <li><i class='bx bx-check'></i> Advanced Excel Functions</li>
                    <li><i class='bx bx-check'></i> Data Visualization</li>
                    <li><i class='bx bx-check'></i> Basic SQL Queries</li>
                    <li><i class='bx bx-check'></i> Business Reporting</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Career Outcomes -->
    <div class="path-section">
        <h2><i class='bx bx-briefcase'></i> Career Outcomes</h2>
        <p>Our Digital Skills graduates have successfully transitioned into various tech roles:</p>
        
        <ul class="career-list">
            <li>Office Administrators</li>
            <li>Graphic Designers</li>
            <li>Web Developers</li>
            <li>Data Analysts</li>
            <li>Content Creators</li>
            <li>Virtual Assistants</li>
            <li>IT Support Specialists</li>
            <li>Digital Marketing Specialists</li>
        </ul>
    </div>

    <!-- CTA Section -->
    <div class="path-section cta-section">
        <h3>Ready to Launch Your Tech Career?</h3>
        <p>Start with any track and build the skills employers want.</p>
        <a href="<?php echo app_url('register-new.php?path=digital'); ?>" class="btn-cta">
            Choose Your Tech Track <i class='bx bx-right-arrow-alt'></i>
        </a>
        <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">First course includes free career counseling session</p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

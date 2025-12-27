<?php
// public/register-new.php - New Universal Registration Wizard
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';

$pageTitle = 'Student Registration - HQ Academy';
$csrf = generateToken('registration_wizard');

// Determine current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$programType = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';

// Valid program types
$validTypes = ['jamb', 'waec', 'postutme', 'digital', 'international'];
if ($step > 1 && !in_array($programType, $validTypes)) {
    $step = 1; // Reset to step 1 if invalid type
}

// Load site settings for sidebar details (bank/contact)
$siteSettings = [];
try {
    $stmtS = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
    $siteSettings = $stmtS->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { $siteSettings = []; }

require_once 'includes/header.php';
?>

<style>
/* Registration Wizard Styles */
.wizard-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

.wizard-progress {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    position: relative;
}

.wizard-progress::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 3px;
    background: #e5e7eb;
    z-index: -1;
}

.progress-step {
    flex: 1;
    text-align: center;
    position: relative;
}

.progress-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    border: 3px solid #e5e7eb;
    margin: 0 auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #9ca3af;
    transition: all 0.3s ease;
}

.progress-step.active .progress-circle {
    background: #ffd600;
    border-color: #ffd600;
    color: #0b1a2c;
}

.progress-step.completed .progress-circle {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.progress-step.completed .progress-circle::before {
    content: '✓';
    font-size: 20px;
}

.progress-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #6b7280;
}

.progress-step.active .progress-label {
    color: #0b1a2c;
}

.wizard-content {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.program-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.program-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: block;
}

.program-card:hover {
    border-color: #ffd600;
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.program-icon {
    font-size: 48px;
    margin-bottom: 15px;
    color: #ffd600;
}

.program-title {
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 8px;
    color: #0b1a2c;
}

.program-desc {
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.5;
}

.wizard-intro {
    text-align: center;
    margin-bottom: 30px;
}

.wizard-intro h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #0b1a2c;
    margin-bottom: 10px;
}

.wizard-intro p {
    font-size: 1.1rem;
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .wizard-content {
        padding: 24px;
    }

    .program-grid {
        grid-template-columns: 1fr;
    }

    .wizard-progress {
        margin-bottom: 30px;
    }

    .progress-label {
        font-size: 0.75rem;
    }
}
</style>

<!-- Hero (consistent with legacy register.php) -->
<section class="about-hero">
    <div class="about-hero-overlay"></div>
    <div class="container about-hero-inner">
        <h1>Register with HIGH Q Academy</h1>
        <p class="lead">Start your journey towards academic excellence. Choose a program and complete your registration.</p>
    </div>
</section>

<div class="container register-layout">
    <main class="register-main">
    <div class="wizard-container">
        <!-- Progress Bar -->
        <div class="wizard-progress">
            <div class="progress-step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                <div class="progress-circle"><?= $step > 1 ? '' : '1' ?></div>
                <div class="progress-label">Choose Program</div>
            </div>
            <div class="progress-step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
                <div class="progress-circle"><?= $step > 2 ? '' : '2' ?></div>
                <div class="progress-label">Your Information</div>
            </div>
            <div class="progress-step <?= $step >= 3 ? 'active' : '' ?>">
                <div class="progress-circle">3</div>
                <div class="progress-label">Payment</div>
            </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
            <?php if ($step === 1): ?>
                <!-- Step 1: Choose Program -->
                <div class="wizard-intro">
                    <h1>Find Your <span style="color: #ffd600;">Path</span></h1>
                    <p>Select the program that matches your educational goals. We'll guide you through the registration process.</p>
                </div>

                <div class="program-grid">
                    <a href="?step=2&type=jamb" class="program-card">
                        <div class="program-icon"><i class='bx bxs-graduation'></i></div>
                        <div class="program-title">JAMB/UTME</div>
                        <div class="program-desc">Comprehensive preparation for JAMB and university entrance</div>
                    </a>

                    <a href="?step=2&type=waec" class="program-card">
                        <div class="program-icon"><i class='bx bxs-book-open'></i></div>
                        <div class="program-title">WAEC/NECO/GCE</div>
                        <div class="program-desc">O-Level exam preparation and tutoring</div>
                    </a>

                    <a href="?step=2&type=postutme" class="program-card">
                        <div class="program-icon"><i class='bx bxs-school'></i></div>
                        <div class="program-title">Post-UTME</div>
                        <div class="program-desc">University screening exam preparation</div>
                    </a>

                    <a href="?step=2&type=digital" class="program-card">
                        <div class="program-icon"><i class='bx bxs-devices'></i></div>
                        <div class="program-title">Digital Skills</div>
                        <div class="program-desc">Web development, cybersecurity, and tech training</div>
                    </a>

                    <a href="?step=2&type=international" class="program-card">
                        <div class="program-icon"><i class='bx bxs-world'></i></div>
                        <div class="program-title">International Programs</div>
                        <div class="program-desc">SAT, IELTS, TOEFL, JUPEB preparation</div>
                    </a>
                </div>

            <?php elseif ($step === 2): ?>
                <!-- Step 2: Program-specific form -->
                <?php
                switch ($programType) {
                    case 'jamb':
                        include __DIR__ . '/forms/jamb-form.php';
                        break;
                    case 'waec':
                        include __DIR__ . '/forms/waec-form.php';
                        break;
                    case 'postutme':
                        include __DIR__ . '/forms/postutme-form.php';
                        break;
                    case 'digital':
                        include __DIR__ . '/forms/digital-form.php';
                        break;
                    case 'international':
                        include __DIR__ . '/forms/international-form.php';
                        break;
                    default:
                        echo '<p class="text-danger">Invalid program type selected.</p>';
                        echo '<a href="?step=1" class="btn btn-primary">Back to Program Selection</a>';
                }
                ?>

            <?php endif; ?>
        </div>
    </div>
    </main>

    <!-- Right Sidebar (mirrors legacy register.php) -->
    <aside class="register-sidebar hq-aside-target">
        <div class="sidebar-card admission-box">
            <h4>Admission Requirements</h4>
            <hr>
            <ul style="margin:8px 0;padding-left:18px;color:#666;font-size:13px">
                <li>Completed O'Level certificate (for JAMB/Post‑UTME)</li>
                <li>Valid identification document</li>
                <li>Passport photograph (2 copies)</li>
                <li>Registration fee payment</li>
                <li>Commitment to academic excellence</li>
            </ul>
        </div>

        <div class="sidebar-card payment-box">
            <h4>Payment Options</h4>
            <div class="payment-method" data-method="bank">
                <strong>Bank Transfer</strong>
                <p>Account Name: <?= htmlspecialchars($siteSettings['bank_account_name'] ?? 'High Q Solid Academy Limited') ?><br>
                Bank: <?= htmlspecialchars($siteSettings['bank_name'] ?? '[Bank Name]') ?><br>
                Account Number: <?= htmlspecialchars($siteSettings['bank_account_number'] ?? '[Account Number]') ?></p>
            </div>
            <div class="payment-method" data-method="cash">
                <strong>Cash Payment</strong>
                <p>Visit our office locations<br>8 Pineapple Avenue, Aiyetoro, Maya<br>Shop 18, World Star Complex, Aiyetoro</p>
            </div>
            <div class="payment-method" data-method="online" id="payment-method-online">
                <strong>Online Payment</strong>
                <p>Secure online payment portal. Credit/Debit card accepted.</p>
            </div>
        </div>

        <div class="sidebar-card help-box">
            <h4>Need Help?</h4>
            <p><strong>Call Us</strong><br><?= htmlspecialchars($siteSettings['contact_phone'] ?? '0807 208 8794') ?></p>
            <p><strong>Email Us</strong><br><?= htmlspecialchars($siteSettings['contact_email'] ?? 'info@hqacademy.com') ?></p>
            <p><strong>Visit Us</strong><br><?= nl2br(htmlspecialchars($siteSettings['contact_address'] ?? "8 Pineapple Avenue, Aiyetoro\nMaya, Ikorodu")) ?></p>
        </div>

        <div class="sidebar-card why-box" id="whyChooseUs">
            <h4>Why Choose Us?</h4>
            <div class="why-stats">
                <div class="stat">
                    <div class="icon"><i class="bx bx-trophy"></i></div>
                    <div class="stat-body">
                        <strong>305</strong>
                        <span>Highest JAMB Score 2025</span>
                    </div>
                </div>
                <div class="stat">
                    <div class="icon"><i class="bx bx-group"></i></div>
                    <div class="stat-body">
                        <strong>1000+</strong>
                        <span>Students Trained</span>
                    </div>
                </div>
                <div class="stat">
                    <div class="icon"><i class="bx bx-bar-chart"></i></div>
                    <div class="stat-body">
                        <strong>99%</strong>
                        <span>Success Rate</span>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>

<!-- What Happens Next? (from legacy register.php) -->
<section class="next-section">
    <div class="container">
        <div class="ceo-heading ceo-heading--center">
            <h2>What Happens <span class="highlight">Next?</span></h2>
            <p class="ceo-subtext">After submitting your registration, here's what you can expect from us.</p>
        </div>

        <div class="achievements-grid">
            <div class="next-stat yellow">
                <div class="next-icon"><i class="bx bx-check-circle"></i></div>
                <strong>1. Confirmation</strong>
                <span>You'll receive an email confirmation within 1 hour and a call from our team within 24 hours.</span>
            </div>

            <div class="next-stat yellow">
                <div class="next-icon"><i class="bx bx-book-open"></i></div>
                <strong>2. Assessment</strong>
                <span>We'll schedule a brief assessment to understand your current level and customize your learning path.</span>
            </div>

            <div class="next-stat red">
                <div class="next-icon"><i class="bx bx-rocket"></i></div>
                <strong>3. Start Learning</strong>
                <span>Begin your journey with our expert tutors and join the ranks of our successful students.</span>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

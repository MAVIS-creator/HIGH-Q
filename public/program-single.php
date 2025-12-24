<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$slug = trim($_GET['slug'] ?? '');
$program = null;
$featureLines = [];

$staticPrograms = [
    'ssce-gce-exams' => [
        'title' => 'SSCE & GCE Exams',
        'description' => "Complete preparation for West African Examinations Council (WAEC) and National Examination Council (NECO) exams. Our structured program provides intensive coaching for school candidates sitting for May/June examinations and private candidates. We also offer NABTEB preparation and specialized training for WAEC GCE and NECO GCE candidates, ensuring you're ready for every assessment.",
        'duration' => '8-24 weeks (exam-calendar aligned)',
        'highlight_badge' => 'Exam-focused coaching',
        'features' => [
            'Comprehensive past questions practice',
            'Timed mock exams under real exam conditions',
            'Subject-specific intensive coaching',
            'Individual performance tracking and feedback',
            'Exam technique and time management strategies',
            'WAEC & NECO (School Candidates - May/June)',
            'WAEC GCE & NECO GCE (Private Candidates)',
            'NABTEB technical examination prep',
        ],
    ],
    'jamb-university-admission' => [
        'title' => 'JAMB & University Admission',
        'description' => "Navigate your path to Nigerian university admission with our comprehensive JAMB and Post-UTME program. Our specialized CBT training covers the full JAMB/UTME syllabus with practice tests that simulate the actual examination environment. We guide you through registration, test preparation, and Post-UTME university screening preparation to maximize your admission chances.",
        'duration' => '6-12 weeks (CBT-focused)',
        'highlight_badge' => 'CBT-driven training',
        'features' => [
            'Full JAMB/UTME syllabus coverage',
            'Computer-Based Test (CBT) simulations',
            'Adaptive practice tests tailored to your level',
            'Real-time performance analytics',
            'Subject coaches for weak areas',
            'JAMB / UTME (Registration & Training)',
            'Post-UTME (University screening prep)',
            'University admission guidance and strategies',
        ],
    ],
    'advanced-international-studies' => [
        'title' => 'Advanced & International Studies',
        'description' => "Prepare for global academic opportunities with our advanced programs. Whether you're pursuing Direct Entry to Nigerian universities through JUPEB or A-Levels, or seeking international qualifications like SAT, TOEFL, IELTS, GMAT, or GRE for study abroad, we provide comprehensive training to meet international academic standards.",
        'duration' => '10-24 weeks (modular)',
        'highlight_badge' => 'International pathways',
        'features' => [
            'SAT, TOEFL, and IELTS coaching',
            'GMAT and GRE preparation',
            'JUPEB and A-Levels training',
            'IGCSE international O-Level prep',
            'University application guidance',
            'Visa and study abroad consultation',
            'Customized modules by qualification',
            'Test-taking strategies and practice materials',
        ],
    ],
    'remedial-foundation-tutorials' => [
        'title' => 'Remedial & Foundation Tutorials',
        'description' => "Academic foundation and remedial programs for students from Primary through Senior Secondary School levels. We provide structured grooming to strengthen foundational knowledge, boost exam readiness, and ensure continuous academic improvement. Small-group sessions and individualized attention help each student succeed at their level.",
        'duration' => '4-16 weeks (modular by class)',
        'highlight_badge' => 'Small-group coaching',
        'features' => [
            'Primary School common entrance preparation',
            'Foundation classes for all subjects',
            'Junior Secondary: BECE (Junior WAEC) Prep',
            'Senior School after-school tutorials (SSS 1-3)',
            'Subject specialization and intensive support',
            'Continuous assessment and progress reports',
            'Small class sizes for personalized attention',
            'Homework support and exam coaching',
        ],
    ],
    'digital-skills-tech' => [
        'title' => 'Digital Skills & Tech',
        'description' => "Master practical technology skills essential for the modern workplace. Our hands-on programs cover productivity tools, design software, and programming fundamentals. Whether you're starting your tech journey or upgrading existing skills, we provide project-based learning that translates directly to real-world applications.",
        'duration' => '4-10 weeks (hands-on)',
        'highlight_badge' => 'Project-based learning',
        'features' => [
            'Microsoft Word and Excel mastery',
            'Graphic design fundamentals and Adobe basics',
            'Programming basics (Python, JavaScript intro)',
            'Data analysis and spreadsheet modeling',
            'Web design and digital content creation',
            'Portfolio-building projects',
            'Industry-standard tools and software',
            'Certificate of completion upon success',
        ],
    ],
    'professional-services' => [
        'title' => 'Professional Services',
        'description' => "Dedicated professional services to support your educational and career goals. From university application assistance to visa processing consultation, educational counseling, and document preparation, our expert advisors provide personalized support for your unique needs.",
        'duration' => 'On-demand (book a slot)',
        'highlight_badge' => 'Advisory & processing',
        'features' => [
            'Educational consultation and career counseling',
            'University application form completion',
            'Visa application guidance and document prep',
            'Personal statement and essay writing support',
            'Interview preparation coaching',
            'Data entry and document processing services',
            'Admission requirement analysis',
            'Follow-up support throughout your journey',
        ],
    ],
];

if ($slug !== '') {
    try {
        $stmt = $pdo->prepare("SELECT c.id, c.title, c.slug, c.description, c.price, c.duration, c.icon, c.highlight_badge, GROUP_CONCAT(cf.feature_text ORDER BY cf.position SEPARATOR '\n') AS features_list FROM courses c LEFT JOIN course_features cf ON cf.course_id = c.id WHERE c.slug = ? AND c.is_active = 1 GROUP BY c.id LIMIT 1");
        $stmt->execute([$slug]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($program) {
            $featureLines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $program['features_list'] ?? ''))));
        }
    } catch (Throwable $_) {
        $program = null;
    }

    // Fallback to static definitions when not found in DB
    if (!$program && isset($staticPrograms[$slug])) {
        $program = $staticPrograms[$slug];
        $featureLines = $staticPrograms[$slug]['features'];
    }
}

include __DIR__ . '/includes/header.php';

if (!$program): ?>
  <div class="container" style="padding: 80px 0; text-align: center;">
    <h2 style="font-size: 2rem; color: var(--hq-black); margin-bottom: 16px;">Program Not Found</h2>
    <p style="color: var(--hq-gray); font-size: 1.1rem; margin-bottom: 28px;">The program you're looking for was not found. Please browse all our programs.</p>
    <a href="programs.php" style="display: inline-block; padding: 14px 32px; background: var(--hq-blue-white); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='var(--hq-yellow)';" onmouseout="this.style.background='var(--hq-blue-white)';">Browse All Programs</a>
  </div>
<?php 
include __DIR__ . '/includes/footer.php';
exit; 
endif; ?>

<style>
  .program-detail-page {
    padding: 64px 0;
    background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
  }

  .program-breadcrumb {
    display: inline-block;
    margin-bottom: 28px;
  }

  .program-breadcrumb a {
    color: var(--hq-blue-white);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    font-size: 0.95rem;
    padding: 8px 12px;
    border-radius: 6px;
  }

  .program-breadcrumb a:hover {
    color: var(--hq-yellow);
    background: rgba(0, 0, 0, 0.05);
  }

  .program-hero {
    margin-bottom: 48px;
    padding: 48px;
    background: linear-gradient(135deg, var(--hq-blue-white) 0%, var(--hq-yellow) 100%);
    border-radius: 16px;
    color: #ffffff;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
  }

  .program-hero h1 {
    font-size: clamp(2rem, 2.6vw, 2.9rem);
    margin: 0 0 16px;
    font-weight: 800;
    line-height: 1.2;
  }

  .program-hero p {
    font-size: 1.05rem;
    margin: 0;
    opacity: 0.95;
    max-width: 820px;
    line-height: 1.6;
  }

  .program-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 36px;
    align-items: start;
    margin-bottom: 56px;
  }

  .program-card {
    margin-bottom: 28px;
    padding: 32px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e6e9f0;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
  }

  .program-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
  }

  .program-card h3 {
    font-size: 1.5rem;
    margin: 0 0 18px;
    color: var(--hq-black);
    font-weight: 700;
  }

  .program-card--accent-blue {
    border-left: 5px solid var(--hq-blue-white);
  }

  .program-card--soft-yellow {
    background: linear-gradient(135deg, var(--hq-yellow-pale) 0%, #fffbf0 100%);
    border-left: 5px solid var(--hq-yellow);
    box-shadow: 0 6px 18px rgba(245, 185, 4, 0.12);
  }

  .program-card p,
  .program-card li {
    color: var(--hq-gray);
    line-height: 1.75;
    font-size: 1rem;
  }

  .program-feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .program-feature-list li {
    padding: 14px 0 14px 36px;
    position: relative;
    border-bottom: 1px solid #f1f3f7;
  }

  .program-feature-list li:last-child {
    border-bottom: none;
  }

  .program-feature-list li span {
    position: absolute;
    left: 0;
    color: var(--hq-yellow);
    font-weight: 700;
    font-size: 1.2rem;
  }

  .quick-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
    margin-bottom: 22px;
  }

  .quick-card {
    padding: 18px;
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #f1f3f7;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  }

  .quick-card .label {
    margin: 0 0 8px;
    color: var(--hq-gray);
    font-size: 0.82rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.4px;
  }

  .quick-card .value {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--hq-blue-white);
  }

  .program-sidebar {
    position: sticky;
    top: 16px;
    height: fit-content;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .program-cta {
    padding: 28px 22px;
    background: linear-gradient(135deg, var(--hq-blue-white) 0%, var(--hq-yellow) 100%);
    border-radius: 12px;
    color: #ffffff;
    box-shadow: 0 12px 32px rgba(57, 58, 147, 0.25);
    text-align: center;
  }

  .program-cta h4 {
    margin: 0 0 10px;
    font-size: 1.15rem;
    font-weight: 700;
  }

  .program-cta p {
    margin: 0 0 18px;
    font-size: 0.92rem;
    opacity: 0.95;
    line-height: 1.6;
  }

  .program-enroll-btn {
    display: block;
    padding: 14px 0;
    background: var(--hq-yellow);
    color: var(--hq-black);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1rem;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
  }

  .program-enroll-btn:hover {
    background: #f4aa01;
    transform: translateY(-2px);
  }

  .info-note {
    padding: 14px 16px;
    background: rgba(245, 185, 4, 0.08);
    border-left: 3px solid var(--hq-yellow);
    border-radius: 8px;
    margin-bottom: 14px;
    color: var(--hq-gray);
    font-size: 0.92rem;
  }

  @media (max-width: 991px) {
    .program-grid {
      grid-template-columns: 1fr;
    }

    .program-sidebar {
      position: relative;
      top: 0;
    }
  }
</style>

<section class="program-detail-page">
  <div class="container">
    <a href="programs.php" class="program-breadcrumb"><i class="bx bx-chevron-left"></i> Back to Programs</a>

    <div class="program-hero">
      <h1><?= htmlspecialchars($program['title']) ?></h1>
      <p><?= htmlspecialchars($program['description']) ?></p>
    </div>

    <div class="program-grid">
      <div>
        <div class="quick-info-grid">
          <div class="quick-card">
            <p class="label">Duration</p>
            <p class="value"><?= htmlspecialchars($program['duration'] ?: 'Flexible') ?></p>
          </div>
          <div class="quick-card">
            <p class="label">Registration Fee</p>
            <p class="value">₦1,500</p>
          </div>
          <div class="quick-card">
            <p class="label">Form Fee</p>
            <p class="value">₦1,000</p>
          </div>
          <?php if (!empty($program['highlight_badge'])): ?>
            <div class="quick-card">
              <p class="label">Badge</p>
              <p class="value" style="font-size: 1rem; color: var(--hq-gray);"><?= htmlspecialchars($program['highlight_badge']) ?></p>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($featureLines)): ?>
          <div class="program-card program-card--accent-blue">
            <h3><i class="bx bx-check-circle" style="color: var(--hq-yellow); margin-right: 10px;"></i>What You'll Learn</h3>
            <ul class="program-feature-list">
              <?php foreach ($featureLines as $line): if (trim($line) === '') continue; ?>
                <li><span>✓</span> <?= htmlspecialchars($line) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="program-card program-card--soft-yellow">
          <h3>Why This Program?</h3>
          <p>This program is designed with your specific learning goals in mind. Our experienced instructors combine proven teaching methods with personalized attention to ensure you achieve the best possible results. Whether you're preparing for exams, building career skills, or pursuing higher education, we have the expertise and resources to support your success.</p>
        </div>
      </div>

      <aside class="program-sidebar">
        <div class="program-cta">
          <h4>Ready to Enroll?</h4>
          <p>Take the next step toward your academic and career goals today.</p>
          <a href="register.php?ref=<?php echo htmlspecialchars(rawurlencode($program['slug'] ?? $program['path'] ?? 'program')); ?>" class="program-enroll-btn">Enroll Now</a>
        </div>

        <div class="info-note">
          <strong>Questions?</strong><br>
          Contact us via the <a href="contact.php" style="color: var(--hq-blue-white); font-weight: 600;">contact form</a> or call <strong>0807 208 8794</strong> to discuss your needs.
        </div>
      </aside>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

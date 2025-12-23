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
<?php else: ?>

<style>
.single-program {
  padding: 64px 0;
  background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
}
.single-card {
  max-width: 960px;
  margin: 0 auto;
  background: #fff;
  border: 1px solid #e6e9f0;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 12px 32px rgba(0,0,0,0.08);
}
.single-card h1 { margin-top: 0; font-weight: 800; }
.single-meta { display: flex; gap: 16px; flex-wrap: wrap; margin: 12px 0 20px; color: var(--hq-gray); }
.feature-list { list-style: none; padding: 0; margin: 0; }
.feature-list li { padding: 10px 0 10px 28px; border-bottom: 1px solid #f2f4f8; position: relative; }
.feature-list li:last-child { border-bottom: none; }
.feature-list li::before { content: '✓'; color: var(--hq-yellow); position: absolute; left: 0; top: 10px; font-weight: 700; }
.cta-box { margin-top: 24px; padding: 18px; border-radius: 12px; background: linear-gradient(135deg, var(--hq-blue-white) 0%, var(--hq-yellow) 100%); color: #fff; text-align: center; box-shadow: 0 8px 24px rgba(57,58,147,0.25); }
.cta-box a { display: inline-block; margin-top: 10px; padding: 12px 24px; background: #fff; color: var(--hq-blue-white); border-radius: 8px; font-weight: 700; text-decoration: none; }
</style>

<section class="single-program">
  <div class="container">
    <div class="single-card">
      <a href="programs.php" style="text-decoration:none; color: var(--hq-blue-white); font-weight:700; display:inline-flex; gap:6px; align-items:center; margin-bottom:14px;">
        <i class='bx bx-chevron-left'></i> Back to Programs
      </a>
      <h1><?= htmlspecialchars($program['title']) ?></h1>
      <div class="single-meta">
        <span><i class='bx bx-time-five'></i> <?= htmlspecialchars($program['duration'] ?: 'Flexible') ?></span>
        <span><i class='bx bx-purchase-tag'></i> <?= htmlspecialchars($program['price'] ?: 'Contact us') ?></span>
        <?php if (!empty($program['highlight_badge'])): ?>
          <span><i class='bx bx-star'></i> <?= htmlspecialchars($program['highlight_badge']) ?></span>
        <?php endif; ?>
      </div>
      <p style="color: var(--hq-gray); line-height:1.7; font-size:1.02rem;">
        <?= htmlspecialchars($program['description'] ?: ($program['title'] . ' is designed to help you excel.')) ?>
      </p>

      <?php if (!empty($featureLines)): ?>
        <h3 style="margin-top: 28px;">Key Features</h3>
        <ul class="feature-list">
          <?php foreach ($featureLines as $line): if (trim($line)==='') continue; ?>
            <li><?= htmlspecialchars($line) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <div class="cta-box">
        <h4>Ready to enroll?</h4>
        <p style="margin:6px 0 10px;">Join thousands of successful students today.</p>
        <a href="register.php?ref=<?= rawurlencode($program['slug']) ?>">Enroll Now</a>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

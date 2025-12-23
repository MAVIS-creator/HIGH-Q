<?php
// Simple single program page (uses database slugs)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$slug = trim($_GET['slug'] ?? '');
$program = null;
$featureLines = [];

$staticPrograms = [
    'ssce-gce-exams' => [
        'title' => 'SSCE & GCE Exams',
        'description' => "Registration and intensive coaching for O'Level success.",
        'price' => 'Contact us (Reg fee + Form fee)',
        'duration' => '8-24 weeks (exam-calendar aligned)',
        'highlight_badge' => 'Exam-focused coaching',
        'features' => [
            'WAEC & NECO (School Candidates - May/June)',
            'WAEC GCE & NECO GCE (Private Candidates)',
            'NABTEB (Optional, fits same track)',
            'Intensive drills, past questions, timed practice',
        ],
    ],
    'jamb-university-admission' => [
        'title' => 'JAMB & University Admission',
        'description' => 'Comprehensive JAMB CBT simulations and admission processing.',
        'price' => 'Contact us (Reg fee + Form fee)',
        'duration' => '6-12 weeks (CBT-focused)',
        'highlight_badge' => 'CBT-driven training',
        'features' => [
            'JAMB / UTME (Registration & Training)',
            'CBT Training (core tool for JAMB success)',
            'Post-UTME (University screening prep)',
            'Admission guidance and application support',
        ],
    ],
    'advanced-international-studies' => [
        'title' => 'Advanced & International Studies',
        'description' => 'Global certifications and Direct Entry programs for university admission.',
        'price' => 'Contact us (Reg fee + Form fee)',
        'duration' => '10-24 weeks (modular)',
        'highlight_badge' => 'International pathways',
        'features' => [
            'Study Abroad: SAT, TOEFL, IELTS, GMAT, GRE',
            'Direct Entry: JUPEB, A-Levels (Cambridge/IJMB)',
            "International O'Level: IGCSE",
            'Application strategy and test readiness',
        ],
    ],
    'remedial-foundation-tutorials' => [
        'title' => 'Remedial & Foundation Tutorials',
        'description' => 'Structured academic grooming from Primary 1 to SSS 3.',
        'price' => 'Contact us (Reg fee + Form fee)',
        'duration' => '4-16 weeks (modular by class)',
        'highlight_badge' => 'Small-group coaching',
        'features' => [
            'Junior Secondary: BECE (Junior WAEC) Prep',
            'Primary School: Common Entrance & Foundation',
            'Senior School: After-school subject tutorials (SSS 1-3)',
            'Continuous assessment and progress tracking',
        ],
    ],
    'digital-skills-tech' => [
        'title' => 'Digital Skills & Tech',
        'description' => 'Practical tech skills for the modern workplace.',
        'price' => 'Contact us (Reg fee + Form fee)',
        'duration' => '4-10 weeks (hands-on)',
        'highlight_badge' => 'Project-based learning',
        'features' => [
            'Computer Literacy (Word/Excel)',
            'Graphic Design & Programming',
            'Data Analysis fundamentals',
            'Portfolio-driven, project-based learning',
        ],
    ],
    'professional-services' => [
        'title' => 'Professional Services',
        'description' => 'Expert guidance on admissions and career paths.',
        'price' => 'Contact us (Reg fee + Form fee)',
        'duration' => 'On-demand (book a slot)',
        'highlight_badge' => 'Advisory & processing',
        'features' => [
            'Educational Consulting & Career Counseling',
            'University/Visa Application Assistance',
            'Data Entry & Document Processing',
            'Personalized advisory sessions',
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

<?php
$slug = trim($_GET['slug'] ?? '');

$programs = [
  'jamb-preparation' => [
    'title' => 'JAMB / Post-UTME Preparation',
    'overview' => 'Comprehensive preparation for JAMB and university entrance exams with practice tests, topic mastery and exam techniques.',
    'curriculum' => [
      'English Language fundamentals',
      'Core Mathematics and Problem Solving',
      'Subject-specific modules (Biology, Chemistry, Physics, etc.)',
      'Timed practice tests and past questions',
    ],
    'who' => ['Senior secondary students preparing for JAMB', 'Students seeking university admission through UTME or Post-UTME'],
    'fees' => '₦50,000.00',
    'duration' => '4–6 months',
  ],
  'waec-preparation' => [
    'title' => 'WAEC Preparation',
    'overview' => 'Complete WAEC preparation including theory, practicals, and mock exams to secure high grades.',
    'curriculum' => ['Core subject mastery', 'Practical skill sessions', 'Mock exams and marking'],
    'who' => ['Secondary school students sitting WAEC'],
    'fees' => 'Negotiable (based on package)',
    'duration' => '6–12 months',
  ],
  'neco-preparation' => [
    'title' => 'NECO Preparation',
    'overview' => 'Focused NECO preparation with extensive practice tests and study materials.',
    'curriculum' => ['Complete syllabus coverage', 'Mock exams', 'Exam technique workshops'],
    'who' => ['Students taking NECO examinations'],
    'fees' => 'Negotiable',
    'duration' => '6–12 months',
  ],
  'post-utme' => [
    'title' => 'Post-UTME',
    'overview' => 'University-specific Post-UTME preparation tailored to faculties and institutions.',
    'curriculum' => ['University-specific past questions', 'Interview prep', 'Subject depth training'],
    'who' => ['UTME candidates applying to university'],
    'fees' => 'Varies by institution',
    'duration' => '2–4 months',
  ],
  'special-tutorials' => [
    'title' => 'Special Tutorials',
    'overview' => 'One-on-one or small group intensive tutorials focused on rapid improvement and subject mastery.',
    'curriculum' => ['Personalized lesson plans', 'Targeted revision', 'Homework feedback & guidance'],
    'who' => ['Students needing intensive academic support', 'Learners with tight schedules'],
    'fees' => 'By arrangement',
    'duration' => 'Flexible',
  ],
  'computer-training' => [
    'title' => 'Computer Training',
    'overview' => 'Digital literacy and computer skills training including Office tools, internet usage, and basic programming.',
    'curriculum' => ['MS Office Suite (Word, Excel, PowerPoint)', 'Internet & research skills', 'Introduction to coding'],
    'who' => ['Beginners in digital literacy', 'Students or professionals needing computer skills'],
    'fees' => '₦30,000.00',
    'duration' => '3–6 months',
  ],
];

// Show fallback if slug doesn't exist
if (!array_key_exists($slug, $programs)) {
  include __DIR__ . '/includes/header.php';
  ?>
  <div class="container" style="padding: 48px 0">
    <h2>Program Not Found</h2>
    <p>The program you're looking for was not found. Please <a href="programs.php">browse all programs</a>.</p>
  </div>
  <?php
  include __DIR__ . '/includes/footer.php';
  exit;
}

$p = $programs[$slug];
include __DIR__ . '/includes/header.php';
?>

<section class="program-detail" style="padding: 48px 0;">
  <div class="container">

    <!-- Optional Breadcrumb -->
    <nav style="margin-bottom: 24px;">
      <a href="programs.php" style="color: var(--hq-gray); text-decoration: none;">← Back to Programs</a>
    </nav>

    <div class="ceo-heading">
      <h2><?= htmlspecialchars($p['title']) ?></h2>
    </div>

    <div class="program-detail-grid">
      <div class="program-detail-main">
        <section class="program-section">
          <h3 id="overview">Overview</h3>
          <p><?= htmlspecialchars($p['overview']) ?></p>
        </section>

        <section class="program-section">
          <h3 id="curriculum">Curriculum / Modules</h3>
          <ul>
            <?php foreach ($p['curriculum'] as $item): ?>
              <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </section>

        <section class="program-section">
          <h3 id="who">Who It's For</h3>
          <ul>
            <?php foreach ($p['who'] as $aud): ?>
              <li><?= htmlspecialchars($aud) ?></li>
            <?php endforeach; ?>
          </ul>
        </section>

        <section class="program-section">
          <h3 id="fees">Duration & Fees</h3>
          <p><strong>Duration:</strong> <?= htmlspecialchars($p['duration']) ?></p>
          <p><strong>Fees:</strong> <?= htmlspecialchars($p['fees']) ?></p>
        </section>

        <p style="margin-top: 28px;">
          <a href="register.php?ref=<?= rawurlencode($slug) ?>" class="btn-primary">
            Register for <?= htmlspecialchars($p['title']) ?>
          </a>
        </p>
      </div>
    </div>
  </div>
</section>
 <nav class="back-nav" style="margin-bottom: 24px;">
  <a href="programs.php" class="back-link">
    <i class='bx bx-arrow-back'></i> Back to Programs
  </a>
</nav>

<?php include __DIR__ . '/includes/footer.php'; ?>

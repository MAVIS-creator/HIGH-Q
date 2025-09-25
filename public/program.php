<?php
// Simple program details page. Uses a whitelist map for known slugs and safe fallbacks.
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
    'fees' => '50,000.00',
    'duration' => '4-6 months',
  ],
  'waec-preparation' => [
    'title' => 'WAEC Preparation',
    'overview' => 'Complete WAEC preparation including theory, practicals and mock exams to secure high grades.',
    'curriculum' => ['Core subject mastery', 'Practical skill sessions', 'Mock exams and marking'],
    'who' => ['Secondary school students sitting WAEC'],
    'fees' => 'Negotiable (depends on package)',
    'duration' => '6-12 months',
  ],
  'neco-preparation' => [
    'title' => 'NECO Preparation',
    'overview' => 'Focused NECO preparation with extensive practice tests and study materials.',
    'curriculum' => ['Complete syllabus coverage', 'Mock exams', 'Exam technique workshops'],
    'who' => ['Students taking NECO examinations'],
    'fees' => 'Negotiable',
    'duration' => '6-12 months',
  ],
  'post-utme' => [
    'title' => 'Post-UTME',
    'overview' => 'University-specific Post-UTME preparation tailored to faculties and institutions.',
    'curriculum' => ['University-specific past questions', 'Interview prep', 'Subject depth training'],
    'who' => ['UTME candidates applying to university'],
    'fees' => 'Varies by program',
    'duration' => '2-4 months',
  ],
  'special-tutorials' => [
    'title' => 'Special Tutorials',
    'overview' => 'One-on-one or small group intensive tutorials focused on improvement and mastery.',
    'curriculum' => ['Personalized lesson plans', 'Targeted revision', 'Homework & feedback'],
    'who' => ['Students seeking intensive support'],
    'fees' => 'Per arrangement',
    'duration' => 'Flexible',
  ],
  'computer-training' => [
    'title' => 'Computer Training',
    'overview' => 'Digital literacy and practical computer skills including office suites and beginner programming.',
    'curriculum' => ['MS Office', 'Internet & research skills', 'Intro to programming'],
    'who' => ['Learners seeking practical computer skills'],
    'fees' => '30,000.00',
    'duration' => '3-6 months',
  ],
];

if (!array_key_exists($slug, $programs)) {
  // fallback: show landing list or a not-found message
  include __DIR__ . '/includes/header.php';
  ?>
  <div class="container" style="padding:48px 0">
    <h2>Program not found</h2>
    <p>The program you requested was not found. Please return to the <a href="programs.php">Programs</a> page.</p>
  </div>
  <?php
  include __DIR__ . '/includes/footer.php';
  exit;
}

$p = $programs[$slug];
include __DIR__ . '/includes/header.php';
?>
<section class="program-detail" style="padding:48px 0">
  <div class="container">
    <div class="ceo-heading"><h2><?= htmlspecialchars($p['title']) ?></h2></div>

    <div class="program-detail-grid">
      <div class="program-detail-main">
        <h3 id="overview">Overview</h3>
        <p><?= htmlspecialchars($p['overview']) ?></p>

        <h3 id="curriculum">Curriculum / Modules</h3>
        <ul>
          <?php foreach ($p['curriculum'] as $item): ?>
            <li><?= htmlspecialchars($item) ?></li>
          <?php endforeach; ?>
        </ul>

        <h3 id="who">Who it's for</h3>
        <ul>
          <?php foreach ($p['who'] as $w): ?>
            <li><?= htmlspecialchars($w) ?></li>
          <?php endforeach; ?>
        </ul>

        <h3 id="fees">Duration &amp; Fees</h3>
        <p><strong>Duration:</strong> <?= htmlspecialchars($p['duration']) ?></p>
        <p><strong>Fees:</strong> <?= htmlspecialchars($p['fees']) ?></p>

        <p style="margin-top:18px"><a href="register.php?ref=<?= rawurlencode($slug) ?>" class="btn-primary">Register for <?= htmlspecialchars($p['title']) ?></a></p>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

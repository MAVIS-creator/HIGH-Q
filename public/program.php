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
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <h2 class="display-5 fw-bold mb-4">Program Not Found</h2>
        <p class="lead text-muted mb-4">The program you're looking for was not found. Please <a href="programs.php" class="text-warning text-decoration-none">browse all programs</a>.</p>
      </div>
    </div>
  </div>
  <?php
  include __DIR__ . '/includes/footer.php';
  exit;
}

$p = $programs[$slug];
include __DIR__ . '/includes/header.php';
?>

<section class="py-5">
  <div class="container">
    <!-- Breadcrumb -->
    <nav class="mb-4">
      <a href="programs.php" class="text-decoration-none d-inline-flex align-items-center gap-2 text-muted hover-warning">
        <i class='bx bx-arrow-left'></i> Back to Programs
      </a>
    </nav>

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="text-center mb-5">
          <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($p['title']) ?></h1>
        </div>

        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4 p-md-5">
            <section class="mb-5">
              <h3 class="h4 fw-bold mb-4">Overview</h3>
              <p class="text-muted mb-0"><?= htmlspecialchars($p['overview']) ?></p>
            </section>

            <section class="mb-5">
              <h3 class="h4 fw-bold mb-4">Curriculum / Modules</h3>
              <ul class="list-unstyled mb-0">
                <?php foreach ($p['curriculum'] as $item): ?>
                  <li class="d-flex align-items-center mb-3">
                    <i class='bx bx-check-circle text-warning me-2 fs-5'></i>
                    <span class="text-muted"><?= htmlspecialchars($item) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>

            <section class="mb-5">
              <h3 class="h4 fw-bold mb-4">Who It's For</h3>
              <ul class="list-unstyled mb-0">
                <?php foreach ($p['who'] as $aud): ?>
                  <li class="d-flex align-items-center mb-3">
                    <i class='bx bx-user text-warning me-2 fs-5'></i>
                    <span class="text-muted"><?= htmlspecialchars($aud) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>

            <section class="mb-5">
              <h3 class="h4 fw-bold mb-4">Duration & Fees</h3>
              <div class="row g-4">
                <div class="col-md-6">
                  <div class="p-4 bg-light rounded-3 text-center h-100">
                    <i class='bx bx-time-five text-warning fs-1 mb-3'></i>
                    <h4 class="h5 fw-bold mb-2">Duration</h4>
                    <p class="text-muted mb-0"><?= htmlspecialchars($p['duration']) ?></p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="p-4 bg-light rounded-3 text-center h-100">
                    <i class='bx bx-money text-warning fs-1 mb-3'></i>
                    <h4 class="h5 fw-bold mb-2">Fees</h4>
                    <p class="text-muted mb-0"><?= htmlspecialchars($p['fees']) ?></p>
                  </div>
                </div>
              </div>
            </section>

            <div class="text-center pt-4">
              <a href="register.php?ref=<?= rawurlencode($slug) ?>" class="btn btn-primary btn-lg px-5">
                Register for <?= htmlspecialchars($p['title']) ?>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

</style>
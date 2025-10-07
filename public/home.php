<?php
// Ensure we have a PDO connection available. Header may already include DB; try loading it if not.
$programs = [];
if (!isset($pdo) || !$pdo) {
  $dbPath = __DIR__ . '/config/db.php';
  if (file_exists($dbPath)) {
    try {
      require_once $dbPath;
    } catch (Throwable $e) {
      // ignore - we'll gracefully fall back to empty programs
    }
  }
}

// Fetch up to 6 active courses added by the admin (only if $pdo is available)
if (isset($pdo) && $pdo instanceof PDO) {
  try {
    // include icon, highlight_badge and aggregated features so we can render icons and feature lists
    $stmt = $pdo->prepare("SELECT c.id, c.title, c.slug, c.description, c.duration, c.price, c.icon, c.highlight_badge, GROUP_CONCAT(cf.feature_text SEPARATOR '\n') AS features_list FROM courses c LEFT JOIN course_features cf ON cf.course_id = c.id WHERE c.is_active = 1 GROUP BY c.id ORDER BY c.created_at DESC LIMIT 6");
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    $programs = [];
  }
}
?>

<section class="hero py-5 bg-warning">
  <div class="container">
    <div class="hero-left py-4 py-lg-5">
      <div class="hero-badge d-inline-flex align-items-center gap-2 mb-4"><i class='bx bxs-star'></i> Nigeria's Premier Tutorial Academy</div>
      <h1 class="display-4 fw-bold mb-3">Excellence in <span class="accent">Education</span></h1>
      <p class="lead mb-4">
        At High Q Solid Academy, we are committed to making our students excel academically and mentally.
        Join thousands of successful students who have achieved their dreams with our proven teaching methods.
      </p>
      <div class="hero-ctas d-flex flex-wrap gap-3 mb-5">
        <a href="register.php" class="btn-primary px-4 py-2">Register Now</a>
        <a href="programs.php" class="btn-warning px-4 py-2">See Our Programs</a>
      </div>
      <div class="hero-stats d-flex flex-wrap gap-4 gap-lg-5">
        <div class="text-center text-lg-start">
          <strong class="d-block fs-3 mb-1">6+</strong>
          <div class="text-muted">Years Experience</div>
        </div>
        <div class="text-center text-lg-start">
          <strong class="d-block fs-3 mb-1">1000+</strong>
          <div class="text-muted">Students Trained</div>
        </div>
        <div class="text-center text-lg-start">
          <strong class="d-block fs-3 mb-1">305</strong>
          <div class="text-muted">Highest JAMB Score</div>
        </div>
      </div>
    </div>

    <aside class="hero-right d-none d-lg-flex flex-column gap-4 p-4">
      <div class="feature-card p-4 rounded-3 shadow-sm">
        <div class="feature-icon yellow rounded-circle d-flex align-items-center justify-content-center mb-3"><i class='bx bxs-award fs-3'></i></div>
        <div class="feature-body">
          <h5 class="fw-bold mb-2">Top JAMB Scores</h5>
          <p class="mb-0">Our students consistently achieve exceptional JAMB scores, with our highest scorer achieving 305 in 2025.</p>
        </div>
      </div>
      <div class="feature-card p-4 rounded-3 shadow-sm">
        <div class="feature-icon red rounded-circle d-flex align-items-center justify-content-center mb-3"><i class='bx bxs-chalkboard fs-3'></i></div>
        <div class="feature-body">
          <h5 class="fw-bold mb-2">Expert Tutors</h5>
          <p class="mb-0">Led by Master Adebule Quam and a team of experienced educators dedicated to your academic success.</p>
        </div>
      </div>
      <div class="feature-card p-4 rounded-3 shadow-sm">
        <div class="feature-icon yellow rounded-circle d-flex align-items-center justify-content-center mb-3"><i class='bx bxs-book-open fs-3'></i></div>
        <div class="feature-body">
          <h5 class="fw-bold mb-2">Comprehensive Programs</h5>
          <p class="mb-0">From WAEC to JAMB, Post-UTME to digital skills - we offer complete educational solutions for your success.</p>
        </div>
      </div>
    </aside>
  </div>
</section>


<section class="ceo-hero py-5 my-4">
  <div class="container">

    <!-- Top Heading -->
    <div class="ceo-heading text-center mb-5">
      <h2 class="display-5 fw-bold mb-3">Meet Our <span class="highlight">CEO & Lead Tutor</span></h2>
      <p class="lead text-muted mx-auto" style="max-width: 700px;">
        Under the visionary leadership of Master Adebule Quam, High Q Solid Academy
        has become a beacon of educational excellence.
      </p>
    </div>

    <!-- Two Column Layout with Bootstrap -->
    <div class="ceo-grid row g-4">
      <div class="ceo-left-column col-lg-5">
        <!-- Left Card -->
        <div class="ceo-card bg-white rounded-3 shadow-sm overflow-hidden mb-4">
          <div class="ceo-photo">
            <img src="./assets/images/quam.jpg" alt="Master Adebule Quam" class="img-fluid w-100 object-fit-cover">
          </div>
          <div class="ceo-info p-4 text-center">
            <h3 class="h4 mb-2">Master Adebule Quam</h3>
            <p class="text-muted mb-0">CEO & Lead Tutor</p>
          </div>
        </div>

        <!-- Quote Box -->
        <div class="ceo-quote bg-white rounded-3 shadow-sm p-4">
          <i class="fas fa-quote-left fs-3 mb-3 text-warning"></i>
          <p class="mb-3 fs-5 fw-light">
            "Education is the force to push one ahead of others. Our unwavering
            commitment is to ensure every student achieves academic excellence and
            develops the confidence to face real-world challenges."
          </p>
          <span class="d-block text-end fw-semibold">- Master Adebule Quam</span>
        </div>
      </div>

      <div class="ceo-right-column col-lg-7">
        <!-- Right Content -->
        <div class="ceo-content bg-white rounded-3 shadow-sm p-4 mb-4">
          <h3 class="h4 fw-bold mb-3">Seasoned Educator & Mentor</h3>
          <div class="mb-4">
            <p class="mb-3 text-muted">
              Master Adebule Quam is a seasoned tutor versed in the teaching profession.
              Over the years, he has produced students who have achieved breathtaking
              academic excellence and excelled in various examinations including GCE,
              WAEC, JAMB, NECO, and professional certifications like HTML and CODING.
            </p>
            <p class="text-muted">
              He believes in the school of thought that education is the force to push
              one ahead of others. Under his guidance, High Q Academy has blossomed into
              a hub of academic excellence and technological proficiency.
            </p>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="ceo-stats d-flex flex-wrap gap-3">
          <div class="stat yellow rounded-3 p-4 d-flex flex-column align-items-center text-center">
            <i class="fas fa-trophy fs-2 mb-2"></i>
            <strong class="fs-3 fw-bold mb-1">305</strong>
            <span class="text-muted">Highest JAMB Score</span>
          </div>
          <div class="stat red rounded-3 p-4 d-flex flex-column align-items-center text-center">
            <i class="fas fa-users fs-2 mb-2"></i>
            <strong class="fs-3 fw-bold mb-1">1000+</strong>
            <span>Students Mentored</span>
          </div>
          <div class="stat gray">
            <i class="fas fa-user-tie"></i>
            <strong>6+</strong>
            <span>Years Leading</span>
          </div>
        </div>

        <div class="ceo-btn">
          <a href="about.php" class="btn-dark">Learn More About Our Story</a>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- Programs Section -->
<section class="programs-section">
  <div class="container">
    <div class="ceo-heading">
      <h2>Our <span class="high">Programs & Services</span></h2>
      <p>We offer comprehensive educational programs designed to ensure our students excel academically and develop essential digital skills for the modern world.</p>
    </div>

    <div class="programs-grid">
      <?php if (empty($programs)): ?>
        <p>No programs have been published yet. Check back later.</p>
      <?php else: ?>
        <?php foreach ($programs as $p): ?>
          <?php
          $title = htmlspecialchars($p['title']);
          $slug = htmlspecialchars($p['slug']);
          $desc = trim($p['description'] ?? '');
          $icon = trim($p['icon'] ?? '');
          $features_list = trim($p['features_list'] ?? '');
          $highlight_badge = trim($p['highlight_badge'] ?? '');

          // If description contains multiple lines, treat each line as a bullet
          $lines = preg_split('/\r?\n/', $desc);
          $hasList = count($lines) > 1;

          // prefer features_list from normalized table
          $features_lines = $features_list !== '' ? preg_split('/\r?\n/', $features_list) : ($hasList ? $lines : []);

          // fallback short summary
          $summary = (empty($features_lines) && !$hasList) ? (strlen($desc) > 220 ? substr($desc, 0, 217) . '...' : $desc) : null;
          ?>

          <article class="program-card">
            <div class="program-card-inner">
              <div class="program-icon">
                <?php
                // Prefer Boxicons class stored in icon, otherwise try image filename under assets/images/icons
                if ($icon !== '') {
                  if (strpos($icon, 'bx') !== false) {
                    echo "<i class='" . htmlspecialchars($icon) . "' aria-hidden='true'></i>";
                  } else {
                    $iconPath = __DIR__ . '/assets/images/icons/' . $icon;
                    if (is_readable($iconPath)) {
                      echo "<img src=\"./assets/images/icons/" . rawurlencode($icon) . "\" alt=\"" . htmlspecialchars($title) . " icon\">";
                    } else {
                      // fallback default icon
                      echo "<i class='bx bxs-book-open' aria-hidden='true'></i>";
                    }
                  }
                } else {
                  echo "<i class='bx bxs-book-open' aria-hidden='true'></i>";
                }
                ?>
              </div>

              <div class="program-body">
                <h4>
                  <a href="programs.php?slug=<?= $slug ?>" class="program-title-link">
                    <?= $title ?>
                  </a>

                </h4>

                <?php if (!empty($desc)): ?>
                  <p class="program-summary"><?= htmlspecialchars($desc) ?></p>
                <?php endif; ?>

                <?php if (!empty($features_lines)): ?>
                  <ul class="program-features">
                    <?php foreach (array_slice($features_lines, 0, 5) as $line): ?>
                      <?php $li = trim($line);
                      if ($li === '') continue; ?>
                      <li><?= htmlspecialchars($li) ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

              </div>
            </div>

            <div class="program-card-footer">
              <?php if ($highlight_badge !== ''): ?>
                <div class="program-highlight"><span class="highlight-icon">✺</span> <?= htmlspecialchars($highlight_badge) ?></div>
              <?php else: ?>
                <span class="badge">Learn More</span>
              <?php endif; ?>
            </div>
          </article>

        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- CTA Banner under Programs -->
<section class="programs-cta">
  <div class="container">
    <div class="programs-cta-inner">
      <h3>Ready to Start Your Success Journey?</h3>
      <p>Join our proven programs and take the first step towards academic excellence. Our expert tutors are ready to guide you to success.</p>
      <div class="programs-cta-actions">
        <a href="register.php" class="btn-primary">Register for Programs</a>
        <a href="programs.php" class="btn-ghost">View All Programs</a>
      </div>
    </div>
  </div>
</section>

<?php
// Latest News & Updates: fetch up to 4 published posts
$latestPosts = [];
// Ensure we have a PDO connection (try to require the public config if missing)
if (!isset($pdo) || !$pdo) {
  $dbPath = __DIR__ . '/config/db.php';
  if (file_exists($dbPath)) {
    try { require_once $dbPath; } catch (Throwable $e) { /* ignore */ }
  }
}
if (isset($pdo) && $pdo instanceof PDO) {
  try {
  // Use a safe likes count subquery so this works on installations without a posts.likes column
  $sql = "SELECT p.id, p.title, p.slug, p.excerpt, p.created_at, p.featured_image, (SELECT COUNT(1) FROM post_likes pl WHERE pl.post_id = p.id) AS likes, (SELECT COUNT(1) FROM comments c WHERE c.post_id = p.id AND c.status = 'approved') AS comments_count FROM posts p WHERE p.status = 'published' ORDER BY p.created_at DESC LIMIT 4";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $latestPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    // log DB error for debugging without exposing to users
    @file_put_contents(__DIR__ . '/../storage/posts-debug.log', date('c') . " HOME POSTS QUERY ERROR: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    $latestPosts = [];
  }
}
?>

<section class="news-section">
  <div class="container">
    <div class="ceo-heading">
      <h2>Latest <span class="highlight">News & Updates</span></h2>
      <p class="muted">Stay informed with our latest announcements and blog posts.</p>
    </div>

    <?php if (empty($latestPosts)): ?>
      <p class="no-posts">No news posts available at the moment. Check back later for updates!</p>
    <?php else: ?>
      <div class="news-grid">
        <?php foreach ($latestPosts as $post): ?>
          <article class="news-card">
            <?php if (!empty($post['featured_image'])): ?>
              <div class="news-thumb"><img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>"></div>
            <?php endif; ?>
            <div class="news-body">
              <h4><a href="./post.php?id=<?= intval($post['id']) ?>"><?= htmlspecialchars($post['title']) ?></a></h4>
              <p class="news-excerpt"><?= htmlspecialchars($post['excerpt'] ?? '') ?></p>
              <div class="news-meta"><time><?= date('M j, Y', strtotime($post['created_at'])) ?></time>
                <span class="news-count" style="margin-left:12px;"><i class="fa-regular fa-heart"></i> <?= intval($post['likes'] ?? 0) ?></span>
                <span class="news-count" style="margin-left:8px;"><i class="fa-regular fa-comment-dots"></i> <?= intval($post['comments_count'] ?? 0) ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="news-cta" style="text-align:center;margin-top:18px;">
        <a href="news.php" class="btn-ghost">View All News</a>
      </div>
    <?php endif; ?>
  </div>
</section>
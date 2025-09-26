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

<section class="hero">
  <div class="container">
    <div class="hero-left">
      <div class="hero-badge"><i class='bx bxs-star'></i> Nigeria's Premier Tutorial Academy</div>
      <h1>Excellence in <span class="accent">Education</span></h1>
      <p class="lead">
        At High Q Solid Academy, we are committed to making our students excel academically and mentally.
        Join thousands of successful students who have achieved their dreams with our proven teaching methods.
      </p>
      <div class="hero-ctas">
        <a href="register.php" class="btn-primary">Register Now</a>
        <a href="programs.php" class="btn-ghost">See Our Programs</a>
      </div>
      <div class="hero-stats">
        <div><strong>6+</strong>
          <div>Years Experience</div>
        </div>
        <div><strong>1000+</strong>
          <div>Students Trained</div>
        </div>
        <div><strong>292</strong>
          <div>Highest JAMB Score</div>
        </div>
      </div>
    </div>

    <aside class="hero-right">
      <div class="feature-card">
        <div class="feature-icon yellow"><i class='bx bxs-award'></i></div>
        <div class="feature-body">
          <h5>Top JAMB Scores</h5>
          <p>Our students consistently achieve exceptional JAMB scores, with our highest scorer achieving 305 in 2025.</p>
        </div>
      </div>
      <div class="feature-card">
        <div class="feature-icon red"><i class='bx bxs-chalkboard'></i></div>
        <div class="feature-body">
          <h5>Expert Tutors</h5>
          <p>Led by Master Adebule Quam and a team of experienced educators dedicated to your academic success.</p>
        </div>
      </div>
      <div class="feature-card">
        <div class="feature-icon yellow"><i class='bx bxs-book-open'></i></div>
        <div class="feature-body">
          <h5>Comprehensive Programs</h5>
          <p>From WAEC to JAMB, Post-UTME to digital skills - we offer complete educational solutions for your success.</p>
        </div>
      </div>
    </aside>
  </div>
</section>


<section class="ceo-hero">
  <div class="container">

    <!-- Top Heading -->
    <div class="ceo-heading">
      <h2>Meet Our <span class="highlight">CEO & Lead Tutor</span></h2>
      <p>
        Under the visionary leadership of Master Adebule Quam, High Q Solid Academy
        has become a beacon of educational excellence.
      </p>
    </div>

    <!-- Two Column Layout: left column holds card + quote, right column holds content + stats + button -->
    <div class="ceo-grid">
      <div class="ceo-left-column">
        <!-- Left Card -->
        <div class="ceo-card">
          <div class="ceo-photo">
            <img src="./assets/images/quam.jpg" alt="Master Adebule Quam">
          </div>
          <h3>Master Adebule Quam</h3>
          <p class="role">CEO & Lead Tutor</p>
        </div>

        <!-- Quote Box (moved under left card) -->
        <div class="ceo-quote">
          <i class="fas fa-quote-left"></i>
          <p>
            "Education is the force to push one ahead of others. Our unwavering
            commitment is to ensure every student achieves academic excellence and
            develops the confidence to face real-world challenges."
          </p>
          <span>- Master Adebule Quam</span>
        </div>
      </div>

      <div class="ceo-right-column">
        <!-- Right Content -->
        <div class="ceo-content">
          <h3>Seasoned Educator & Mentor</h3>
          <p>
            Master Adebule Quam is a seasoned tutor versed in the teaching profession.
            Over the years, he has produced students who have achieved breathtaking
            academic excellence and excelled in various examinations including GCE,
            WAEC, JAMB, NECO, and professional certifications like HTML and CODING.
          </p>
          <p>
            He believes in the school of thought that education is the force to push
            one ahead of others. Under his guidance, High Q Academy has blossomed into
            a hub of academic excellence and technological proficiency.
          </p>
        </div>

        <!-- Stats Row (moved under right content) -->
        <div class="ceo-stats">
          <div class="stat yellow">
            <i class="fas fa-trophy"></i>
            <strong>292</strong>
            <span>Highest JAMB Score</span>
          </div>
          <div class="stat red">
            <i class="fas fa-users"></i>
            <strong>1000+</strong>
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
                      echo "<img src=\"assets/images/icons/" . rawurlencode($icon) . "\" alt=\"" . htmlspecialchars($title) . " icon\">";
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
if (isset($pdo) && $pdo instanceof PDO) {
  try {
    $stmt = $pdo->prepare("SELECT id, title, slug, excerpt, created_at, featured_image FROM posts WHERE status='published' ORDER BY created_at DESC LIMIT 4");
    $stmt->execute();
    $latestPosts = $stmt->fetchAll();
  } catch (Throwable $e) {
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
              <h4><a href="post.php?slug=<?= htmlspecialchars($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a></h4>
              <p class="news-excerpt"><?= htmlspecialchars($post['excerpt'] ?: (strlen(strip_tags($post['excerpt'] ?? '')) > 180 ? substr($post['excerpt'], 0, 177) . '...' : ($post['excerpt'] ?? ''))) ?></p>
              <div class="news-meta"><time><?= date('M j, Y', strtotime($post['created_at'])) ?></time></div>
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
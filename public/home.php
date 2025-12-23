

<section class="hero py-5">
  <div class="container d-flex flex-column flex-lg-row align-items-center justify-content-between">
    <div class="hero-left text-center text-lg-start mb-4 mb-lg-0 col-12 col-lg-6">
      <div class="hero-badge mb-3">
        <i class='bx bxs-star'></i> Nigeria's Premier Tutorial Academy
      </div>
      <h1>Excellence in <span class="accent">Education</span></h1>
      <p class="lead">
        At High Q Solid Academy, we are committed to making our students excel academically and mentally.
        Join thousands of successful students who have achieved their dreams with our proven teaching methods.
      </p>
      <div class="hero-ctas d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start gap-2 mt-3">
        <a href="<?= function_exists('app_url') ? app_url('register.php') : 'register.php' ?>" class="btn btn-primary">Register Now</a>
        <a href="<?= function_exists('app_url') ? app_url('programs.php') : 'programs.php' ?>" class="btn btn-light text-dark border">See Our Programs</a>
      </div>
      <div class="hero-stats d-flex flex-wrap justify-content-center justify-content-lg-start gap-3 mt-4">
        <div class="text-center">
          <strong>6+</strong>
          <div>Years Experience</div>
        </div>
        <div class="text-center">
          <strong>1000+</strong>
          <div>Students Trained</div>
        </div>
        <div class="text-center">
          <strong>305</strong>
          <div>Highest JAMB Score</div>
        </div>
      </div>
    </div>

  <aside class="hero-right col-12 col-lg-5 d-flex flex-column gap-3 hq-aside-target">
      <div class="feature-card d-flex">
        <div class="feature-icon yellow me-3"><i class='bx bxs-award'></i></div>
        <div class="feature-body">
          <h5>Top JAMB Scores</h5>
          <p>Our students consistently achieve exceptional JAMB scores, with our highest scorer achieving 305 in 2025.</p>
        </div>
      </div>
      <div class="feature-card d-flex">
        <div class="feature-icon red me-3"><i class='bx bxs-chalkboard'></i></div>
        <div class="feature-body">
          <h5>Expert Tutors</h5>
          <p>Led by Master Adebule Quam and a team of experienced educators dedicated to your academic success.</p>
        </div>
      </div>
      <div class="feature-card d-flex">
        <div class="feature-icon yellow me-3"><i class='bx bxs-book-open'></i></div>
        <div class="feature-body">
          <h5>Comprehensive Programs</h5>
          <p>From WAEC to JAMB, Post-UTME to digital skills - we offer complete educational solutions for your success.</p>
        </div>
      </div>
    </aside>
  </div>
</section>


<section class="ceo-hero py-5">
  <div class="container">

    <div class="ceo-heading text-center mb-5">
      <h2>Meet Our <span class="highlight">CEO & Lead Tutor</span></h2>
      <p>
        Under the visionary leadership of Master Adebule Quam, High Q Solid Academy
        has become a beacon of educational excellence.
      </p>
    </div>

    <div class="ceo-grid align-items-start g-4">
      <div class="ceo-left-column d-flex flex-column align-items-center align-items-lg-start">
        <div class="ceo-card text-center text-lg-start mb-4">
          <div class="ceo-photo mb-3">
            <img src="<?= app_url('assets/images/quam.jpg') ?>" alt="Master Adebule Quam" class="img-fluid rounded">
          </div>
          <div class="ceo-info">
            <h3 class="mb-1">Master Adebule Quam</h3>
            <p class="role mb-0">CEO & Lead Tutor</p>
          </div>
        </div>

        <div class="ceo-quote text-center text-lg-start">
          <i class="fas fa-quote-left mb-2"></i>
          <p class="mb-3">
            "Education is the force to push one ahead of others. Our unwavering
            commitment is to ensure every student achieves academic excellence and
            develops the confidence to face real-world challenges."
          </p>
          <span class="d-block text-end">- Master Adebule Quam</span>
        </div>
      </div>

  <div class="ceo-right-column hq-aside-target">
        <div class="ceo-content mb-4">
          <h3 class="mb-3">Seasoned Educator & Mentor</h3>
          <p class="mb-3">
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

        <div class="ceo-stats d-flex flex-wrap justify-content-center justify-content-lg-start gap-4 mb-4">
          <div class="stat yellow text-center">
            <i class="fas fa-trophy"></i>
            <strong>305</strong>
            <span>Highest JAMB Score</span>
          </div>
          <div class="stat red text-center">
            <i class="fas fa-users"></i>
            <strong>1000+</strong>
            <span>Students Mentored</span>
          </div>
          <div class="stat gray text-center">
            <i class="fas fa-user-tie"></i>
            <strong>6+</strong>
            <span>Years Leading</span>
          </div>
        </div>

        <div class="ceo-btn text-center text-lg-start">
          <a href="about.php" class="btn btn-dark">Learn More About Our Story</a>
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
      
      <!-- Static Program Cards -->
      <article class="program-card">
        <div class="program-card-inner">
          <div class="program-icon">
            <i class='bx bx-target-lock' aria-hidden='true'></i>
          </div>
          <div class="program-body">
            <h4><a href="programs.php#jamb" class="program-title-link">JAMB Preparation</a></h4>
            <p class="program-summary">Comprehensive preparation for Joint Admissions and Matriculation Board examinations</p>
            <ul class="program-features">
              <li>English Language</li>
              <li>Mathematics</li>
              <li>Sciences & Arts</li>
              <li>CBT Practice</li>
            </ul>
          </div>
        </div>
        <div class="program-card-footer">
          <div class="program-highlight"><span class="highlight-icon">✺</span> 305 - Highest Score 2025</div>
        </div>
      </article>

      <article class="program-card">
        <div class="program-card-inner">
          <div class="program-icon">
            <i class='bx bx-book' aria-hidden='true'></i>
          </div>
          <div class="program-body">
            <h4><a href="programs.php#waec" class="program-title-link">WAEC Preparation</a></h4>
            <p class="program-summary">Complete preparation for West African Senior School Certificate Examination</p>
            <ul class="program-features">
              <li>Core Subjects</li>
              <li>Electives</li>
              <li>Practicals</li>
              <li>Past Questions</li>
            </ul>
          </div>
        </div>
        <div class="program-card-footer">
          <span class="badge">Learn More</span>
        </div>
      </article>

      <article class="program-card">
        <div class="program-card-inner">
          <div class="program-icon">
            <i class='bx bx-book-open' aria-hidden='true'></i>
          </div>
          <div class="program-body">
            <h4><a href="programs.php#neco" class="program-title-link">NECO Preparation</a></h4>
            <p class="program-summary">National Examination Council preparation with experienced tutors</p>
            <ul class="program-features">
              <li>All Subjects</li>
              <li>Mock Exams</li>
              <li>Study Materials</li>
              <li>Expert Tutors</li>
            </ul>
          </div>
        </div>
        <div class="program-card-footer">
          <span class="badge">Learn More</span>
        </div>
      </article>

      <article class="program-card">
        <div class="program-card-inner">
          <div class="program-icon">
            <i class='bx bx-award' aria-hidden='true'></i>
          </div>
          <div class="program-body">
            <h4><a href="programs.php#postutme" class="program-title-link">Post-UTME</a></h4>
            <p class="program-summary">University-specific entrance examination preparation</p>
            <ul class="program-features">
              <li>University Focus</li>
              <li>Practice Tests</li>
              <li>Interview Prep</li>
              <li>Counseling</li>
            </ul>
          </div>
        </div>
        <div class="program-card-footer">
          <span class="badge">Learn More</span>
        </div>
      </article>

      <article class="program-card">
        <div class="program-card-inner">
          <div class="program-icon">
            <i class='bx bx-star' aria-hidden='true'></i>
          </div>
          <div class="program-body">
            <h4><a href="programs.php#tutorials" class="program-title-link">Special Tutorials</a></h4>
            <p class="program-summary">Intensive one-on-one and small group tutorial sessions</p>
            <ul class="program-features">
              <li>Personalized</li>
              <li>Flexible Schedule</li>
              <li>Subject Focus</li>
              <li>Individual Attention</li>
            </ul>
          </div>
        </div>
        <div class="program-card-footer">
          <span class="badge">Learn More</span>
        </div>
      </article>

      <article class="program-card">
        <div class="program-card-inner">
          <div class="program-icon">
            <i class='bx bx-laptop' aria-hidden='true'></i>
          </div>
          <div class="program-body">
            <h4><a href="programs.php#computer" class="program-title-link">Computer Training</a></h4>
            <p class="program-summary">Modern computer skills and digital literacy training</p>
            <ul class="program-features">
              <li>MS Office</li>
              <li>Internet Skills</li>
              <li>Programming</li>
              <li>Web Design</li>
            </ul>
          </div>
        </div>
        <div class="program-card-footer">
          <span class="badge">Learn More</span>
        </div>
      </article>

    </div>
  </div>
</section>

<!-- CTA Banner under Programs -->
<section class="programs-cta">
  <div class="container">
    <div class="programs-cta-inner">
        <h3 class="mb-3">Ready to Start Your Success Journey?</h3>
        <p class="mb-4 mx-auto" style="max-width: 700px;">
          Join our proven programs and take the first step towards academic excellence. 
          Our expert tutors are ready to guide you to success.
        </p>

        <div class="programs-cta-actions d-flex flex-column flex-sm-row justify-content-center justify-content-md-start gap-3">
          <a href="register.php" class="btn btn-primary">Register for Programs</a>
          <a href="programs.php" class="btn btn-hq-ghost">View All Programs</a>
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
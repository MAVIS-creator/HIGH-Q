<?php
// SEO Configuration
require_once __DIR__ . '/includes/seo-helpers.php';
set_page_title('Excellence in Education', true);
define('PAGE_DESCRIPTION', SEO_DESC_HOME);

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
        <a href="find-your-path-quiz.php" class="btn btn-primary">Find Your Path</a>
        <a href="register-new.php" class="btn btn-light text-dark border">Skip to Registration</a>
      </div>
      <div class="hero-stats d-flex flex-wrap justify-content-center justify-content-lg-start gap-3 mt-4">
        <div class="text-center">
          <strong>98%</strong>
          <div>WAEC / NECO Success</div>
        </div>
        <div class="text-center">
          <strong>305</strong>
          <div>Highest JAMB Score</div>
        </div>
        <div class="text-center">
          <strong>75%</strong>
          <div>Tech/Digital Placement</div>
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

<!-- Success Stories Strip (One-Two Punch) -->
<section class="testimonials-strip">
  <div class="container">
    <div class="ceo-heading text-center mb-4">
      <h3>Proof of Excellence <span class="highlight">Across Paths</span></h3>
      <p class="lead">Real wins from students who trusted HQ Academy.</p>
    </div>

    <div class="testimonials-scroll-wrapper">
      <button class="testimonials-scroll-btn testimonials-scroll-left" aria-label="Scroll left">
        <i class='bx bx-chevron-left'></i>
      </button>
      
      <div class="testimonials-scroll-container">
        <?php
        // Fetch testimonials from database (more for scrollable view)
        $topTestimonials = [];
        try {
          $stmt = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY display_order ASC LIMIT 8");
          $topTestimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
          // Silently fail
        }
        
        if (empty($topTestimonials)):
          // Fallback hardcoded testimonials
        ?>
        <article class="testimonial-mini">
          <div class="badge-outcome">Admitted to Engineering</div>
          <p class="quote">"Moved from doubts to LAUTECH admission after my prep at HQ."</p>
          <div class="meta">— Aisha O., WAEC + Post-UTME Track</div>
        </article>

        <article class="testimonial-mini">
          <div class="badge-outcome">305 JAMB Score</div>
          <p class="quote">"Structured mocks and tutor feedback pushed me past 300."</p>
          <div class="meta">— Tunde A., JAMB + CBT Mastery</div>
        </article>

        <article class="testimonial-mini">
          <div class="badge-outcome">Cybersecurity Pro</div>
          <p class="quote">"Tech track plus interview coaching → internship in 10 weeks."</p>
          <div class="meta">— Chidinma E., Digital Skills Track</div>
        </article>
        
        <article class="testimonial-mini">
          <div class="badge-outcome">Medical Student</div>
          <p class="quote">"From 180 to 285 JAMB score in just 3 months of intensive prep."</p>
          <div class="meta">— Favour A., JAMB Track</div>
        </article>
        
        <article class="testimonial-mini">
          <div class="badge-outcome">UI/UX Designer</div>
          <p class="quote">"The digital skills track opened doors I never knew existed."</p>
          <div class="meta">— Samuel K., Digital Skills Track</div>
        </article>
        <?php else: ?>
          <?php foreach ($topTestimonials as $t): ?>
          <article class="testimonial-mini">
            <?php if ($t['outcome_badge']): ?>
              <div class="badge-outcome"><?= htmlspecialchars($t['outcome_badge']) ?></div>
            <?php endif; ?>
            <p class="quote">"<?= htmlspecialchars($t['testimonial_text']) ?>"</p>
            <div class="meta">— <?= htmlspecialchars($t['name']) ?><?php if ($t['role_institution']): ?>, <?= htmlspecialchars($t['role_institution']) ?><?php endif; ?></div>
          </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      
      <button class="testimonials-scroll-btn testimonials-scroll-right" aria-label="Scroll right">
        <i class='bx bx-chevron-right'></i>
      </button>
    </div>

    <div class="text-center mt-3">
      <a class="link-more" href="about.php#wall-of-fame">See all 50+ success stories</a>
    </div>
  </div>
</section>

<style>
.testimonials-strip {
  padding: 48px 0;
  background: #0b1a2c;
  color: #fff;
}
.testimonials-strip .highlight { color: var(--hq-yellow, #ffd600); }

/* Scrollable testimonials container */
.testimonials-scroll-wrapper {
  position: relative;
  max-width: 100%;
  margin: 0 auto;
}

.testimonials-scroll-container {
  display: flex;
  gap: 20px;
  overflow-x: auto;
  scroll-behavior: smooth;
  padding: 20px 10px;
  scrollbar-width: thin;
  scrollbar-color: #ffd600 rgba(255,255,255,0.1);
  scroll-snap-type: x mandatory;
}

.testimonials-scroll-container::-webkit-scrollbar {
  height: 6px;
}

.testimonials-scroll-container::-webkit-scrollbar-track {
  background: rgba(255,255,255,0.1);
  border-radius: 10px;
}

.testimonials-scroll-container::-webkit-scrollbar-thumb {
  background: #ffd600;
  border-radius: 10px;
}

.testimonial-mini {
  flex: 0 0 300px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.18);
  scroll-snap-align: start;
  transition: all 0.3s ease;
}

.testimonial-mini:hover {
  background: rgba(255,255,255,0.12);
  border-color: rgba(255, 214, 0, 0.4);
  transform: translateY(-4px);
}

.badge-outcome {
  display: inline-block;
  background: var(--hq-yellow, #ffd600);
  color: #0b1a2c;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 0.8rem;
  margin-bottom: 12px;
}

.testimonial-mini .quote { margin: 0 0 12px; line-height: 1.6; font-size: 0.95rem; }
.testimonial-mini .meta { font-size: 0.85rem; color: rgba(255,255,255,0.75); }
.link-more { color: #ffd600; font-weight: 700; text-decoration: none; }
.link-more:hover { text-decoration: underline; }

/* Scroll buttons */
.testimonials-scroll-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: #ffd600;
  border: none;
  border-radius: 50%;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.testimonials-scroll-btn:hover {
  background: #fff;
  transform: translateY(-50%) scale(1.1);
}

.testimonials-scroll-btn i {
  font-size: 24px;
  color: #0b1a2c;
}

.testimonials-scroll-left {
  left: -10px;
}

.testimonials-scroll-right {
  right: -10px;
}

@media (max-width: 1024px) {
  .testimonials-scroll-container {
    -webkit-overflow-scrolling: touch;
  }
  
  .testimonials-scroll-btn {
    width: 38px;
    height: 38px;
  }
  
  .testimonials-scroll-btn i {
    font-size: 20px;
  }
  
  .testimonials-scroll-left {
    left: 5px;
  }
  
  .testimonials-scroll-right {
    right: 5px;
  }
}

@media (max-width: 768px) {
  .testimonials-strip { padding: 36px 0; }
  
  .testimonials-scroll-btn {
    display: none;
  }
  
  .testimonial-mini {
    flex: 0 0 260px;
  }
}
</style>

<script>
// Testimonials scroll buttons functionality
document.addEventListener('DOMContentLoaded', function() {
  const scrollContainer = document.querySelector('.testimonials-scroll-container');
  const leftBtn = document.querySelector('.testimonials-scroll-left');
  const rightBtn = document.querySelector('.testimonials-scroll-right');

  if (scrollContainer && leftBtn && rightBtn) {
    leftBtn.addEventListener('click', () => {
      scrollContainer.scrollBy({ left: -320, behavior: 'smooth' });
    });

    rightBtn.addEventListener('click', () => {
      scrollContainer.scrollBy({ left: 320, behavior: 'smooth' });
    });

    // Update button visibility based on scroll position
    function updateScrollButtons() {
      const maxScroll = scrollContainer.scrollWidth - scrollContainer.clientWidth;
      leftBtn.style.opacity = scrollContainer.scrollLeft > 0 ? '1' : '0.3';
      leftBtn.style.pointerEvents = scrollContainer.scrollLeft > 0 ? 'auto' : 'none';
      rightBtn.style.opacity = scrollContainer.scrollLeft < maxScroll - 5 ? '1' : '0.3';
      rightBtn.style.pointerEvents = scrollContainer.scrollLeft < maxScroll - 5 ? 'auto' : 'none';
    }

    scrollContainer.addEventListener('scroll', updateScrollButtons);
    updateScrollButtons(); // Initial check
  }
});
</script>


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
                      echo "<img src='" . app_url('assets/images/icons/' . rawurlencode($icon)) . "' alt='" . htmlspecialchars($title) . " icon'>";
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
        <h3 class="mb-3">Ready to Start Your Success Journey?</h3>
        <p class="mb-4 mx-auto" style="max-width: 700px;">
          Join our proven programs and take the first step towards academic excellence. 
          Our expert tutors are ready to guide you to success.
        </p>

        <div class="programs-cta-actions d-flex flex-column flex-sm-row justify-content-center justify-content-md-start gap-3">
          <a href="find-your-path-quiz.php" class="btn btn-primary">Take the Path Quiz</a>
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
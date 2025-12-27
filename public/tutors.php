<?php
// public/tutors.php - partial for displaying featured tutors
$tutors = [];
// load DB connection if available
if (file_exists(__DIR__ . '/config/db.php')) {
  try {
    require_once __DIR__ . '/config/db.php';
    if (isset($pdo)) {
      // Try featured tutors first (preserve featured if any), but when falling back or listing ensure tutors are ordered by id ascending per UX request
      $stmt = $pdo->prepare("SELECT * FROM tutors WHERE is_featured=1 ORDER BY created_at DESC LIMIT 6");
      $stmt->execute();
      $tutors = $stmt->fetchAll();
      // If none are featured, fall back to any tutors so the section is visible for testing
      if (empty($tutors)) {
        // order by id ascending as requested
        $stmt2 = $pdo->prepare("SELECT * FROM tutors ORDER BY id ASC");
        $stmt2->execute();
        $tutors = $stmt2->fetchAll();
      }
    }
  } catch (Throwable $e) {
    // swallow DB errors and render an empty state
    $tutors = [];
  }
}
?>

<section class="tutors-section">
  <!-- moved page-scoped styles into public/assets/css/public.css -->
  <div class="container">
    <div class="ceo-heading">
      <h2>Meet Our Expert <span class="highlight">Tutors</span></h2>
      <p class="lead">Our dedicated team of experienced educators is committed to your academic success</p>
    </div>

    <?php if (empty($tutors)): ?>
      <p class="no-posts">No tutors available at this time. (If you're testing, create a tutor in the admin area.)</p>
      <!-- Placeholder tutor so layout can be previewed -->
      <div class="tutors-grid">
        <article class="tutor-card">
      <div class="tutor-thumb"><img src="<?= app_url('assets/images/hq-logo.jpeg') ?>" alt="Placeholder"></div>
          <div class="tutor-body">
            <h3>Sample Tutor</h3>
            <p class="role">B.Sc, M.Ed</p>
            <p class="tutor-short">Experienced educator in Mathematics and Sciences.</p>
            <div class="subjects"><span class="tag">Mathematics</span><span class="tag">Physics</span></div>
          </div>
        </article>
      </div>
    <?php else: ?>
      <?php
        // Static lead card for Adebule Quam (CEO) — manually inserted so it always appears first
      ?>
      <div class="tutor-lead-wrap">
        <article class="tutor-card tutor-lead">
          <div class="tutor-thumb">
            <img src="<?= app_url('assets/images/quam.jpg') ?>" alt="Adebule Quam">
          </div>
          <div class="tutor-body">
            <h3>Adebule Quam</h3>
            <p class="qualification-line">CEO of HIGH Q SOLID ACADEMY</p>
            <p class="tutor-short">Seasoned tutor whose students excel in GCE, WAEC, JAMB, NECO and coding certifications.</p>
          </div>
        </article>
      </div>

      <div class="tutors-grid">
        <?php foreach ($tutors as $t): ?>
          <article class="tutor-card">
              <div class="tutor-thumb">
              <img src="<?= htmlspecialchars($t['photo'] ?: app_url('assets/images/hq-logo.jpeg')) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
            </div>
            <div class="tutor-body">
              <h3><?= htmlspecialchars($t['name']) ?></h3>

              <?php
                // qualifications: show as single line or 'Not specified'
                $quals = array_filter(array_map('trim', explode(',', $t['qualifications'] ?? '')));
                if (!empty($quals)):
              ?>
                <p class="qualification-line"><?= htmlspecialchars(implode(', ', $quals)) ?></p>
              <?php else: ?>
                <p class="qualification-line">Not specified</p>
              <?php endif; ?>

              <!-- Long bio (full description) next -->
              <?php if (!empty($t['long_bio'])): ?>
                <div class="tutor-long-bio"><?= nl2br(htmlspecialchars($t['long_bio'])) ?></div>
              <?php endif; ?>

              <!-- Subjects with label as requested -->
              <?php $subs = json_decode($t['subjects'] ?? '[]', true); if (!empty($subs)): ?>
                <div class="subjects">
                  <div class="subjects-label"><strong>Subjects:</strong></div>
                  <div class="subjects-list">
                    <?php foreach ($subs as $s): ?>
                      <span class="tag"><?= htmlspecialchars($s) ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <!-- Short bio (years of experience) at the bottom -->
              <p class="tutor-short"><?= htmlspecialchars($t['short_bio']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- Tutors footer text -->
    <div class="tutors-footer text-center mt-4">
      <p class="lead">And many other experienced tutors dedicated to your academic success...</p>
      <p class="tutor-description">Working together with our team of dedicated educators to nurture the next generation of academic achievers.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- moved runtime layout overrides into public CSS; JS overrides removed -->

<style>
.tutors-footer {
    margin-top: 3rem;
    padding: 2rem;
    background: var(--hq-yellow-pale);
    border-radius: 12px;
    text-align: center;
}

.tutors-footer .lead {
    color: var(--hq-black);
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.tutors-footer .tutor-description {
    color: var(--hq-gray);
    font-style: italic;
    margin-top: 0.5rem;
}

.text-center {
    text-align: center;
}

.mt-3 {
    margin-top: 1rem;
}

.mt-4 {
    margin-top: 1.5rem;
}
</style>
<style>
/* Ensure when we use Bootstrap .row together with existing site classes,
   the row behavior (flex) wins on small screens so columns stack correctly.
   On desktop we prefer the site-wide grid (4 columns) defined in public.css. */
@media (max-width: 991.98px) {
  .achievements-grid.row, .testimonials-grid.row {
    display: flex !important;
    flex-wrap: wrap !important;
  }
  .achievements-grid.row > .col-12, .testimonials-grid.row > .col-12 { display: block; }
}
</style>
<!-- Achievements -->
<!-- Achievements Section -->
<section class="achievements py-5">
  <div class="container">
    <div class="ceo-heading text-center mb-4">
      <h2>Our <span class="highlight">Achievements</span></h2>
    </div>
    <div class="row achievements-grid gy-4">
      <div class="col-6 col-md-3 achievement text-center">
        <strong>500+</strong>
        <span>Students Graduated</span>
      </div>
      <div class="col-6 col-md-3 achievement text-center">
        <strong>98%</strong>
        <span>Success Rate</span>
      </div>
      <div class="col-6 col-md-3 achievement text-center">
        <strong>15+</strong>
        <span>Expert Tutors</span>
      </div>
      <div class="col-6 col-md-3 achievement text-center">
        <strong>5+</strong>
        <span>Years Experience</span>
      </div>
    </div>
  </div>
</section>

<style>
  /* Mobile-only: keep achievements in two columns and centered for small screens */
  @media (max-width: 575.98px) {
    .achievements-grid {
      display: flex !important;
      flex-wrap: wrap !important;
      justify-content: center; /* center the grid */
      gap: 0.5rem 1rem;
    }

    .achievements-grid .achievement {
      flex: 0 0 45%; /* two columns with small gutter */
      max-width: 45%;
      box-sizing: border-box;
      text-align: center;
      padding: 0.5rem 0;
    }

    /* Make numbers slightly larger and keep span on its own line for readability */
    .achievements-grid .achievement strong { display: block; font-size: 1.6rem; color: var(--hq-yellow, #f4c542); }
    .achievements-grid .achievement span { display: block; color: var(--hq-gray, #666); font-size: 0.9rem; }
  }
</style>

<!-- Wall of Fame - Horizontal Scrolling Testimonials -->
<section class="wall-of-fame-section py-5">
  <div class="container-fluid px-3 px-md-4">
    <div class="ceo-heading text-center mb-4">
      <h2>Wall of <span class="highlight">Fame</span></h2>
      <p>Real success stories from students who trusted HQ Academy</p>
    </div>

    <?php
    // Fetch testimonials from database
    require_once __DIR__ . '/config/db.php';
    $wallTestimonials = [];
    try {
      $stmt = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY display_order ASC LIMIT 12");
      $wallTestimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      // Silently fail
    }
    ?>

    <?php if (empty($wallTestimonials)): ?>
      <p class="text-center text-muted">Our success stories are being updated. Check back soon!</p>
    <?php else: ?>
      <div class="wall-scroll-wrapper">
        <button class="wall-scroll-btn wall-scroll-left" aria-label="Scroll left">
          <i class='bx bx-chevron-left'></i>
        </button>
        
        <div class="wall-scroll-container">
          <?php foreach ($wallTestimonials as $t): ?>
          <article class="wall-testimony-card">
            <?php if ($t['image_path']): ?>
              <div class="wall-testimony-image">
                <img src="<?= htmlspecialchars($t['image_path']) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
              </div>
            <?php else: ?>
              <div class="wall-testimony-image wall-testimony-placeholder">
                <i class='bx bxs-user-circle'></i>
              </div>
            <?php endif; ?>
            
            <div class="wall-testimony-content">
              <?php if ($t['outcome_badge']): ?>
                <span class="wall-testimony-badge"><?= htmlspecialchars($t['outcome_badge']) ?></span>
              <?php endif; ?>
              
              <p class="wall-testimony-text">"<?= htmlspecialchars($t['testimonial_text']) ?>"</p>
              
              <div class="wall-testimony-author">
                <strong><?= htmlspecialchars($t['name']) ?></strong>
                <?php if ($t['role_institution']): ?>
                  <small><?= htmlspecialchars($t['role_institution']) ?></small>
                <?php endif; ?>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <button class="wall-scroll-btn wall-scroll-right" aria-label="Scroll right">
          <i class='bx bx-chevron-right'></i>
        </button>
      </div>

      <div class="text-center mt-4">
        <a href="about.php#wall-of-fame" class="btn btn-outline-primary">View All Success Stories</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.wall-of-fame-section {
  background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
}

.wall-scroll-wrapper {
  position: relative;
  max-width: 100%;
  margin: 0 auto;
}

.wall-scroll-container {
  display: flex;
  gap: 20px;
  overflow-x: auto;
  scroll-behavior: smooth;
  padding: 20px 5px;
  scrollbar-width: thin;
  scrollbar-color: #ffd600 #f0f0f0;
  /* Scroll snap for mobile/tablets */
  scroll-snap-type: x mandatory;
}

.wall-scroll-container::-webkit-scrollbar {
  height: 8px;
}

.wall-scroll-container::-webkit-scrollbar-track {
  background: #f0f0f0;
  border-radius: 10px;
}

.wall-scroll-container::-webkit-scrollbar-thumb {
  background: #ffd600;
  border-radius: 10px;
}

.wall-scroll-container::-webkit-scrollbar-thumb:hover {
  background: #e6c200;
}

.wall-testimony-card {
  flex: 0 0 320px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  transition: all 0.3s ease;
  /* Scroll snap alignment */
  scroll-snap-align: start;
  scroll-snap-stop: always;
}

.wall-testimony-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  border-color: #ffd600;
}

.wall-testimony-image {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  margin: 0 auto;
  border: 3px solid #ffd600;
  flex-shrink: 0;
}

.wall-testimony-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.wall-testimony-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  font-size: 48px;
  color: #9ca3af;
}

.wall-testimony-content {
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.wall-testimony-badge {
  display: inline-block;
  background: #ffd600;
  color: #0b1a2c;
  font-weight: 700;
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
  align-self: center;
}

.wall-testimony-text {
  font-size: 0.9rem;
  line-height: 1.6;
  color: #374151;
  margin: 0;
  font-style: italic;
}

.wall-testimony-author strong {
  display: block;
  font-size: 1rem;
  color: #111;
  margin-bottom: 4px;
}

.wall-testimony-author small {
  display: block;
  font-size: 0.85rem;
  color: #6b7280;
}

.wall-scroll-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: white;
  border: 2px solid #ffd600;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.wall-scroll-btn:hover {
  background: #ffd600;
  transform: translateY(-50%) scale(1.1);
}

.wall-scroll-btn i {
  font-size: 28px;
  color: #0b1a2c;
}

.wall-scroll-left {
  left: 10px;
}

.wall-scroll-right {
  right: 10px;
}

@media (max-width: 1024px) {
  .wall-scroll-container {
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    /* Enable momentum scrolling on iOS */
  }
  
  .wall-testimony-card {
    scroll-snap-align: start;
    scroll-snap-stop: always;
  }
  
  .wall-scroll-btn {
    width: 40px;
    height: 40px;
  }
  
  .wall-scroll-btn i {
    font-size: 22px;
  }
}

@media (max-width: 768px) {
  .wall-scroll-btn {
    width: 36px;
    height: 36px;
    display: flex;
  }
  
  .wall-scroll-btn i {
    font-size: 20px;
  }
  
  .wall-scroll-left {
    left: 5px;
  }
  
  .wall-scroll-right {
    right: 5px;
  }
  
  .wall-testimony-card {
    flex: 0 0 280px;
  }
}
</style>

<script>
// Horizontal scroll buttons for Wall of Fame
document.addEventListener('DOMContentLoaded', function() {
  const scrollContainer = document.querySelector('.wall-scroll-container');
  const leftBtn = document.querySelector('.wall-scroll-left');
  const rightBtn = document.querySelector('.wall-scroll-right');

  if (scrollContainer && leftBtn && rightBtn) {
    leftBtn.addEventListener('click', () => {
      scrollContainer.scrollBy({ left: -350, behavior: 'smooth' });
    });

    rightBtn.addEventListener('click', () => {
      scrollContainer.scrollBy({ left: 350, behavior: 'smooth' });
    });

    // Hide/show buttons based on scroll position
    function updateScrollButtons() {
      const maxScroll = scrollContainer.scrollWidth - scrollContainer.clientWidth;
      leftBtn.style.opacity = scrollContainer.scrollLeft > 0 ? '1' : '0.3';
      leftBtn.style.pointerEvents = scrollContainer.scrollLeft > 0 ? 'auto' : 'none';
      rightBtn.style.opacity = scrollContainer.scrollLeft < maxScroll - 5 ? '1' : '0.3';
      rightBtn.style.pointerEvents = scrollContainer.scrollLeft < maxScroll - 5 ? 'auto' : 'none';
    }

    scrollContainer.addEventListener('scroll', updateScrollButtons);
    updateScrollButtons();
  }
});
</script>

<!-- CTA Banner -->
<section class="cta-join">
  <div class="container">
    <h2>Ready to Start Your Journey?</h2>
    <p>Join hundreds of students who have achieved their academic goals with us</p>
    <a href="register-new.php" class="btn-dark cta-btn">Find Your Path</a>
  </div>
</section>

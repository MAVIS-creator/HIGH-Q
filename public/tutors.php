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
      <div class="tutor-thumb"><img src="/HIGH-Q/public/assets/images/hq-logo.jpeg" alt="Placeholder"></div>
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
            <img src="/HIGH-Q/public/assets/images/quam.jpg" alt="Adebule Quam">
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
              <img src="/HIGH-Q/public/<?= htmlspecialchars($t['photo'] ?: 'assets/images/hq-logo.jpeg') ?>" alt="<?= htmlspecialchars($t['name']) ?>">
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
   the row behavior (flex) wins so columns stack correctly on mobile. */
.achievements-grid.row, .testimonials-grid.row {
  display: flex !important;
  flex-wrap: wrap !important;
}
.achievements-grid.row > .col-12, .testimonials-grid.row > .col-12 { display: block; }
</style>
<style>
/* Fix: some global card animation rules set .achievement to opacity:0 until JS adds .in-view
   On small screens we want Achievements visible even if JS does not run, so override there. */
@media (max-width: 768px) {
  .achievements .achievement,
  .achievements .achievement * {
    opacity: 1 !important;
    transform: none !important;
    visibility: visible !important;
  }
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

<!-- Testimonials / What Our Students Say -->
<section class="testimonials-section py-5">
  <div class="container px-3 px-md-0">
    <div class="ceo-heading text-center mb-4">
      <h2>What Our <span class="highlight">Students Say</span></h2>
    </div>

    <div class="row testimonials-grid gy-4 justify-content-center">
      <article class="testimonial-card col-12 col-md-6 col-lg-4">
        <div class="p-3 h-100 border rounded shadow-sm">
          <div class="rating mb-2">★★★★★</div>
          <p class="quote">"Master Quam helped me realize my potential in the digital world. His guidance and mentorship opened my eyes to the vast opportunities in tech, leading me to pursue my passion in cybersecurity."</p>
          <p class="attribution mb-0"><strong>Akintunde Dolapo</strong><br><small>Studying Cybersecurity at LAUTECH</small></p>
        </div>
      </article>

      <article class="testimonial-card col-12 col-md-6 col-lg-4">
        <div class="p-3 h-100 border rounded shadow-sm">
          <div class="rating mb-2">★★★★★</div>
          <p class="quote">"Through HQ Academy's exceptional tutoring and guidance, I achieved an outstanding score in JAMB. Their teaching methodology and dedication to student success is unmatched."</p>
          <p class="attribution mb-0"><strong>Sanni Micheal</strong><br><small>JAMB Score: 305</small></p>
        </div>
      </article>

      <article class="testimonial-card col-12 col-md-6 col-lg-4">
        <div class="p-3 h-100 border rounded shadow-sm">
          <div class="rating mb-2">★★★★★</div>
          <p class="quote">"The comprehensive preparation and mentorship at HQ Academy were instrumental in my academic journey. Their guidance helped me secure my place in Chemical Engineering."</p>
          <p class="attribution mb-0"><strong>Adebayo Samod</strong><br><small>Chemical Engineering, LAUTECH | JAMB Score: 257</small></p>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- CTA Banner -->
<section class="cta-join">
  <div class="container">
    <h2>Ready to Start Your Journey?</h2>
    <p>Join hundreds of students who have achieved their academic goals with us</p>
    <a href="register.php" class="btn-dark cta-btn">Register Now</a>
  </div>
</section>

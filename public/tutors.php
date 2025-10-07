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

<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="display-5 fw-bold mb-3">Meet Our Expert <span class="text-primary">Tutors</span></h2>
      <p class="lead text-muted">Our dedicated team of experienced educators is committed to your academic success</p>
    </div>

    <?php if (empty($tutors)): ?>
      <div class="text-center text-muted my-5">
        <p class="mb-0">No tutors available at this time. (If you're testing, create a tutor in the admin area.)</p>
      </div>
      <!-- Placeholder tutor so layout can be previewed -->
      <div class="row g-4">
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 border-0 shadow-sm">
            <img src="/HIGH-Q/public/assets/images/hq-logo.jpeg" class="card-img-top" alt="Placeholder" style="height: 240px; object-fit: cover;">
            <div class="card-body p-4">
              <h4 class="card-title h5 mb-2">Sample Tutor</h4>
              <p class="text-muted small mb-3">B.Sc, M.Ed</p>
              <p class="card-text mb-3">Experienced educator in Mathematics and Sciences.</p>
              <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">Mathematics</span>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">Physics</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php else: ?>
      <?php
        // Static lead card for Adebule Quam (CEO) — manually inserted so it always appears first
      ?>
      <div class="row justify-content-center mb-5">
        <div class="col-lg-8">
          <div class="card border-0 shadow-lg overflow-hidden">
            <div class="row g-0">
              <div class="col-md-5">
                <img src="/HIGH-Q/public/assets/images/quam.jpg" class="w-100 h-100" alt="Adebule Quam" style="object-fit: cover;">
              </div>
              <div class="col-md-7">
                <div class="card-body p-4 p-md-5">
                  <h3 class="card-title h2 mb-2">Adebule Quam</h3>
                  <p class="text-primary fw-bold mb-3">CEO of HIGH Q SOLID ACADEMY</p>
                  <p class="card-text lead mb-0">Seasoned tutor whose students excel in GCE, WAEC, JAMB, NECO and coding certifications.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <?php foreach ($tutors as $t): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
              <img src="/HIGH-Q/public/<?= htmlspecialchars($t['photo'] ?: 'assets/images/hq-logo.jpeg') ?>" 
                   class="card-img-top" alt="<?= htmlspecialchars($t['name']) ?>" 
                   style="height: 240px; object-fit: cover;">
              <div class="card-body p-4">
                <h4 class="card-title h5 mb-2"><?= htmlspecialchars($t['name']) ?></h4>

              <?php
                // qualifications: show as single line or 'Not specified'
                $quals = array_filter(array_map('trim', explode(',', $t['qualifications'] ?? '')));
                if (!empty($quals)):
              ?>
                <p class="text-muted small mb-3"><?= htmlspecialchars(implode(', ', $quals)) ?></p>
              <?php else: ?>
                <p class="text-muted small mb-3">Not specified</p>
              <?php endif; ?>

              <!-- Long bio (full description) next -->
              <?php if (!empty($t['long_bio'])): ?>
                <p class="card-text mb-3"><?= nl2br(htmlspecialchars($t['long_bio'])) ?></p>
              <?php endif; ?>

              <!-- Subjects with label as requested -->
              <?php $subs = json_decode($t['subjects'] ?? '[]', true); if (!empty($subs)): ?>
                <div class="mt-auto">
                  <p class="text-muted small mb-2"><strong>Subjects:</strong></p>
                  <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($subs as $s): ?>
                      <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2"><?= htmlspecialchars($s) ?></span>
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
<!-- Achievements -->
<section class="achievements">
  <div class="container">
    <div class="ceo-heading">
      <h2>Our <span class="highlight">Achievements</span></h2>
    </div>
    <div class="achievements-grid">
      <div class="achievement">
        <strong>500+</strong>
        <span>Students Graduated</span>
      </div>
      <div class="achievement">
        <strong>98%</strong>
        <span>Success Rate</span>
      </div>
      <div class="achievement">
        <strong>15+</strong>
        <span>Expert Tutors</span>
      </div>
      <div class="achievement">
        <strong>5+</strong>
        <span>Years Experience</span>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials / What Our Students Say -->
<section class="testimonials-section">
  <div class="container">
    <div class="ceo-heading">
      <h2>What Our <span class="highlight">Students Say</span></h2>
    </div>

    <div class="testimonials-grid">
      <article class="testimonial-card">
        <div class="rating">★★★★★</div>
        <p class="quote">"Master Quam helped me realize my potential in the digital world. His guidance and mentorship opened my eyes to the vast opportunities in tech, leading me to pursue my passion in cybersecurity."</p>
        <p class="attribution"><strong>Akintunde Dolapo</strong><br><small>Studying Cybersecurity at LAUTECH</small></p>
      </article>

      <article class="testimonial-card">
        <div class="rating">★★★★★</div>
        <p class="quote">"Through HQ Academy's exceptional tutoring and guidance, I achieved an outstanding score in JAMB. Their teaching methodology and dedication to student success is unmatched."</p>
        <p class="attribution"><strong>Sanni Micheal</strong><br><small>JAMB Score: 305</small></p>
      </article>

      <article class="testimonial-card">
        <div class="rating">★★★★★</div>
        <p class="quote">"The comprehensive preparation and mentorship at HQ Academy were instrumental in my academic journey. Their guidance helped me secure my place in Chemical Engineering."</p>
        <p class="attribution"><strong>Adebayo Samod</strong><br><small>Chemical Engineering, LAUTECH | JAMB Score: 257</small></p>
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

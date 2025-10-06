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
          <div class="tutor-thumb"><img src="./assets/images/avatar-placeholder.png" alt="Placeholder"></div>
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
            <img src="./assets/images/avatar-placeholder.png" alt="Adebule Quam">
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
              <img src="./<?= htmlspecialchars($t['photo'] ?: 'assets/images/avatar-placeholder.png') ?>" alt="<?= htmlspecialchars($t['name']) ?>">
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
        <p class="quote">"HQ Academy transformed my understanding of mathematics. The tutors are incredibly patient and explain concepts so clearly!"</p>
        <p class="attribution"><strong>Adunni Olatunji</strong><br><small>JAMB 2023 - Score: 287</small></p>
      </article>

      <article class="testimonial-card">
        <div class="rating">★★★★★</div>
        <p class="quote">"Thanks to HQ Academy, I passed my WAEC with flying colors. The personalized attention made all the difference!"</p>
        <p class="attribution"><strong>Chidi Okwu</strong><br><small>WAEC 2023 - 8 A's</small></p>
      </article>

      <article class="testimonial-card">
        <div class="rating">★★★★★</div>
        <p class="quote">"The best decision I made was joining HQ Academy. Their Post-UTME preparation got me into my dream university!"</p>
        <p class="attribution"><strong>Fatima Hassan</strong><br><small>University of Lagos 2023</small></p>
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

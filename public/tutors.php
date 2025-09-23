<?php
// public/tutors.php - partial for displaying featured tutors
$tutors = [];
// load DB connection if available
if (file_exists(__DIR__ . '/config/db.php')) {
  try {
    require_once __DIR__ . '/config/db.php';
    if (isset($pdo)) {
      $stmt = $pdo->prepare("SELECT * FROM tutors WHERE is_featured=1 ORDER BY created_at DESC LIMIT 6");
      $stmt->execute();
      $tutors = $stmt->fetchAll();
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
      <p class="no-posts">No tutors available at this time.</p>
    <?php else: ?>
      <div class="tutors-grid">
        <?php foreach ($tutors as $t): ?>
          <article class="tutor-card">
            <div class="tutor-thumb">
              <img src="./<?= htmlspecialchars($t['photo'] ?: 'assets/images/avatar-placeholder.png') ?>" alt="<?= htmlspecialchars($t['name']) ?>">
            </div>
            <div class="tutor-body">
              <h3><?= htmlspecialchars($t['name']) ?></h3>
              <p class="role"><?= htmlspecialchars($t['qualifications']) ?></p>
              <p class="tutor-short"><?= htmlspecialchars($t['short_bio']) ?></p>
              <?php $subs = json_decode($t['subjects'] ?? '[]', true); if (!empty($subs)): ?>
                <div class="subjects">
                  <?php foreach ($subs as $s): ?>
                    <span class="tag"><?= htmlspecialchars($s) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

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

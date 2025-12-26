<?php
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
  /* Mobile-only: center history logo and add small gap under it without affecting desktop */
  @media (max-width: 767.98px) {
    .history-logo {
      display: flex !important;
      justify-content: center !important; /* center horizontally */
      align-items: center !important;
      text-align: center;
      margin-bottom: 0.75rem; /* small gap similar to a <br> */
    }

    /* make the logo-card slightly smaller on very small screens if needed */
    .history-logo .logo-card img {
      max-width: 140px;
      height: auto;
    }
  }
</style>

<section class="about-hero">
  <div class="about-hero-overlay"></div>
  <div class="container about-hero-inner">
    <h1>About HQ Academy</h1>
    <p class="lead">Building Excellence in Education Through Dedication and Innovation</p>
  </div>
</section>

<div id="wall-of-fame"></div>

<!-- Wall of Fame Section -->
<section class="wall-of-fame-section">
  <div class="container">
    <div class="ceo-heading text-center">
      <h2>Our Wall of <span class="highlight">Fame</span></h2>
      <p class="lead">Real success stories from students who trusted HQ Academy to transform their futures.</p>
    </div>

    <?php
    // Fetch active testimonials from database
    require_once __DIR__ . '/config/db.php';
    $testimonials = [];
    try {
      $stmt = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC");
      $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      // Silently fail
    }
    ?>

    <div class="wall-grid">
      <?php if (empty($testimonials)): ?>
        <p style="text-align: center; color: #666; padding: 40px; grid-column: 1 / -1;">Our success stories are being updated. Check back soon!</p>
      <?php else: ?>
        <?php foreach ($testimonials as $t): ?>
        <article class="wall-card">
          <?php if ($t['image_path']): ?>
            <div class="wall-image">
              <img src="<?= app_url($t['image_path']) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
            </div>
          <?php else: ?>
            <div class="wall-image wall-image-placeholder">
              <i class='bx bxs-user-circle'></i>
            </div>
          <?php endif; ?>
          
          <div class="wall-content">
            <?php if ($t['outcome_badge']): ?>
              <span class="wall-badge"><?= htmlspecialchars($t['outcome_badge']) ?></span>
            <?php endif; ?>
            
            <h4><?= htmlspecialchars($t['name']) ?></h4>
            
            <?php if ($t['role_institution']): ?>
              <p class="wall-role"><?= htmlspecialchars($t['role_institution']) ?></p>
            <?php endif; ?>
            
            <p class="wall-text"><?= htmlspecialchars($t['testimonial_text']) ?></p>
          </div>
        </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
.wall-of-fame-section {
  padding: 60px 0;
  background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
}

.wall-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
  margin-top: 40px;
}

.wall-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 24px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.wall-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  border-color: #ffd600;
}

.wall-image {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  overflow: hidden;
  margin: 0 auto 16px;
  border: 3px solid #ffd600;
}

.wall-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.wall-image-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  font-size: 60px;
  color: #9ca3af;
}

.wall-content {
  text-align: center;
}

.wall-badge {
  display: inline-block;
  background: #ffd600;
  color: #0b1a2c;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 0.8rem;
  margin-bottom: 12px;
  letter-spacing: 0.5px;
}

.wall-card h4 {
  margin: 0 0 6px;
  font-size: 1.2rem;
  color: #111;
  font-weight: 700;
}

.wall-role {
  font-size: 0.9rem;
  color: #6b7280;
  margin: 0 0 16px;
  font-weight: 500;
}

.wall-text {
  font-size: 0.95rem;
  line-height: 1.7;
  color: #374151;
  margin: 0;
}

@media (max-width: 768px) {
  .wall-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .wall-of-fame-section {
    padding: 40px 0;
  }
}
</style>

<!-- Core Values -->

<section class="history-section">
  <div class="container">
    <div class="row align-items-start">
      <div class="col-12 col-md-8 order-2 order-md-1 history-content">
      <h3>History About High Q Solid Academy</h3>
      <p>High Q tutorial founded in 2018/2019 by Mr. Adebule Quam Okikiola and Mr. Adebule Ibrahim has left an enduring legacy since its inception. Named after its visionary founders, the tutorial symbolizes a commitment to education empowerment that resonates within the community.</p>

      <p>Following Mr. Ibrahim's departure for overseas opportunities, Mr. Adebule Quam assumed sole leadership, steering the tutorial toward remarkable success. Under Mr. Adebule Quam's guidance, High Q Tutorial has blossomed into a hub of academic excellence and technological proficiency.</p>

      <p>Beyond conventional tutorial work, it serves as a catalyst for holistic development. Through meticulous instruction, students have sharpened their academic prowess, achieving commendable results in examinations such as JAMB, WAEC, and NECO since 2018.</p>

      <p>High Q Tutorial’s impact transcends the classroom, enriching lives and fostering digital literacy essential for navigating the complexities of the modern world. By equipping learners with practical skills in Microsoft Word, Excel, graphic design, and programming, the tutorial prepares students for both academic success and real-world challenges.</p>

      <p>The tutorial’s unwavering dedication to educational enrichment has solidified its position as a cornerstone of the community.</p>
      </div>

      <div class="col-12 col-md-4 order-1 order-md-2 history-logo d-flex justify-content-md-center justify-content-end">
        <div class="logo-card">
          <img src="<?= app_url('assets/images/hq-logo.jpeg') ?>" alt="HQ Logo" class="img-fluid">
        </div>
      </div>
    </div>
  </div>
</section>


<!-- Vision & Mission -->
<section class="values-section">
  <div class="container">
    <div class="values-grid">
      <article class="value-card">
        <div class="value-icon"><i class='bx bxs-bullseye'></i></div>
        <h4>Our Vision</h4>
        <p>To be the leading tutorial academy that transforms students into confident, well-prepared individuals ready to excel in their academic pursuits and achieve their educational goals with excellence.</p>
      </article>

      <article class="value-card">
        <div class="value-icon"><i class='bx bxs-rocket'></i></div>
        <h4>Our Mission</h4>
        <p>To provide high-quality, personalized education that empowers students to achieve academic excellence through innovative teaching methods, experienced tutors, and comprehensive exam preparation programs.</p>
      </article>
    </div>
  </div><br><br>
  <div class="container">
    <div class="ceo-heading">
      <h2>Our Core <span class="highlight">Values</span></h2>
    </div>

    <div class="core-grid">
      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-award'></i></div>
        <h5>Excellence</h5>
        <p>We strive for the highest standards in everything we do</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-shield'></i></div>
        <h5>Integrity</h5>
        <p>Building trust through honesty and transparent communication</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-rocket'></i></div>
        <h5>Innovation</h5>
        <p>Embracing new methods and technologies for better learning</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-hand'></i></div>
        <h5>Dedication</h5>
        <p>Committed to our students' success and growth</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-star'></i></div>
        <h5>Quality</h5>
        <p>Delivering superior educational experiences consistently</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-heart'></i></div>
        <h5>Care</h5>
        <p>Nurturing every student with personalized attention</p>
      </div>
    </div>
  </div>
</section>

<?php
// Include tutors partial (keeps heavy markup in a separate file)
include __DIR__ . '/tutors.php';

include __DIR__ . '/includes/footer.php'; ?>

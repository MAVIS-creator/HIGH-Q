<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$slug = trim($_GET['slug'] ?? '');

// Pull program with its feature list from the database
$program = null;
try {
  $stmt = $pdo->prepare("SELECT c.id, c.title, c.slug, c.description, c.price, c.duration, c.icon, c.highlight_badge, GROUP_CONCAT(cf.feature_text ORDER BY cf.position SEPARATOR '\n') AS features_list FROM courses c LEFT JOIN course_features cf ON cf.course_id = c.id WHERE c.slug = ? AND c.is_active = 1 GROUP BY c.id LIMIT 1");
  $stmt->execute([$slug]);
  $program = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $_) {}

// Show fallback if program doesn't exist
if (!$program) {
  include __DIR__ . '/includes/header.php';
  ?>
  <div class="container" style="padding: 80px 0; text-align: center;">
    <h2 style="font-size: 2rem; color: var(--hq-black); margin-bottom: 16px;">Program Not Found</h2>
    <p style="color: var(--hq-gray); font-size: 1.1rem; margin-bottom: 28px;">The program you're looking for was not found. Please browse all our programs.</p>
    <a href="programs.php" style="display: inline-block; padding: 14px 32px; background: var(--hq-blue-white); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='var(--hq-yellow)';" onmouseout="this.style.background='var(--hq-blue-white)';">Browse All Programs</a>
  </div>
  <?php
  include __DIR__ . '/includes/footer.php';
  exit;
}

include __DIR__ . '/includes/header.php';
?>

<style>
  .program-detail-page {
    padding: 64px 0;
    background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
  }

  .program-breadcrumb a {
    color: var(--hq-blue-white);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: color 0.2s ease;
    font-size: 0.95rem;
  }

  .program-breadcrumb a:hover {
    color: var(--hq-yellow);
  }

  .program-hero {
    margin-bottom: 48px;
    padding: 48px;
    background: linear-gradient(135deg, var(--hq-blue-white) 0%, var(--hq-yellow) 100%);
    border-radius: 16px;
    color: #ffffff;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
  }

  .program-hero h1 {
    font-size: clamp(2rem, 2.6vw, 2.9rem);
    margin: 0 0 16px;
    font-weight: 800;
    line-height: 1.2;
  }

  .program-hero p {
    font-size: 1.05rem;
    margin: 0;
    opacity: 0.95;
    max-width: 820px;
    line-height: 1.6;
  }

  .program-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 36px;
    align-items: start;
    margin-bottom: 56px;
  }

  .program-card {
    margin-bottom: 28px;
    padding: 32px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e6e9f0;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
  }

  .program-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
  }

  .program-card h3 {
    font-size: 1.5rem;
    margin: 0 0 18px;
    color: var(--hq-black);
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
  }

  .program-card--accent-blue {
    border-left: 5px solid var(--hq-blue-white);
  }

  .program-card--accent-yellow {
    border-left: 5px solid var(--hq-yellow);
  }

  .program-card--soft-yellow {
    background: linear-gradient(135deg, var(--hq-yellow-pale) 0%, #fffbf0 100%);
    border-left: 5px solid var(--hq-yellow);
    box-shadow: 0 6px 18px rgba(245, 185, 4, 0.12);
  }

  .program-card p,
  .program-card li {
    color: var(--hq-gray);
    line-height: 1.75;
    font-size: 1rem;
  }

  .program-feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .program-feature-list li {
    padding: 14px 0 14px 36px;
    position: relative;
    border-bottom: 1px solid #f1f3f7;
  }

  .program-feature-list li:last-child {
    border-bottom: none;
  }

  .program-feature-list li span {
    position: absolute;
    left: 0;
    color: var(--hq-yellow);
    font-weight: 700;
    font-size: 1.2rem;
  }

  .quick-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
    margin-bottom: 22px;
  }

  .quick-card {
    padding: 18px;
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #f1f3f7;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  }

  .quick-card .label {
    margin: 0 0 8px;
    color: var(--hq-gray);
    font-size: 0.82rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.4px;
  }

  .quick-card .value {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--hq-blue-white);
  }

  .info-note {
    padding: 14px 16px;
    background: #ffffff;
    border-left: 3px solid var(--hq-blue-white);
    border-radius: 8px;
    margin-bottom: 14px;
    color: var(--hq-gray);
    font-size: 0.92rem;
  }

  .program-note {
    margin: 0;
    font-size: 0.88rem;
    color: var(--hq-gray);
    line-height: 1.6;
    padding: 14px 16px;
    background: rgba(245, 185, 4, 0.08);
    border-radius: 8px;
  }

  .program-sidebar {
    position: sticky;
    top: 16px;
    height: fit-content;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .program-cta {
    padding: 28px 22px;
    background: linear-gradient(135deg, var(--hq-blue-white) 0%, var(--hq-yellow) 100%);
    border-radius: 12px;
    color: #ffffff;
    box-shadow: 0 12px 32px rgba(57, 58, 147, 0.25);
    text-align: center;
  }

  .program-cta h4 {
    margin: 0 0 10px;
    font-size: 1.15rem;
    font-weight: 700;
  }

  .program-cta p {
    margin: 0 0 18px;
    font-size: 0.92rem;
    opacity: 0.95;
    line-height: 1.6;
  }

  .program-enroll-btn {
    display: block;
    padding: 14px 0;
    background: var(--hq-yellow);
    color: var(--hq-black);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1rem;
    transition: all 0.2s ease;
    border: 2px solid var(--hq-yellow);
    cursor: pointer;
  }

  .program-enroll-btn:hover {
    background: #ffffff;
    box-shadow: 0 12px 24px rgba(245, 185, 4, 0.3);
    transform: translateY(-2px);
  }

  .program-support {
    padding: 22px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    display: grid;
    gap: 4px;
  }

  .program-support a {
    color: var(--hq-blue-white);
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 0;
    border-bottom: 1px solid #e2e8f0;
    transition: color 0.2s ease;
  }

  .program-support a:last-child {
    border-bottom: none;
  }

  .program-support a:hover {
    color: var(--hq-yellow);
  }

  .program-stats {
    padding: 18px;
    background: #f7fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    text-align: center;
    color: var(--hq-gray);
    font-size: 0.85rem;
    line-height: 1.6;
  }

  .program-stats i {
    color: #48bb78;
    margin-right: 6px;
  }

  .program-related {
    padding: 64px 0;
    background: #fafafa;
    border-top: 1px solid #e2e8f0;
  }

  .program-related .header {
    text-align: center;
    margin-bottom: 44px;
  }

  .program-related h2 {
    font-size: 2rem;
    color: var(--hq-black);
    margin: 0 0 10px;
    font-weight: 800;
  }

  .program-related p {
    color: var(--hq-gray);
    margin: 0;
    font-size: 1rem;
  }

  .related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 28px;
  }

  .related-card {
    padding: 24px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    border-top: 4px solid var(--hq-blue-white);
  }

  .related-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 24px rgba(57, 58, 147, 0.15);
  }

  .related-card h4 {
    margin: 0 0 12px;
    color: var(--hq-black);
    font-size: 1.1rem;
    font-weight: 700;
  }

  .related-card p {
    margin: 0 0 14px;
    color: var(--hq-gray);
    font-size: 0.92rem;
    line-height: 1.6;
  }

  .related-card a {
    color: var(--hq-blue-white);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.2s ease;
  }

  .related-card a:hover {
    color: var(--hq-yellow);
  }

  @media (max-width: 1024px) {
    .program-grid {
      grid-template-columns: 1fr;
    }

    .program-sidebar {
      position: static;
    }

    .program-hero {
      text-align: center;
    }

    .program-hero p {
      margin: 0 auto;
    }
  }

  @media (max-width: 768px) {
    .program-detail-page {
      padding: 48px 0;
    }

    .program-hero {
      padding: 36px 24px;
    }

    .program-card {
      padding: 24px;
    }

    .program-grid {
      gap: 26px;
    }
  }

  @media (max-width: 560px) {
    .program-hero {
      padding: 26px 18px;
    }

    .program-feature-list li {
      padding-left: 28px;
    }

    .program-cta {
      padding: 22px 18px;
    }
  }
</style>
<section class="program-detail-page">
  <div class="container">

    <nav class="program-breadcrumb" aria-label="Breadcrumb">
      <a href="programs.php"><i class='bx bx-chevron-left' style="font-size: 1.2rem;"></i> Back to All Programs</a>
    </nav>

    <div class="program-hero">
      <h1><?= htmlspecialchars($program['title']) ?></h1>
      <p><?= htmlspecialchars($program['description'] ?: $program['title'] . ' - Transform your academic success') ?></p>
    </div>

    <div class="program-grid">
      <div class="program-main">
        <section class="program-card program-card--accent-blue">
          <h3><i class='bx bx-book-open' style="font-size: 1.8rem; color: var(--hq-blue-white);"></i>Program Overview</h3>
          <p><?= htmlspecialchars($program['description'] ?: $program['title'] . ' is designed to help you excel in your academic journey. Our comprehensive curriculum, experienced instructors, and proven teaching methodologies ensure your success.') ?></p>
        </section>

        <section class="program-card program-card--accent-yellow">
          <h3><i class='bx bx-check-double' style="font-size: 1.8rem; color: var(--hq-yellow);"></i>Key Features & Benefits</h3>
          <ul class="program-feature-list">
            <li><span>✓</span>Expert instructors with years of experience</li>
            <li><span>✓</span>Comprehensive study materials and resources</li>
            <li><span>✓</span>Regular mock exams and practice tests</li>
            <li><span>✓</span>One-on-one guidance and support</li>
            <li><span>✓</span>Flexible scheduling to fit your needs</li>
          </ul>
        </section>

        <section class="program-card program-card--soft-yellow">
          <h3><i class='bx bx-time' style="font-size: 1.8rem; color: var(--hq-yellow);"></i>Duration & Cost</h3>

          <div class="quick-info-grid">
            <div class="quick-card">
              <p class="label">Duration</p>
              <p class="value"><?= htmlspecialchars($program['duration'] ?: 'Flexible') ?></p>
            </div>
            <div class="quick-card">
              <p class="label">Tuition Fee</p>
              <p class="value"><?= htmlspecialchars($program['price'] ?: 'Contact us') ?></p>
            </div>
          </div>

          <div class="info-note"><strong>Additional Fees:</strong> Registration Form: ₦1,000 | Student Card: ₦1,500</div>

          <p class="program-note">
            <i class='bx bx-info-circle' style="margin-right: 6px; vertical-align: middle;"></i>
            <strong>Note:</strong> Tuition fees do not include third-party registration fees (JAMB, WAEC, NECO, university registrations, etc.). External examination charges are separate.
          </p>
        </section>
      </div>

      <aside class="program-sidebar">
        <div class="program-cta">
          <h4>Ready to Enroll?</h4>
          <p>Join hundreds of successful students. Transform your academic journey today!</p>
          <a class="program-enroll-btn" href="register.php?ref=<?= rawurlencode($program['slug']) ?>">Enroll Now</a>
          <p style="margin: 14px 0 0; font-size: 0.8rem; opacity: 0.9;">✓ 30-day money-back guarantee</p>
        </div>

        <div class="program-support">
          <p style="margin: 0 0 8px; color: var(--hq-gray); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Need Help?</p>
          <a href="contact.php"><i class='bx bx-phone' style="font-size: 1.2rem;"></i> Contact Us</a>
          <a href="programs.php"><i class='bx bx-list-ul' style="font-size: 1.2rem;"></i> All Programs</a>
        </div>

        <div class="program-stats">
          <p style="margin: 0;">
            <i class='bx bx-check-circle'></i><strong>305</strong> Highest JAMB Score<br>
            <i class='bx bx-check-circle'></i><strong>1000+</strong> Students Trained<br>
            <i class='bx bx-check-circle'></i><strong>6+</strong> Years Excellence
          </p>
        </div>
      </aside>
    </div>

  </div>
</section>

<section class="program-related">
  <div class="container">
    <div class="header">
      <h2>Explore Other <span style="color: var(--hq-blue-white);">Programs</span></h2>
      <p>Discover all the educational solutions we offer</p>
    </div>
    <div class="related-grid">
      <?php
      try {
        $related = $pdo->prepare("SELECT id, title, slug, description FROM courses WHERE is_active = 1 AND slug != ? ORDER BY RAND() LIMIT 3");
        $related->execute([$program['slug']]);
        $relatedPrograms = $related->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($relatedPrograms as $prog) {
      ?>
        <div class="related-card">
          <h4><?= htmlspecialchars($prog['title']) ?></h4>
          <p><?= substr(htmlspecialchars($prog['description'] ?: 'Excellent educational program'), 0, 90) ?>...</p>
          <a href="program.php?slug=<?= htmlspecialchars($prog['slug']) ?>">Learn More <i class='bx bx-chevron-right'></i></a>
        </div>
      <?php 
        }
      } catch (Throwable $_) {
        // Silently fail if query fails
      }
      ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

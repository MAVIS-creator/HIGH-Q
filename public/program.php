<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$slug = trim($_GET['slug'] ?? '');
// Fetch program from database

$program = null;
try {
  $stmt = $pdo->prepare("SELECT id, title, slug, description, price, duration FROM courses WHERE slug = ? AND is_active = 1");
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

<section class="program-detail" style="padding: 64px 0; background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);">
  <div class="container">

    <!-- Breadcrumb Navigation -->
    <nav style="margin-bottom: 32px;">
      <a href="programs.php" style="color: var(--hq-blue-white); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; font-size: 0.95rem;" onmouseover="this.style.color='var(--hq-yellow)';" onmouseout="this.style.color='var(--hq-blue-white)';">
        <i class='bx bx-chevron-left' style="font-size: 1.2rem;"></i> Back to All Programs
      </a>
    </nav>

    <!-- Program Header Hero -->
    <div class="program-header" style="margin-bottom: 56px; padding: 48px; background: linear-gradient(135deg, var(--hq-blue-white) 0%, var(--hq-yellow) 100%); border-radius: 16px; color: white; box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);">
      <h1 style="font-size: 2.8rem; margin: 0 0 20px; font-weight: 800; line-height: 1.2;"><?= htmlspecialchars($program['title']) ?></h1>
      <p style="font-size: 1.15rem; margin: 0; opacity: 0.95; max-width: 750px; line-height: 1.6;"><?= htmlspecialchars($program['description'] ?: $program['title'] . ' - Transform your academic success') ?></p>
    </div>

    <!-- Main Content Grid -->
    <div class="program-detail-grid" style="display: grid; grid-template-columns: 1fr 340px; gap: 48px; margin-bottom: 56px;">
      
      <!-- Left Column: Main Content -->
      <div class="program-detail-main">
        
        <!-- Program Overview Section -->
        <section class="program-section" style="margin-bottom: 40px; padding: 36px; background: white; border-radius: 12px; border-left: 5px solid var(--hq-blue-white); box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06); transition: all 0.3s;">
          <h3 style="font-size: 1.5rem; margin: 0 0 24px; color: var(--hq-black); display: flex; align-items: center; gap: 12px; font-weight: 700;">
            <i class='bx bx-book-open' style="font-size: 1.8rem; color: var(--hq-blue-white);"></i>
            Program Overview
          </h3>
          <p style="color: var(--hq-gray); line-height: 1.8; font-size: 1rem; margin: 0;">
            <?= htmlspecialchars($program['description'] ?: $program['title'] . ' is designed to help you excel in your academic journey. Our comprehensive curriculum, experienced instructors, and proven teaching methodologies ensure your success.') ?>
          </p>
        </section>

        <!-- Key Features Section -->
        <section class="program-section" style="margin-bottom: 40px; padding: 36px; background: white; border-radius: 12px; border-left: 5px solid var(--hq-yellow); box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);">
          <h3 style="font-size: 1.5rem; margin: 0 0 24px; color: var(--hq-black); display: flex; align-items: center; gap: 12px; font-weight: 700;">
            <i class='bx bx-check-double' style="font-size: 1.8rem; color: var(--hq-yellow);"></i>
            Key Features & Benefits
          </h3>
          <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="padding: 14px 0; padding-left: 36px; position: relative; color: var(--hq-gray); line-height: 1.7; font-size: 0.95rem; border-bottom: 1px solid #f0f0f0; transition: all 0.2s;">
              <span style="position: absolute; left: 0; color: var(--hq-yellow); font-weight: 700; font-size: 1.2rem;">✓</span>
              Expert instructors with years of experience
            </li>
            <li style="padding: 14px 0; padding-left: 36px; position: relative; color: var(--hq-gray); line-height: 1.7; font-size: 0.95rem; border-bottom: 1px solid #f0f0f0;">
              <span style="position: absolute; left: 0; color: var(--hq-yellow); font-weight: 700; font-size: 1.2rem;">✓</span>
              Comprehensive study materials and resources
            </li>
            <li style="padding: 14px 0; padding-left: 36px; position: relative; color: var(--hq-gray); line-height: 1.7; font-size: 0.95rem; border-bottom: 1px solid #f0f0f0;">
              <span style="position: absolute; left: 0; color: var(--hq-yellow); font-weight: 700; font-size: 1.2rem;">✓</span>
              Regular mock exams and practice tests
            </li>
            <li style="padding: 14px 0; padding-left: 36px; position: relative; color: var(--hq-gray); line-height: 1.7; font-size: 0.95rem; border-bottom: 1px solid #f0f0f0;">
              <span style="position: absolute; left: 0; color: var(--hq-yellow); font-weight: 700; font-size: 1.2rem;">✓</span>
              One-on-one guidance and support
            </li>
            <li style="padding: 14px 0; padding-left: 36px; position: relative; color: var(--hq-gray); line-height: 1.7; font-size: 0.95rem;">
              <span style="position: absolute; left: 0; color: var(--hq-yellow); font-weight: 700; font-size: 1.2rem;">✓</span>
              Flexible scheduling to fit your needs
            </li>
          </ul>
        </section>

        <!-- Duration & Fees Info Section -->
        <section class="program-section" style="margin-bottom: 40px; padding: 36px; background: linear-gradient(135deg, var(--hq-yellow-pale) 0%, #fffbf0 100%); border-radius: 12px; border-left: 5px solid var(--hq-yellow); box-shadow: 0 4px 16px rgba(245, 185, 4, 0.1);">
          <h3 style="font-size: 1.5rem; margin: 0 0 24px; color: var(--hq-black); display: flex; align-items: center; gap: 12px; font-weight: 700;">
            <i class='bx bx-time' style="font-size: 1.8rem; color: var(--hq-yellow);"></i>
            Duration & Cost
          </h3>
          
          <!-- Quick Info Cards -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px;">
            <div style="padding: 20px; background: white; border-radius: 10px; border: 1px solid #ffe680; box-shadow: 0 2px 8px rgba(245,185,4,0.1);">
              <p style="margin: 0 0 8px; color: var(--hq-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Duration</p>
              <p style="margin: 0; font-size: 1.35rem; font-weight: 800; color: var(--hq-black);"><?= htmlspecialchars($program['duration'] ?: 'Flexible') ?></p>
            </div>
            <div style="padding: 20px; background: white; border-radius: 10px; border: 1px solid #e8d5ff; box-shadow: 0 2px 8px rgba(57,58,147,0.1);">
              <p style="margin: 0 0 8px; color: var(--hq-gray); font-size: 0.85rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Tuition Fee</p>
              <p style="margin: 0; font-size: 1.35rem; font-weight: 800; color: var(--hq-blue-white);"><?= htmlspecialchars($program['price'] ?: 'Contact us') ?></p>
            </div>
          </div>

          <!-- Additional Fees Note -->
          <div style="padding: 16px; background: white; border-left: 3px solid var(--hq-blue-white); border-radius: 8px; margin-bottom: 16px;">
            <p style="margin: 0; color: var(--hq-gray); font-size: 0.9rem; line-height: 1.6;"><strong>Additional Fees:</strong> Registration Form: ₦1,000 | Student Card: ₦1,500</p>
          </div>

          <p style="margin: 0; font-size: 0.85rem; color: var(--hq-gray); line-height: 1.7; padding: 16px; background: rgba(245,185,4,0.08); border-radius: 8px;">
            <i class='bx bx-info-circle' style="margin-right: 6px; vertical-align: middle;"></i>
            <strong>Note:</strong> Tuition fees do not include third-party registration fees (JAMB, WAEC, NECO, university registrations, etc.). External examination charges are separate.
          </p>
        </section>

      </div>

      <!-- Right Sidebar: Call-to-Action & Support -->
      <aside class="program-sidebar" style="position: sticky; top: 20px; height: fit-content;">
        
        <!-- Main CTA Box -->
        <div style="padding: 32px 24px; background: linear-gradient(135deg, var(--hq-blue-white) 0%, var(--hq-yellow) 100%); border-radius: 12px; color: white; box-shadow: 0 12px 32px rgba(57, 58, 147, 0.25); margin-bottom: 20px; text-align: center;">
          <h4 style="margin: 0 0 12px; font-size: 1.15rem; font-weight: 700;">Ready to Enroll?</h4>
          <p style="margin: 0 0 24px; font-size: 0.9rem; opacity: 0.95; line-height: 1.6;">Join hundreds of successful students. Transform your academic journey today!</p>
          
          <a href="register.php?ref=<?= rawurlencode($program['slug']) ?>" style="display: block; padding: 14px 0; background: var(--hq-yellow); color: var(--hq-black); text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 1rem; transition: all 0.2s; border: 2px solid var(--hq-yellow); cursor: pointer;" onmouseover="this.style.background='white'; this.style.boxShadow='0 12px 24px rgba(245,185,4,0.3)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='var(--hq-yellow)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';">Enroll Now</a>
          
          <p style="margin: 16px 0 0; font-size: 0.75rem; opacity: 0.85;">✓ 30-day money-back guarantee</p>
        </div>

        <!-- Support Box -->
        <div style="padding: 24px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);">
          <p style="margin: 0 0 16px; color: var(--hq-gray); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Need Help?</p>
          
          <a href="contact.php" style="color: var(--hq-blue-white); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; padding: 12px 0; border-bottom: 1px solid #e2e8f0; transition: all 0.2s;" onmouseover="this.style.color='var(--hq-yellow)';" onmouseout="this.style.color='var(--hq-blue-white)';">
            <i class='bx bx-phone' style="font-size: 1.2rem;"></i> Contact Us
          </a>
          
          <a href="programs.php" style="color: var(--hq-blue-white); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; padding: 12px 0; transition: all 0.2s;" onmouseover="this.style.color='var(--hq-yellow)';" onmouseout="this.style.color='var(--hq-blue-white)';">
            <i class='bx bx-list-ul' style="font-size: 1.2rem;"></i> All Programs
          </a>
        </div>

        <!-- Trust Indicators -->
        <div style="margin-top: 20px; padding: 20px; background: #f7fafc; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
          <p style="margin: 0; font-size: 0.8rem; color: var(--hq-gray); line-height: 1.6;">
            <i class='bx bx-check-circle' style="color: #48bb78; margin-right: 4px;"></i><strong>305</strong> Highest JAMB Score<br>
            <i class='bx bx-check-circle' style="color: #48bb78; margin-right: 4px;"></i><strong>1000+</strong> Students Trained<br>
            <i class='bx bx-check-circle' style="color: #48bb78; margin-right: 4px;"></i><strong>6+</strong> Years Excellence
          </p>
        </div>

      </aside>

    </div>

  </div>
</section>

<!-- Related Programs Section -->
<section style="padding: 64px 0; background: #fafafa; border-top: 1px solid #e2e8f0;">
  <div class="container">
    <div style="text-align: center; margin-bottom: 48px;">
      <h2 style="font-size: 2rem; color: var(--hq-black); margin: 0 0 12px;">Explore Other <span style="color: var(--hq-blue-white);">Programs</span></h2>
      <p style="color: var(--hq-gray); font-size: 1rem; margin: 0;">Discover all the educational solutions we offer</p>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px;">
      <?php
      try {
        $related = $pdo->prepare("SELECT id, title, slug, description FROM courses WHERE is_active = 1 AND slug != ? ORDER BY RAND() LIMIT 3");
        $related->execute([$program['slug']]);
        $relatedPrograms = $related->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($relatedPrograms as $prog) {
      ?>
        <div style="padding: 28px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); transition: all 0.3s; border-top: 4px solid var(--hq-blue-white);" onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 24px rgba(57,58,147,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.06)';">
          <h4 style="margin: 0 0 12px; color: var(--hq-black); font-size: 1.1rem; font-weight: 700;"><?= htmlspecialchars($prog['title']) ?></h4>
          <p style="margin: 0 0 16px; color: var(--hq-gray); font-size: 0.9rem; line-height: 1.6;"><?= substr(htmlspecialchars($prog['description'] ?: 'Excellent educational program'), 0, 90) ?>...</p>
          <a href="program.php?slug=<?= htmlspecialchars($prog['slug']) ?>" style="color: var(--hq-blue-white); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.color='var(--hq-yellow)';" onmouseout="this.style.color='var(--hq-blue-white)';">Learn More <i class='bx bx-chevron-right'></i></a>
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

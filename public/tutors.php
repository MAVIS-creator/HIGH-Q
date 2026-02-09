<?php
// public/tutors.php - partial for displaying featured tutors
$tutors = [];
// load DB connection if available
if (file_exists(__DIR__ . '/config/db.php')) {
  try {
    require_once __DIR__ . '/config/db.php';
    if (isset($pdo)) {
      // Try featured tutors first (preserve featured if any), but when falling back or listing ensure tutors are ordered by id ascending per UX request
      // Show all featured tutors, oldest first (no limit)
      $stmt = $pdo->prepare("SELECT * FROM tutors WHERE is_featured=1 ORDER BY created_at ASC");
      $stmt->execute();
      $tutors = $stmt->fetchAll();
      // If none are featured, fall back to any tutors so the section is visible for testing
      if (empty($tutors)) {
        // order by id ascending as requested (oldest first)
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

<?php
if (!function_exists('hq_normalize_student_name')) {
  function hq_normalize_student_name($name) {
    $name = strtolower(trim($name ?? ''));
    $name = preg_replace('/\s+/', ' ', $name);
    return $name;
  }
}

if (!function_exists('hq_build_student_feature_image_map')) {
  function hq_build_student_feature_image_map($csvPath, $csvDir) {
    if (!is_file($csvPath) || !is_dir($csvDir)) {
      return [];
    }

    $files = array_diff(scandir($csvDir), ['.', '..']);
    $filesByBase = [];
    foreach ($files as $file) {
      if (is_dir($csvDir . DIRECTORY_SEPARATOR . $file)) {
        continue;
      }
      $base = pathinfo($file, PATHINFO_FILENAME);
      $base = strtolower(preg_replace('/\s+/', ' ', $base));
      $filesByBase[$base] = $file;
    }

    $handle = fopen($csvPath, 'r');
    if ($handle === false) {
      return [];
    }

    $header = fgetcsv($handle);
    if (empty($header)) {
      fclose($handle);
      return [];
    }
    $header = array_map('trim', $header);

    $nameIndex = array_search('Full Name', $header, true);
    if ($nameIndex === false) {
      foreach ($header as $i => $h) {
        if (stripos($h, 'Full Name') !== false) {
          $nameIndex = $i;
          break;
        }
      }
    }

    $picIndex = array_search('Upload your picture (PIC)', $header, true);
    if ($picIndex === false) {
      foreach ($header as $i => $h) {
        if (stripos($h, 'Upload your picture') !== false) {
          $picIndex = $i;
          break;
        }
      }
    }

    if ($nameIndex === false || $picIndex === false) {
      fclose($handle);
      return [];
    }

    $map = [];
    while (($row = fgetcsv($handle)) !== false) {
      $nameRaw = trim($row[$nameIndex] ?? '');
      $picRaw = trim($row[$picIndex] ?? '');
      if ($nameRaw === '' || $picRaw === '') {
        continue;
      }

      $picKey = strtolower(preg_replace('/\s+/', ' ', $picRaw));
      $matchedFile = $filesByBase[$picKey] ?? null;
      if (!$matchedFile) {
        continue;
      }

      $map[hq_normalize_student_name($nameRaw)] = 'uploads/HQ Student Feature Submission.csv (1)/' . $matchedFile;
    }

    fclose($handle);
    return $map;
  }
}
?>

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

    $needsImageMap = false;
    foreach ($wallTestimonials as $t) {
      if (empty($t['image_path'])) {
        $needsImageMap = true;
        break;
      }
    }

    if ($needsImageMap) {
      $csvDir = __DIR__ . '/uploads/HQ Student Feature Submission.csv (1)';
      $csvPath = $csvDir . '/HQ Student Feature Submission.csv';
      $imageMap = hq_build_student_feature_image_map($csvPath, $csvDir);
      if (!empty($imageMap)) {
        foreach ($wallTestimonials as &$t) {
          if (!empty($t['image_path'])) {
            continue;
          }
          $key = hq_normalize_student_name($t['name'] ?? '');
          if (isset($imageMap[$key])) {
            $t['image_path'] = $imageMap[$key];
          }
        }
        unset($t);
      }
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
  overflow: visible;
}

.wall-scroll-wrapper {
  position: relative;
  max-width: 100%;
  margin: 0 auto;
  padding: 0 50px;
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
  
  .wall-scroll-wrapper {
    padding: 0 40px;
  }
  
  .wall-testimony-card {
    flex: 0 0 280px;
  }
}
</style>

<!-- Video Testimonials Section -->
<section class="video-testimonials-section py-5">
  <div class="container">
    <div class="ceo-heading text-center mb-4">
      <h2>Student <span class="highlight">Video Stories</span></h2>
      <p>Watch real testimonials from our successful students</p>
    </div>
    
    <div class="video-grid">
      <?php
      $videoDir = 'uploads/others-multi (1)/';
      $videos = [
        'VID-20260112-WA0040.mp4',
        'VID-20260112-WA0041.mp4',
        'VID-20260112-WA0043.mp4',
        'VID-20260112-WA0044.mp4',
        'VID-20260112-WA0046.mp4'
      ];
      foreach ($videos as $index => $video):
      ?>
      <div class="video-card" data-video-index="<?= $index ?>">
        <video 
          class="video-player"
          src="<?= htmlspecialchars($videoDir . $video) ?>"
          muted
          playsinline
          preload="metadata"
          poster=""
        ></video>
        <div class="video-overlay">
          <div class="video-play-btn">
            <i class='bx bx-play'></i>
          </div>
          <p class="video-hint">Click to unmute & play</p>
        </div>
        <div class="video-unmute-indicator" style="display:none;">
          <i class='bx bx-volume-full'></i> Sound On
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<style>
.video-testimonials-section {
  background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%);
}

.video-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

.video-card {
  position: relative;
  border-radius: 16px;
  overflow: hidden;
  background: #0b1a2c;
  aspect-ratio: 9/16;
  max-height: 400px;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.video-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px rgba(0,0,0,0.25);
}

.video-player {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.video-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  transition: opacity 0.3s ease;
}

.video-card.playing .video-overlay {
  opacity: 0;
  pointer-events: none;
}

.video-play-btn {
  width: 70px;
  height: 70px;
  background: rgba(255, 214, 0, 0.95);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.3s ease, background 0.3s ease;
}

.video-play-btn i {
  font-size: 36px;
  color: #0b1a2c;
  margin-left: 4px;
}

.video-card:hover .video-play-btn {
  transform: scale(1.1);
  background: #ffd600;
}

.video-hint {
  color: white;
  font-size: 0.85rem;
  margin-top: 12px;
  opacity: 0.9;
}

.video-unmute-indicator {
  position: absolute;
  bottom: 16px;
  left: 16px;
  background: rgba(255, 214, 0, 0.95);
  color: #0b1a2c;
  padding: 8px 14px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
}

.video-unmute-indicator i {
  font-size: 16px;
}

@media (max-width: 768px) {
  .video-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }
  
  .video-card {
    max-height: 300px;
  }
  
  .video-play-btn {
    width: 50px;
    height: 50px;
  }
  
  .video-play-btn i {
    font-size: 24px;
  }
}

@media (max-width: 480px) {
  .video-grid {
    grid-template-columns: 1fr;
  }
  
  .video-card {
    max-height: 450px;
  }
}
</style>

<script>
// Video testimonials interaction
document.addEventListener('DOMContentLoaded', function() {
  const videoCards = document.querySelectorAll('.video-card');
  
  videoCards.forEach(card => {
    const video = card.querySelector('.video-player');
    const overlay = card.querySelector('.video-overlay');
    const unmuteIndicator = card.querySelector('.video-unmute-indicator');
    let isUnmuted = false;
    
    // Hover: Play muted (desktop only)
    card.addEventListener('mouseenter', () => {
      if (window.innerWidth > 768 && !isUnmuted) {
        video.muted = true;
        video.play().catch(() => {});
        card.classList.add('playing');
      }
    });
    
    card.addEventListener('mouseleave', () => {
      if (!isUnmuted) {
        video.pause();
        video.currentTime = 0;
        card.classList.remove('playing');
      }
    });
    
    // Click: Toggle unmute and play with controls
    card.addEventListener('click', () => {
      // Pause all other videos
      videoCards.forEach(otherCard => {
        if (otherCard !== card) {
          const otherVideo = otherCard.querySelector('.video-player');
          const otherIndicator = otherCard.querySelector('.video-unmute-indicator');
          otherVideo.pause();
          otherVideo.muted = true;
          otherCard.classList.remove('playing');
          otherIndicator.style.display = 'none';
        }
      });
      
      if (isUnmuted) {
        // Already unmuted, pause
        video.pause();
        video.muted = true;
        isUnmuted = false;
        card.classList.remove('playing');
        unmuteIndicator.style.display = 'none';
      } else {
        // Unmute and play
        video.muted = false;
        video.controls = true;
        video.play().catch(() => {});
        isUnmuted = true;
        card.classList.add('playing');
        unmuteIndicator.style.display = 'flex';
        
        // Hide indicator after 2 seconds
        setTimeout(() => {
          unmuteIndicator.style.display = 'none';
        }, 2000);
      }
    });
    
    // When video ends, reset state
    video.addEventListener('ended', () => {
      isUnmuted = false;
      video.muted = true;
      video.controls = false;
      card.classList.remove('playing');
      unmuteIndicator.style.display = 'none';
    });
  });
});
</script>

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

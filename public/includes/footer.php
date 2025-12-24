<!-- ===== FOOTER START ===== -->
<footer class="site-footer">
  <div class="container footer-grid">

    <!-- Logo & About -->
  <div class="footer-about">
      <div class="logo">
  <img src="<?= app_url('assets/images/hq-logo.jpeg') ?>" alt="HQ Logo" class="brand-logo">
        <div>
          <h2>HIGH Q SOLID ACADEMY</h2>
          <small>Limited</small>
        </div>
      </div>
      <p>
        Nigeria’s premier tutorial academy committed to academic excellence and 
        student success since 2018.
      </p>
      <p class="motto">"<span>Always Ahead of Others</span>"</p>
      <div class="socials">
        <?php
          $socials = [];
          try { 
            $stmt = $pdo->query("SELECT social_links, contact_facebook, contact_tiktok, contact_twitter, contact_instagram FROM site_settings LIMIT 1");
            if ($stmt) {
              $s = $stmt->fetch(PDO::FETCH_ASSOC);
              if ($s) {
                // Try structured social_links first
                if (!empty($s['social_links'])) {
                  $decoded = json_decode($s['social_links'], true);
                  if (is_array($decoded)) {
                    $socials = $decoded;
                  }
                }
                
                // Fallback to legacy columns if needed
                if (empty($socials['facebook']) && !empty($s['contact_facebook'])) {
                  $socials['facebook'] = $s['contact_facebook'];
                }
                if (empty($socials['instagram']) && !empty($s['contact_instagram'])) {
                  $socials['instagram'] = $s['contact_instagram'];
                }
                if (empty($socials['tiktok'])) {
                  if (!empty($s['contact_tiktok'])) {
                    $socials['tiktok'] = $s['contact_tiktok'];
                  } elseif (!empty($s['contact_twitter'])) {
                    $socials['tiktok'] = $s['contact_twitter'];
                  }
                }
              }
            }
          } catch(Throwable $_) {
            // Silently fail and use defaults
          }

          // Ensure we have some default social links if none are set
          if (empty($socials)) {
            $socials = [
              'facebook' => 'https://facebook.com/highqsolidacademy',
              'instagram' => 'https://instagram.com/highqsolidacademy',
              'tiktok' => 'https://tiktok.com/@highqsolidacademy'
            ];
          }
        ?>
        
        <a href="<?= htmlspecialchars($socials['facebook'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" class="social-link facebook" aria-label="Facebook">
          <i class="bx bxl-facebook-circle"></i>
        </a>
        
        <a href="<?= htmlspecialchars($socials['instagram'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" class="social-link instagram" aria-label="Instagram">
          <i class="bx bxl-instagram-alt"></i>
        </a>
        
        <a href="<?= htmlspecialchars($socials['tiktok'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" class="social-link tiktok" aria-label="TikTok">
          <i class="bx bxl-tiktok"></i>
        </a>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="footer-links">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="<?= app_url('index.php') ?>">Home</a></li>
        <li><a href="<?= app_url('about.php') ?>">About Us</a></li>
        <li><a href="<?= app_url('programs.php') ?>">Programs</a></li>
        <li><a href="<?= app_url('register.php') ?>">Admission</a></li>
        <li><a href="<?= app_url('contact.php') ?>">Contact</a></li>
      </ul>
    </div>

    <!-- Programs -->
      <div class="footer-programs">
      <h3>Our Programs</h3>
      <ul>
        <?php
          try {
            $progs = $pdo->query("SELECT title, slug FROM courses WHERE is_active=1 ORDER BY title LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
          } catch(Throwable $_) { $progs = []; }
          if (!empty($progs)) {
            foreach ($progs as $p) {
              $slug = $p['slug'] ?: 'programs.php';
              echo '<li><a href="' . app_url('program.php?slug=' . htmlspecialchars($slug)) . '">' . htmlspecialchars($p['title']) . '</a></li>';
            }
          } else {
            // fallback static links
            echo '<li><a href="' . app_url('programs.php') . '">JAMB/Post-UTME</a></li><li><a href="' . app_url('programs.php') . '">WAEC/NECO</a></li><li><a href="' . app_url('programs.php') . '">Digital Skills Training</a></li>';
          }
        ?>
      </ul>
    </div>

    <!-- Contact -->
    <div class="footer-contact">
      <h3>Contact Information</h3>
      <div class="address-box">
        <strong><i class="fas fa-map-marker-alt"></i> Tutorial Address</strong>
        <p>8 Pineapple Avenue, Aiyetoro<br>Ikorodu North LCDA, Maya, Ikorodu</p>
      </div>
      <div class="address-box">
        <strong><i class="fas fa-building"></i> Area Office</strong>
        <p>Shop 3, 17, 18, World Star Complex<br>Opposite London Street, Aiyetoro Maya, Ikorodu, Lagos State</p>
      </div>
      <?php
        try { $ss = $pdo->query("SELECT contact_phone, contact_email FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC); } catch(Throwable $_) { $ss = []; }
        $fphone = $ss['contact_phone'] ?? '0807 208 8794';
        $femail = $ss['contact_email'] ?? 'info@hqacademy.com';
      ?>
      <p><i class="fas fa-phone"></i> <?= htmlspecialchars($fphone) ?></p>
      <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($femail) ?></p>
      <p><i class="fas fa-clock"></i> Mon - Fri: 8:00 AM - 6:00 PM<br>Sat: 9:00 AM - 4:00 PM</p>
    </div>

  </div>

  <!-- Bottom -->
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> High Q Solid Academy Limited. All rights reserved.</p>
    <div class="links">
      <a href="<?= app_url('privacy.php') ?>">Privacy Policy</a>
      <a href="<?= app_url('terms.php') ?>">Terms of Service</a>
    </div>
  </div>
</footer>
<!-- ===== FOOTER END ===== -->
</main>

<!-- Floating Live Chat Button -->
<a href="<?= app_url('contact.php#livechat') ?>" class="floating-chat" aria-label="Live Chat with us">
  <i class="bx bx-chat"></i>
</a>
<script src="<?= app_url('assets/js/viewport-inview.js') ?>"></script>
<script src="<?= app_url('assets/js/contact-helpers.js') ?>"></script>
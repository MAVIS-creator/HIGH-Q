<!-- ===== FOOTER START ===== -->
<footer class="site-footer">
  <div class="container footer-grid">

    <!-- Logo & About -->
  <div class="footer-about">
      <div class="logo">
        <img src="./assets/images/hq-logo.jpeg" alt="HQ Logo" class="brand-logo">
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
          try { $s = $pdo->query("SELECT social_links, contact_facebook, contact_tiktok, contact_twitter, contact_instagram FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC); if(!empty($s['social_links'])) $socials = json_decode($s['social_links'], true) ?: []; 
            // If structured keys are empty, fall back to legacy contact_* columns
            if (empty($socials['facebook']) && !empty($s['contact_facebook'])) $socials['facebook'] = $s['contact_facebook'];
            if (empty($socials['instagram']) && !empty($s['contact_instagram'])) $socials['instagram'] = $s['contact_instagram'];
            // TikTok: prefer explicit tiktok key, then contact_tiktok column, otherwise fallback to contact_twitter for older installs
            if (empty($socials['tiktok']) && !empty($s['contact_tiktok'])) $socials['tiktok'] = $s['contact_tiktok'];
            if (empty($socials['tiktok']) && !empty($s['contact_twitter'])) $socials['tiktok'] = $s['contact_twitter'];
          } catch(Throwable $_) {}
        ?>
        <?php if (!empty($socials['facebook'])): ?>
          <a href="<?= htmlspecialchars($socials['facebook']) ?>" aria-label="Facebook">
            <!-- Inline Facebook SVG -->
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M22 12.07C22 6.59 17.52 2 12 2S2 6.59 2 12.07C2 17.09 5.66 21.19 10.44 22v-7.03H7.9v-2.9h2.54V9.41c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.23.2 2.23.2v2.45h-1.25c-1.23 0-1.62.77-1.62 1.56v1.87h2.77l-.44 2.9h-2.33V22C18.34 21.19 22 17.09 22 12.07z"/></svg>
          </a>
        <?php endif; ?>
        <?php if (!empty($socials['instagram'])): ?>
          <a href="<?= htmlspecialchars($socials['instagram']) ?>" aria-label="Instagram">
            <!-- Inline Instagram SVG -->
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M7 2h10a5 5 0 015 5v10a5 5 0 01-5 5H7a5 5 0 01-5-5V7a5 5 0 015-5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 8.8a3.2 3.2 0 100 6.4 3.2 3.2 0 000-6.4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M17.5 6.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </a>
        <?php endif; ?>
        <?php if (!empty($socials['tiktok'])): ?>
          <a href="<?= htmlspecialchars($socials['tiktok']) ?>" aria-label="TikTok">
            <!-- Inline TikTok SVG (brand) -->
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12.5 3v7.2a3.3 3.3 0 01-3.3-3.3V19a4 4 0 11-4-4c0-4.4 3.6-8 8-8V3h3v4h-1.5V3z"/></svg>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="footer-links">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About Us</a></li>
        <li><a href="programs.php">Programs</a></li>
        <li><a href="register.php">Admission</a></li>
        <li><a href="contact.php">Contact</a></li>
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
              echo '<li><a href="program.php?slug=' . htmlspecialchars($slug) . '">' . htmlspecialchars($p['title']) . '</a></li>';
            }
          } else {
            // fallback static links
            echo '<li><a href="programs.php">JAMB/Post-UTME</a></li><li><a href="programs.php">WAEC/NECO</a></li><li><a href="programs.php">Digital Skills Training</a></li>';
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
      <a href="privacy.php">Privacy Policy</a>
      <a href="terms.php">Terms of Service</a>
    </div>
  </div>
</footer>
<!-- ===== FOOTER END ===== -->
</main>

<!-- Floating Live Chat Button -->
<a href="contact.php#livechat" class="floating-chat" aria-label="Live Chat with us">
  <i class="bx bx-chat"></i>
</a>

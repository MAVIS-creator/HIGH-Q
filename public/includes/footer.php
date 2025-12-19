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
            // fallback static links with working slugs
            $fallbackPrograms = [
              ['title' => 'JAMB Preparation', 'slug' => 'jamb-preparation'],
              ['title' => 'WAEC Preparation', 'slug' => 'waec-preparation'],
              ['title' => 'NECO Preparation', 'slug' => 'neco-preparation'],
              ['title' => 'Post-UTME', 'slug' => 'post-utme'],
              ['title' => 'Special Tutorials', 'slug' => 'special-tutorials'],
              ['title' => 'Computer Training', 'slug' => 'computer-training'],
            ];
            foreach ($fallbackPrograms as $fp) {
              echo '<li><a href="' . app_url('program.php?slug=' . urlencode($fp['slug'])) . '">' . htmlspecialchars($fp['title']) . '</a></li>';
            }
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
    <div class="links" style="gap:10px; flex-wrap:wrap;">
      <a href="https://github.com/MAVIS-creator" target="_blank" rel="noopener noreferrer" style="background:linear-gradient(90deg,#ffd54f,#3b82f6); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-weight:700;">Made by MAVIS</a>
      <span style="color:#cbd5e1;">•</span>
      <a href="https://github.com/gamerdave-web" target="_blank" rel="noopener noreferrer">Exam portal by GamerDave</a>
    </div>
    <div class="links">
      <a href="<?= app_url('privacy.php') ?>">Privacy Policy</a>
      <a href="<?= app_url('terms.php') ?>">Terms of Service</a>
    </div>
  </div>
</footer>
<!-- ===== FOOTER END ===== -->
</main>

<!-- Floating Live Chat Button -->
<button id="globalFloatChat" class="floating-chat" aria-label="Live Chat with us">
  <i class="bx bx-chat"></i>
  <span class="chat-badge" style="display:none;position:absolute;top:-4px;right:-4px;background:#ff3333;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:bold;display:none;align-items:center;justify-content:center;"></span>
</button>

<!-- Chat widget modal positioned bottom-right (accessible from all pages) -->
<div id="globalChatModal" style="display:none;position:fixed;bottom:90px;right:20px;z-index:9998;width:400px;max-width:calc(100vw - 40px);height:600px;max-height:calc(100vh - 110px);box-shadow:0 12px 48px rgba(0,0,0,0.25);border-radius:16px;overflow:hidden;background:#fff;">
	<div style="width:100%;height:100%;position:relative;">
		<button id="globalCloseChatModal" aria-label="Close chat" style="position:absolute;right:8px;top:8px;border:none;background:rgba(255,255,255,0.95);padding:8px 10px;border-radius:50%;cursor:pointer;z-index:3;box-shadow:0 4px 12px rgba(0,0,0,0.15);font-size:18px;line-height:1;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-weight:bold;color:#333;transition:all 0.2s ease;"><i class="bx bx-x"></i></button>
		<iframe id="globalChatIframe" src="<?= app_url('chatbox.php') ?>" style="width:100%;height:100%;border:0;display:block;" title="Live Chat"></iframe>
	</div>
</div>

<style>
/* Mobile chat positioning */
@media (max-width: 700px) {
	#globalChatModal {
		bottom: 75px !important;
		right: 12px !important;
		width: calc(100vw - 24px) !important;
		height: calc(100vh - 95px) !important;
		max-height: 550px !important;
	}
}
#globalCloseChatModal:hover {
	background: rgba(255,255,255,1);
	transform: scale(1.1);
}
.floating-chat {
	position: fixed;
	bottom: 20px;
	right: 20px;
	z-index: 9999;
	width: 60px;
	height: 60px;
	border-radius: 50%;
	background: linear-gradient(135deg, #ffd600 0%, #f6c23a 100%);
	color: #111;
	border: none;
	cursor: pointer;
	box-shadow: 0 8px 24px rgba(255, 214, 0, 0.4);
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 28px;
	animation: pulse 2s infinite;
}
.floating-chat:hover {
	transform: scale(1.1);
	box-shadow: 0 12px 32px rgba(255, 214, 0, 0.6);
}
@keyframes pulse {
	0%, 100% { box-shadow: 0 8px 24px rgba(255, 214, 0, 0.4); }
	50% { box-shadow: 0 8px 32px rgba(255, 214, 0, 0.7); }
}
@media (max-width: 700px) {
	.floating-chat {
		bottom: 15px;
		right: 15px;
		width: 55px;
		height: 55px;
		font-size: 24px;
	}
}
</style>

<script>
// Global chat functionality - works on all pages
(function() {
	var chatModal = document.getElementById('globalChatModal');
	var chatIframe = document.getElementById('globalChatIframe');
	var floatBtn = document.getElementById('globalFloatChat');
	var closeBtn = document.getElementById('globalCloseChatModal');
	var badge = document.querySelector('.chat-badge');
	
	function getCookie(name) {
		var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
		return match ? match[2] : null;
	}
	
	function openChatModal() {
		if(chatModal) {
			chatModal.style.display = 'block';
			chatModal.setAttribute('aria-hidden', 'false');
			// Reload iframe to ensure fresh state and proper landing panel
			if(chatIframe) {
				var src = chatIframe.src;
				chatIframe.src = src;
			}
			// Post focus message after iframe loads
			setTimeout(function() {
				if(chatIframe && chatIframe.contentWindow) {
					chatIframe.contentWindow.postMessage({hq_chat_action:'focus'}, '*');
				}
			}, 300);
		}
	}
	
	function closeChatModal() {
		if(chatModal) {
			chatModal.style.display = 'none';
			chatModal.setAttribute('aria-hidden', 'true');
			if(chatIframe && chatIframe.contentWindow) {
				chatIframe.contentWindow.postMessage({hq_chat_action:'close'}, '*');
			}
		}
	}
	
	function updateBadge() {
		try {
			var thread = getCookie('hq_thread_id');
			if(thread && badge) {
				badge.style.display = 'flex';
			} else if(badge) {
				badge.style.display = 'none';
			}
		} catch(e) {}
	}
	
	// Event listeners
	if(floatBtn) {
		floatBtn.addEventListener('click', function(e) {
			e.preventDefault();
			openChatModal();
		});
	}
	
	if(closeBtn) {
		closeBtn.addEventListener('click', function(e) {
			e.preventDefault();
			closeChatModal();
		});
	}
	
	// Listen for messages from iframe
	window.addEventListener('message', function(ev) {
		try {
			if(ev.data && ev.data.hq_chat_action === 'close') {
				closeChatModal();
			}
		} catch(e) {}
	});
	
	// Auto-open if URL has #livechat hash
	if(window.location.hash === '#livechat') {
		openChatModal();
	}
	
	// Update badge on load
	updateBadge();
	
	// Poll for new messages if thread exists
	var threadId = getCookie('hq_thread_id');
	if(threadId) {
		setInterval(updateBadge, 30000); // Check every 30 seconds
	}
})();
</script>

<script src="<?= app_url('assets/js/viewport-inview.js') ?>"></script>
<script src="<?= app_url('assets/js/contact-helpers.js') ?>"></script>
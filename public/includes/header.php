<?php
// public/includes/header.php - lightweight public header
// Attempt to load site settings (contact phone, bank details, etc.) from database
$contact_phone = '0807 208 8794';
$siteSettings = [
  'site' => ['name' => 'HIGH Q SOLID ACADEMY', 'bank_name' => '', 'bank_account_name' => '', 'bank_account_number' => ''],
  'contact' => ['phone' => $contact_phone, 'email' => 'info@hqacademy.com', 'address' => "8 Pineapple Avenue, Aiyetoro\nMaya, Ikorodu"]
];
// include DB connection if available
if (file_exists(__DIR__ . '/../config/db.php')) {
  try {
    require_once __DIR__ . '/../config/db.php';
    if (isset($pdo)) {
      // Try to fetch the structured site_settings row first
      try {
        $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
          // populate contact phone if available
          if (!empty($row['contact_phone'])) $contact_phone = $row['contact_phone'];
          $siteSettings['site']['name'] = $row['site_name'] ?? $siteSettings['site']['name'];
          $siteSettings['site']['bank_name'] = $row['bank_name'] ?? '';
          $siteSettings['site']['bank_account_name'] = $row['bank_account_name'] ?? '';
          $siteSettings['site']['bank_account_number'] = $row['bank_account_number'] ?? '';
          // Backwards-compatible flat keys (some public templates expect flat structure)
          $siteSettings['bank_name'] = $siteSettings['site']['bank_name'];
          $siteSettings['bank_account_name'] = $siteSettings['site']['bank_account_name'];
          $siteSettings['bank_account_number'] = $siteSettings['site']['bank_account_number'];
          $siteSettings['contact']['phone'] = $row['contact_phone'] ?? $siteSettings['contact']['phone'];
          $siteSettings['contact']['email'] = $row['contact_email'] ?? $siteSettings['contact']['email'];
          $siteSettings['contact']['address'] = $row['contact_address'] ?? $siteSettings['contact']['address'];
          $siteSettings['contact_phone'] = $siteSettings['contact']['phone'];
        }
      } catch (Throwable $e) {
        // fall back to the legacy settings table if site_settings not present
        try {
          $stmt2 = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
          $stmt2->execute(['system_settings']);
          $val = $stmt2->fetchColumn();
          if (!$val) {
            $stmt2 = $pdo->query("SELECT value FROM settings LIMIT 1");
            $val = $stmt2->fetchColumn();
          }
          $data = $val ? json_decode($val, true) : [];
          if (is_array($data)) {
            $siteSettings = array_merge($siteSettings, $data);
            if (!empty($data['site']['bank_name'])) $siteSettings['site']['bank_name'] = $data['site']['bank_name'];
            if (!empty($data['site']['bank_account_name'])) $siteSettings['site']['bank_account_name'] = $data['site']['bank_account_name'];
            if (!empty($data['site']['bank_account_number'])) $siteSettings['site']['bank_account_number'] = $data['site']['bank_account_number'];
            if (!empty($data['contact']['phone'])) $contact_phone = $data['contact']['phone'];
          }
        } catch (Throwable $e2) {
          // ignore and use defaults
        }
      }
      // If maintenance mode is enabled, and visitor is not an admin or allowlisted IP, show maintenance page
      try {
        $maintenance = false;
        // Prefer structured site_settings row if available
        if (!empty($row) && isset($row['maintenance'])) $maintenance = (bool)$row['maintenance'];
        else {
          $stmt2 = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
          $stmt2->execute(['system_settings']);
          $val = $stmt2->fetchColumn();
          $j = $val ? json_decode($val, true) : [];
          if (!empty($j['security']['maintenance'])) $maintenance = (bool)$j['security']['maintenance'];
        }

        // Build allowlist of IPs from structured row or legacy settings (comma-separated)
        $allowedIps = [];
        if (!empty($row) && !empty($row['maintenance_allowed_ips'])) {
          $allowedIps = array_filter(array_map('trim', explode(',', $row['maintenance_allowed_ips'])));
        } else {
          $ipCandidates = $j['security']['maintenance_allowed_ips'] ?? ($j['security']['allowed_ips'] ?? null);
          if (!empty($ipCandidates)) {
            if (is_array($ipCandidates)) $allowedIps = $ipCandidates;
            else $allowedIps = array_filter(array_map('trim', explode(',', $ipCandidates)));
          }
        }

        // Decide whether the visitor is a logged-in admin via flexible session checks
        $isAdminBySession = false;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
          $u = $_SESSION['user'];
          // Common shapes: ['role'] => 'admin', ['role_slug'] => 'admin', ['is_admin'] => true, ['role_id'] => 1
          if (!empty($u['is_admin'])) $isAdminBySession = true;
          if (!empty($u['role']) && strtolower($u['role']) === 'admin') $isAdminBySession = true;
          if (!empty($u['role_slug']) && strtolower($u['role_slug']) === 'admin') $isAdminBySession = true;
          if (!empty($u['role_id']) && intval($u['role_id']) === 1) $isAdminBySession = true;
        }

        // Allow a small set of admin pages (login/reset) to be accessible so an admin can sign in during maintenance
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $isAdminArea = (strpos($requestUri, '/admin') !== false);
        $isAdminAuthPage = (stripos($requestUri, '/admin/login') !== false || stripos($requestUri, '/admin/forgot') !== false || stripos($requestUri, '/admin/reset') !== false || stripos($requestUri, '/admin/signup') !== false);

        // Check IP allowlist
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipAllowed = false;
        if ($remoteIp && !empty($allowedIps)) {
          foreach ($allowedIps as $candidate) {
            if ($candidate === $remoteIp) { $ipAllowed = true; break; }
            // allow CIDR or prefix matches (simple startsWith) for convenience
            if (strpos($candidate, '/') !== false) {
              // skip complex CIDR handling here; exact match required for safety
            } elseif ($candidate !== '' && strpos($remoteIp, $candidate) === 0) {
              $ipAllowed = true; break;
            }
          }
        }

        // Determine whether admins should be allowed to view the public site during maintenance (struct or legacy)
        $allowAdminPublicView = false;
        if (!empty($row) && isset($row['allow_admin_public_view_during_maintenance'])) {
          $allowAdminPublicView = (bool)$row['allow_admin_public_view_during_maintenance'];
        } elseif (!empty($j['security']['allow_admin_public_view_during_maintenance'])) {
          $allowAdminPublicView = (bool)$j['security']['allow_admin_public_view_during_maintenance'];
        }

        // If maintenance is on, block public pages unless requester is admin by session (and in admin area) or allowed via flag/IP/auth page
        if ($maintenance && !(($isAdminBySession && $isAdminArea) || $ipAllowed || $isAdminAuthPage || ($isAdminBySession && $allowAdminPublicView))) {
          // Render a simple maintenance notice and exit
          http_response_code(503);
          ?>
          <!doctype html>
          <html><head><meta charset="utf-8"><title>Maintenance</title><link rel="stylesheet" href="/assets/css/public.css"></head><body>
          <main style="display:flex;align-items:center;justify-content:center;height:80vh;text-align:center;padding:24px;">
            <div style="max-width:720px;padding:28px;border-radius:10px;background:linear-gradient(90deg,#ffd54f,#ffb300);box-shadow:0 8px 30px rgba(0,0,0,0.12);color:#111;">
              <div style="display:flex;gap:16px;align-items:center;justify-content:center;margin-bottom:12px;">
                <div style="width:56px;height:56px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(0,0,0,0.08);">
                  <img src="/assets/images/hq-logo.jpeg" alt="Logo" style="width:36px;height:36px;object-fit:contain;">
                </div>
                <h1 style="margin:0;font-size:1.6rem;">We'll be back soon</h1>
              </div>
              <p style="margin:0 0 12px;color:rgba(0,0,0,0.8);">Our site is currently undergoing scheduled maintenance. We're working to bring it back online as quickly as possible.</p>
              <p style="margin:0;color:rgba(0,0,0,0.7);font-size:0.95rem">If you are an administrator and need access, log in via the admin area or contact support.</p>
            </div>
          </main>
          </body></html>
          <?php
          exit;
        }
        // --- IP & MAC logging: insert a short access log for every public request ---
        try {
          $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
          $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
          $path = $_SERVER['REQUEST_URI'] ?? null;
          $referer = $_SERVER['HTTP_REFERER'] ?? null;
          $headers = [];
          foreach (['HTTP_X_DEVICE_MAC','HTTP_X_CLIENT_MAC','HTTP_MAC','HTTP_X_MAC_ADDRESS'] as $h) {
            if (!empty($_SERVER[$h])) { $headers[$h] = $_SERVER[$h]; }
          }
          $hdrJson = !empty($headers) ? json_encode($headers) : null;
          if (!empty($pdo)) {
            $ins = $pdo->prepare('INSERT INTO ip_logs (ip, user_agent, path, referer, headers, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $ins->execute([$remoteIp, $ua, $path, $referer, $hdrJson]);
          }
        } catch (Throwable $e) { error_log('ip_logs insert failed: ' . $e->getMessage()); }

        // --- MAC/IP enforcement: consult settings.security.enforcement_mode ('mac'|'ip'|'both') ---
        try {
          $enforcement = 'mac';
          $stmtS = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
          $stmtS->execute(['system_settings']);
          $val = $stmtS->fetchColumn();
          $j = $val ? json_decode($val, true) : [];
          $enforcement = $j['security']['enforcement_mode'] ?? $j['security']['enforce_by'] ?? $enforcement;

          $mac = null;
          foreach (['HTTP_X_DEVICE_MAC','HTTP_X_CLIENT_MAC','HTTP_MAC','HTTP_X_MAC_ADDRESS'] as $h) {
            if (!empty($_SERVER[$h])) { $mac = trim($_SERVER[$h]); break; }
          }
          if (!empty($mac) && in_array($enforcement, ['mac','both'])) {
            $q = $pdo->prepare('SELECT enabled FROM mac_blocklist WHERE mac = ? LIMIT 1');
            $q->execute([$mac]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            if ($r && !empty($r['enabled'])) {
              http_response_code(403);
              echo "<h1>Access denied</h1><p>Your device is blocked (MAC).</p>";
              exit;
            }
          }

          if (in_array($enforcement, ['ip','both'])) {
            $bq = $pdo->prepare('SELECT 1 FROM blocked_ips WHERE ip = ? LIMIT 1');
            $bq->execute([$remoteIp]);
            if ($bq->fetch()) {
              http_response_code(403);
              echo "<h1>Access denied</h1><p>Your IP address is blocked.</p>";
              exit;
            }
          }
        } catch (Throwable $e) { error_log('mac/ip enforcement failed: ' . $e->getMessage()); }
      } catch (Throwable $e) {
        // If anything goes wrong here, fall back to normal header rendering
      }
    }
  } catch (Throwable $e) {
    // ignore DB errors and fall back to default
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= isset($pageTitle) ? $pageTitle : 'HIGH Q SOLID ACADEMY'; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Prefer local Boxicons if available (place CSS+fonts in public/assets/vendor/boxicons/) -->
  <link rel="stylesheet" href="./assets/vendor/boxicons/boxicons.min.css" onerror="this.remove();" />
  <!-- Fallback to CDN if local not available -->
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <!-- Font Awesome (fallback for admin/backwards compatibility) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- BoxIcons CSS -->
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="./assets/css/public.css">
  <link rel="stylesheet" href="./assets/css/responsive.css">
  <link rel="stylesheet" href="./assets/css/ceo-responsive.css">
  <link rel="stylesheet" href="./assets/css/animations.css">
  <link rel="stylesheet" href="./assets/css/social-icons.css">
  <link rel="stylesheet" href="./assets/css/post-toc.css">
  <link rel="stylesheet" href="./assets/css/hero.css">
  <link rel="shortcut icon" href="./assets/images/favicon.ico" type="image/x-icon">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  <header>
    <!-- Top bar -->
    <div class="top-bar">
      <div class="container">
  <span><i class="fas fa-phone"></i> <?= htmlentities($siteSettings['contact']['phone'] ?? $contact_phone) ?></span>
  <span><i class="fas fa-envelope"></i> <?= htmlentities($siteSettings['contact']['email'] ?? 'info@hqacademy.com') ?></span>
        <span class="motto">"Always Ahead of Others"</span>
      </div>
    </div>

    <!-- Main nav -->
    <div class="main-header">
      <div class="container">
        <nav class="navbar navbar-expand-lg w-100 position-relative">
          <!-- Logo + Name -->
          <a class="navbar-brand" href="index.php">
            <div class="logo">
              <img src="/assets/images/hq-logo.jpeg" alt="HQ Logo" class="brand-logo">
              <div>
                <h1>HIGH Q SOLID ACADEMY</h1>
                <small>Limited</small>
              </div>
            </div>
          </a>

          <!-- Mobile Toggle Button - Positioned Absolutely -->
          <button class="navbar-toggler mobile-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <i class="bx bx-menu"></i>
          </button>

          <!-- Navigation -->
          <div class="collapse navbar-collapse" id="mainNav">
            <?php $cur = basename($_SERVER['PHP_SELF'] ?? '') ?>
            <ul class="navbar-nav mx-auto">
              <li class="nav-item">
                <a class="nav-link <?= $cur === 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= $cur === 'about.php' ? 'active' : '' ?>" href="about.php">About Us</a>
              </li>

              <!-- Programs Dropdown -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                  Programs
                </a>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="programs.php">Programs</a></li>
                  <li><a class="dropdown-item" href="exams.php">Exams</a></li>
                </ul>
              </li>

              <!-- News Dropdown -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                  News
                </a>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="news.php">News & Blog</a></li>
                  <li><a class="dropdown-item" href="community.php">Community</a></li>
                </ul>
              </li>

              <li class="nav-item">
                <a class="nav-link <?= $cur === 'register.php' ? 'active' : '' ?>" href="register.php">Admission</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= $cur === 'contact.php' ? 'active' : '' ?>" href="contact.php">Contact</a>
              </li>
            </ul>

            <!-- Register Button -->
            <div class="d-none d-lg-block">
              <a href="register.php" class="btn btn-primary">Register Now</a>
            </div>
          </div>
        </nav>
      </div>
    </div>
  </header>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Toggle nav dropdown open/close on click and close when clicking outside
    (function(){
      document.addEventListener('DOMContentLoaded', function(){
        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.navbar-toggler');
        const mobileMenu = document.querySelector('.navbar-collapse');
        
        if(mobileMenuBtn && mobileMenu) {
          mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('show');
          });
        }

        var dropToggles = document.querySelectorAll('.nav-dropdown .drop-toggle');
        dropToggles.forEach(function(toggle){
          toggle.addEventListener('click', function(e){
            e.preventDefault();
            var parent = toggle.closest('.nav-dropdown');
            // toggle open on this parent, close others
            document.querySelectorAll('.nav-dropdown.open').forEach(function(n){ if(n !== parent) n.classList.remove('open'); });
            parent.classList.toggle('open');
          });
        });

        // close dropdowns on outside click
        document.addEventListener('click', function(e){
          if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown.open').forEach(function(n){ n.classList.remove('open'); });
          }
        });

        // Add .is-loaded after first paint so CSS animations trigger after initial paint
        try {
          if ('requestAnimationFrame' in window) {
            requestAnimationFrame(function(){
              requestAnimationFrame(function(){
                document.documentElement.classList.add('is-loaded');
                // After we mark the page as loaded, assign stagger classes to common groups
                try {
                  var groups = [
                    {selector: '.programs-grid .program-card', base: 'stagger'},
                    {selector: '.tutors-grid .tutor-card', base: 'stagger'},
                    {selector: '.core-grid .value-card', base: 'stagger'},
                    {selector: '.ceo-stats .stat', base: 'stagger'}
                  ];
                  groups.forEach(function(g){
                    var nodes = Array.prototype.slice.call(document.querySelectorAll(g.selector));
                    nodes.forEach(function(n, i){
                      var idx = Math.min(4, Math.max(1, Math.ceil((i+1)/1)));
                      n.classList.add(g.base + '-' + idx);
                      // add a hover-zoom for nice mouse interaction on interactive cards
                      n.classList.add('hover-zoom');
                    });
                  });
                } catch (innerErr) { /* ignore */ }

                // Icon fallback: if Boxicons didn't render (font not available), replace bx <i> tags with a simple inline SVG square so UI stays usable.
                try {
                  // Test whether boxicons are rendering by checking computed font-family of a test element
                  var test = document.createElement('i');
                  test.className = 'bx bx-test-icon';
                  test.style.display = 'none';
                  document.body.appendChild(test);
                  var ff = window.getComputedStyle(test).fontFamily || '';
                  document.body.removeChild(test);
                  if (!/boxicons/i.test(ff)) {
                    // Replace visible .bx icons with inline fallback SVG (small square with inner glyph look)
                    document.querySelectorAll('i.bx, i.bxs, i.bxl').forEach(function(icon){
                      try {
                        var svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
                        svg.setAttribute('width','20'); svg.setAttribute('height','20'); svg.setAttribute('viewBox','0 0 24 24');
                        svg.innerHTML = '<rect x="3" y="3" width="18" height="18" rx="4" fill="#fff6d9" stroke="#f5b904" stroke-width="1.2"></rect>';
                        icon.parentNode.replaceChild(svg, icon);
                      } catch(e){}
                    });
                  }
                } catch(e){}
              });
            });
          } else {
            // fallback
            setTimeout(function(){ document.documentElement.classList.add('is-loaded'); }, 50);
          }
        } catch (e) {
          // if anything goes wrong, still try a small timeout
          setTimeout(function(){ try { document.documentElement.classList.add('is-loaded'); } catch (_){} }, 100);
        }
        // IntersectionObserver: reveal elements as they scroll into view with staggered delays
        try {
          var revealContainers = [
            {container: '.programs-grid', item: '.program-card'},
            {container: '.tutors-grid', item: '.tutor-card'},
            {container: '.core-grid', item: '.value-card'},
            {container: '.posts-grid', item: '.post-card'},
            {container: '.ceo-stats', item: '.stat'},
            {container: '.register-sidebar', item: '.card'},
            {container: '.sidebar-card', item: '.sidebar-card'}
          ];

          var observerOptions = { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.06 };
          var revealObserver = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
              var el = entry.target;
              if (entry.isIntersecting) {
                // mark visible
                el.classList.add('in-view');
                // small safety: ensure hover-zoom exists
                el.classList.add('hover-zoom');
                revealObserver.unobserve(el);
              }
            });
          }, observerOptions);

          revealContainers.forEach(function(group){
            var containers = document.querySelectorAll(group.container);
            containers.forEach(function(parent){
              var items = parent.querySelectorAll(group.item);
              items.forEach(function(item, idx){
                // compute a small stagger delay based on index
                var delay = Math.min(0.28, Math.max(0, idx * 0.06));
                item.style.transitionDelay = delay + 's';
                // if using CSS variables instead, set here as fallback
                revealObserver.observe(item);
              });
            });
          });
        } catch (ioErr) { /* ignore IntersectionObserver errors on old browsers */ }
      });
    })();
  </script>

  <main class="public-main">
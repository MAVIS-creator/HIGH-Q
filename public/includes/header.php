<?php
// public/includes/header.php - lightweight public header

// Load helper functions (includes app_url()) before anything else
if (file_exists(__DIR__ . '/../config/functions.php')) {
  require_once __DIR__ . '/../config/functions.php';
}

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
          <html><head><meta charset="utf-8"><title>Maintenance</title><link rel="stylesheet" href="<?= app_url('assets/css/public.css') ?>"></head><body>
          <main style="display:flex;align-items:center;justify-content:center;height:80vh;text-align:center;padding:24px;">
            <div style="max-width:720px;padding:28px;border-radius:10px;background:linear-gradient(90deg,#ffd54f,#ffb300);box-shadow:0 8px 30px rgba(0,0,0,0.12);color:#111;">
              <div style="display:flex;gap:16px;align-items:center;justify-content:center;margin-bottom:12px;">
                <div style="width:56px;height:56px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(0,0,0,0.08);">
                  <img src="<?= app_url('assets/images/hq-logo.jpeg') ?>" alt="Logo" style="width:36px;height:36px;object-fit:contain;">
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
  
  <!-- Comprehensive SEO Meta Tags -->
  <?php if (isset($pageDescription)): ?>
  <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
  <?php elseif (defined('PAGE_DESCRIPTION')): ?>
  <meta name="description" content="<?= htmlspecialchars(PAGE_DESCRIPTION, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  
  <?php if (isset($pageKeywords)): ?>
  <meta name="keywords" content="<?= htmlspecialchars($pageKeywords, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  
  <!-- Open Graph Tags -->
  <meta property="og:title" content="<?= htmlspecialchars(isset($pageTitle) ? $pageTitle : 'HIGH Q SOLID ACADEMY', ENT_QUOTES, 'UTF-8') ?>">
  <?php if (isset($pageDescription)): ?>
  <meta property="og:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') ?>://<?= htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
  <meta property="og:image" content="<?= app_url('assets/images/hq-logo.jpeg') ?>">
  
  <!-- Canonical URL -->
  <link rel="canonical" href="<?= htmlspecialchars(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') ?>://<?= htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
  
  <?php if (function_exists('auto_robots_tag')): ?>
  <?php echo auto_robots_tag(); ?>
  <?php endif; ?>
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="<?= app_url('assets/images/favicon.ico') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('assets/images/apple-touch-icon.png') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('assets/images/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('assets/images/favicon-16x16.png') ?>">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- HQ Theme overrides (must come after Bootstrap) -->
  <link rel="stylesheet" href="<?= app_url('assets/css/theme.css') ?>">
  <!-- Prefer local Boxicons if available (place CSS+fonts in public/assets/vendor/boxicons/) -->
  <link rel="stylesheet" href="<?= app_url('assets/vendor/boxicons/boxicons.min.css') ?>" onerror="this.remove();" />
  <!-- Fallback to CDN if local not available -->
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <!-- Font Awesome (fallback for admin/backwards compatibility) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- BoxIcons CSS -->
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= app_url('assets/css/public.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/responsive.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/ceo-responsive.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/animations.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/social-icons.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/post-toc.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/hero.css') ?>">
  <!-- Small site-specific hero overrides (loaded after hero.css) -->
  <link rel="stylesheet" href="<?= app_url('assets/css/hero-fixed.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/offcanvas.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/contact-fixes.css') ?>">
  <!-- HQ UI/UX System (device-responsive design) -->
  <link rel="stylesheet" href="<?= app_url('assets/css/hq-ui-system.css') ?>">
  <link rel="shortcut icon" href="<?= app_url('assets/images/favicon.ico') ?>" type="image/x-icon">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- HQ Animations helper -->
  <script defer src="<?= app_url('assets/js/hq-animations.js') ?>"></script>
  <!-- HQ UI/UX System Scripts -->
  <script defer src="<?= app_url('assets/js/hq-particles.js') ?>"></script>
  <script defer src="<?= app_url('assets/js/hq-magnetic.js') ?>"></script>
  <script defer src="<?= app_url('assets/js/hq-ripple.js') ?>"></script>
  </head>

  <body class="hq-public">
  <?php
    // Expose a client-side base URL for public scripts.
    // Prefer app_url() from helpers/.env when available; otherwise fall back to scheme://host + script dir.
    try {
      if (function_exists('app_url')) {
        $hq_app_base = rtrim(app_url(''), '/');
      } else {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $hq_app_base = rtrim($proto . '://' . $host, '/') . $scriptDir;
      }
    } catch (Throwable $_) { $hq_app_base = ''; }
  ?>

  <script>
    // HQ base available for public JS. window.HQ_APP_BASE preferred; keep HQ_BASE for legacy pages.
    window.HQ_APP_BASE = <?= json_encode($hq_app_base) ?>;
    if (!window.HQ_BASE) window.HQ_BASE = window.HQ_APP_BASE;
  </script>
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
          <a class="navbar-brand" href="<?= app_url('index.php') ?>">
            <div class="logo">
              <img src="<?= app_url('assets/images/hq-logo.jpeg') ?>" alt="HQ Logo" class="brand-logo">
              <div>
                <h1>HIGH Q SOLID ACADEMY</h1>
                <small>Limited</small>
              </div>
            </div>
          </a>

          <!-- Toggle for mobile - opens offcanvas -->
          <button class="navbar-toggler border-0 ms-auto mobile-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
            <i class="bx bx-menu"></i>
          </button>

          <!-- Desktop Navigation -->
          <div class="collapse navbar-collapse" id="mainNav">
            <?php $cur = basename($_SERVER['PHP_SELF'] ?? '') ?>
            <ul class="navbar-nav mx-auto d-none d-lg-flex">
              <li class="nav-item">
                <a class="nav-link <?= $cur === 'index.php' ? 'active' : '' ?>" href="<?= app_url('index.php') ?>">Home</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= $cur === 'about.php' ? 'active' : '' ?>" href="<?= app_url('about.php') ?>">About Us</a>
              </li>

              <!-- Programs Dropdown -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                  Programs
                </a>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="<?= app_url('programs.php') ?>">Programs</a></li>
                  <li><a class="dropdown-item" href="<?= app_url('exams.php') ?>">Exams</a></li>
                </ul>
              </li>

              <!-- News Dropdown -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                  News
                </a>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="<?= app_url('news.php') ?>">News & Blog</a></li>
                  <li><a class="dropdown-item" href="<?= app_url('community.php') ?>">Community</a></li>
                </ul>
              </li>

              <li class="nav-item">
                <a class="nav-link <?= $cur === 'register-new.php' ? 'active' : '' ?>" href="<?= app_url('register-new.php') ?>">Admission</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= $cur === 'contact.php' ? 'active' : '' ?>" href="<?= app_url('contact.php') ?>">Contact</a>
              </li>
            </ul>

            <!-- Register Button -->
            <div class="d-none d-lg-block">
              <a href="<?= app_url('find-your-path-quiz.php') ?>" class="btn btn-primary"><i class='bx bx-compass me-1'></i>Find Your Path</a>
            </div>
          </div>

          <!-- Toggle for mobile - opens offcanvas -->
          <button class="navbar-toggler border-0 ms-auto mobile-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
            <i class="bx bx-menu"></i>
          </button>
        </nav>
      </div>
    </div>
  </header>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Add .is-loaded after first paint for CSS animations
    document.addEventListener('DOMContentLoaded', function(){
      requestAnimationFrame(function(){
        requestAnimationFrame(function(){
          document.documentElement.classList.add('is-loaded');
          // Add stagger classes to common groups
          try {
            var groups = [
              {selector: '.programs-grid .program-card', base: 'stagger'},
              {selector: '.tutors-grid .tutor-card', base: 'stagger'},
              {selector: '.core-grid .value-card', base: 'stagger'},
              {selector: '.ceo-stats .stat', base: 'stagger'}
            ];
            groups.forEach(function(g){
              document.querySelectorAll(g.selector).forEach(function(n, i){
                var idx = Math.min(4, Math.max(1, Math.ceil((i+1)/1)));
                n.classList.add(g.base + '-' + idx, 'hover-zoom');
              });
            });
          } catch (err) { /* ignore */ }
        });
      });
    });
  </script>

  <!-- Offcanvas Side Nav for Mobile -->
    <div class="offcanvas offcanvas-start custom-offcanvas d-lg-none" tabindex="-1" id="mobileNav">
        <div class="offcanvas-header py-3">
            <h5 class="offcanvas-title fw-bold mb-0">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-3">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link px-3 py-2 <?= $cur === 'index.php' ? 'active' : '' ?>" href="<?= app_url('index.php') ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2 <?= $cur === 'about.php' ? 'active' : '' ?>" href="<?= app_url('about.php') ?>">About Us</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="<?= app_url('programs.php') ?>">Programs</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="<?= app_url('exams.php') ?>">Exams</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="<?= app_url('news.php') ?>">News & Blog</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="<?= app_url('community.php') ?>">Community</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2 <?= $cur === 'register-new.php' ? 'active' : '' ?>" href="<?= app_url('register-new.php') ?>">Admission</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2 <?= $cur === 'contact.php' ? 'active' : '' ?>" href="<?= app_url('contact.php') ?>">Contact</a></li>
            </ul>
            <div class="mt-4 px-3">
              <a href="<?= app_url('register-new.php') ?>" class="btn btn-primary w-100 py-2">Find Your Path</a>
            </div>
        </div>
    </div>

  <main class="public-main">
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

        // If maintenance is on, block public pages unless requester is admin by session, from allowed IP, or visiting an auth page
        if ($maintenance && !($isAdminBySession || $ipAllowed || $isAdminAuthPage)) {
          // Render a simple maintenance notice and exit
          http_response_code(503);
          ?>
          <!doctype html>
          <html><head><meta charset="utf-8"><title>Maintenance</title><link rel="stylesheet" href="/public/assets/css/public.css"></head><body>
          <main style="display:flex;align-items:center;justify-content:center;height:80vh;text-align:center;padding:24px;">
            <div style="max-width:640px;border:1px solid #eee;padding:28px;border-radius:8px;background:#fff;">
              <h1>We'll be back soon</h1>
              <p>Our site is currently undergoing scheduled maintenance. Please check back shortly.</p>
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
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="./assets/css/public.css">
  <link rel="shortcut icon" href="./assets/images/favicon.ico" type="image/x-icon">
</head>

<body>
  <header>
    <!-- Top bar -->
    <div class="top-bar">
      <div class="container">
        <span>📞 <?= htmlentities($contact_phone) ?></span>
        <span>✉️ info@hqacademy.com</span>
        <span class="motto">"Always Ahead of Others"</span>
      </div>
    </div>

    <!-- Main nav -->
    <div class="main-header">
      <div class="container">
        <!-- Logo + Name -->
        <div class="logo">
          <img src="./assets/images/hq-logo.jpeg" alt="HQ Logo" class="brand-logo" href="index.php">
          <div>
            <h1>HIGH Q SOLID ACADEMY</h1>
            <small>Limited</small>
          </div>
        </div>


        <!-- Navigation -->
        <nav>
          <a href="index.php" class="active">Home</a>
          <a href="about.php">About Us</a>

          <!-- Combined dropdown: a single, general label that reveals Programs and News on click -->
          <div class="nav-dropdown">
            <a href="#" class="drop-toggle">Programs & News</a>
            <div class="nav-dropdown-content">
              <a href="programs.php">Programs</a>
              <a href="news.php">News</a>
            </div>
          </div>

          <a href="register.php">Admission</a>
          <a href="contact.php">Contact</a>
        </nav>

        <!-- Button -->
        <a href="register.php" class="btn">Register Now</a>
      </div>
    </div>
  </header>

  <script>
    // Toggle nav dropdown open/close on click and close when clicking outside
    (function(){
      document.addEventListener('DOMContentLoaded', function(){
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
      });
    })();
  </script>

  <main class="public-main">
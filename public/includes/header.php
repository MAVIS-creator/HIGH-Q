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
    if (!function_exists('hqIsLocalHost')) {
      function hqIsLocalHost(?string $host = null): bool {
        $host = $host ?? ($_SERVER['HTTP_HOST'] ?? '');
        $host = strtolower((string)preg_replace('/:\d+$/', '', (string)$host));
        return $host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || str_ends_with($host, '.localhost');
      }
    }
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
        $sslEnforce = false;
        $ipLoggingEnabled = true;
        // Prefer structured site_settings row if available
        if (!empty($row) && isset($row['maintenance'])) $maintenance = (bool)$row['maintenance'];
        else {
          $stmt2 = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
          $stmt2->execute(['system_settings']);
          $val = $stmt2->fetchColumn();
          $j = $val ? json_decode($val, true) : [];
          if (!empty($j['security']['maintenance'])) $maintenance = (bool)$j['security']['maintenance'];
        }
        if (!empty($j['advanced']['ssl_enforce'])) $sslEnforce = (bool)$j['advanced']['ssl_enforce'];
        if (isset($j['advanced']['ip_logging'])) $ipLoggingEnabled = (bool)$j['advanced']['ip_logging'];

        if ($sslEnforce) {
          $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
          $host = $_SERVER['HTTP_HOST'] ?? '';
          if (!$isSecure && $host !== '' && !hqIsLocalHost($host)) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: https://' . $host . $requestUri, true, 302);
            exit;
          }
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
          $supportPhone = trim((string)($siteSettings['contact']['phone'] ?? $contact_phone));
          $supportEmail = trim((string)($siteSettings['contact']['email'] ?? 'info@hqacademy.com'));
          $supportAddress = trim((string)($siteSettings['contact']['address'] ?? ''));
          $siteName = trim((string)($siteSettings['site']['name'] ?? 'HIGH Q SOLID ACADEMY'));
          $logoUrl = app_url('assets/images/hq-logo.jpeg');

          http_response_code(503);
          ?>
          <!doctype html>
          <html lang="en">
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Maintenance | <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></title>
            <link rel="icon" type="image/x-icon" href="<?= app_url('assets/images/favicon.ico') ?>">
            <style>
              :root {
                --hq-gold: #f7c325;
                --hq-gold-deep: #e3a300;
                --hq-ink: #0f172a;
                --hq-slate: #475569;
                --hq-border: rgba(15, 23, 42, 0.08);
                --hq-surface: rgba(255,255,255,0.9);
              }
              * { box-sizing: border-box; }
              html, body { margin: 0; min-height: 100%; font-family: Inter, "Segoe UI", Arial, sans-serif; color: var(--hq-ink); }
              body {
                background:
                  radial-gradient(circle at top left, rgba(247, 195, 37, 0.24), transparent 30%),
                  radial-gradient(circle at bottom right, rgba(255, 213, 79, 0.26), transparent 24%),
                  linear-gradient(180deg, #fffaf0 0%, #f8fafc 100%);
              }
              .maintenance-shell {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 32px 20px;
              }
              .maintenance-panel {
                width: min(1100px, 100%);
                display: grid;
                grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
                background: rgba(255,255,255,0.82);
                border: 1px solid var(--hq-border);
                border-radius: 28px;
                box-shadow: 0 24px 80px rgba(15, 23, 42, 0.12);
                overflow: hidden;
                backdrop-filter: blur(18px);
              }
              .maintenance-main {
                padding: 52px;
                background:
                  linear-gradient(135deg, rgba(255,255,255,0.95), rgba(255,248,220,0.88)),
                  linear-gradient(120deg, #fff 0%, #fff6d9 100%);
              }
              .maintenance-side {
                padding: 40px 34px;
                background: linear-gradient(180deg, #111827 0%, #1f2937 100%);
                color: #f8fafc;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 24px;
              }
              .brand-row {
                display: flex;
                align-items: center;
                gap: 18px;
                margin-bottom: 28px;
              }
              .brand-logo {
                width: 74px;
                height: 74px;
                border-radius: 22px;
                background: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 18px 40px rgba(227, 163, 0, 0.18);
                flex-shrink: 0;
              }
              .brand-logo img {
                width: 48px;
                height: 48px;
                object-fit: contain;
              }
              .brand-text small {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 7px 12px;
                border-radius: 999px;
                background: rgba(247, 195, 37, 0.18);
                color: #8a5a00;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                font-size: 12px;
                margin-bottom: 12px;
              }
              .brand-text h1 {
                margin: 0;
                font-size: clamp(2.1rem, 4vw, 3.5rem);
                line-height: 1.02;
                letter-spacing: 0;
              }
              .brand-text p {
                margin: 14px 0 0;
                max-width: 640px;
                color: var(--hq-slate);
                font-size: 1.04rem;
                line-height: 1.7;
              }
              .status-strip {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                margin: 0 0 20px;
                padding: 10px 14px;
                border-radius: 999px;
                background: rgba(247, 195, 37, 0.16);
                color: #8a5a00;
                font-weight: 700;
                font-size: 0.95rem;
              }
              .status-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: #f59e0b;
                box-shadow: 0 0 0 6px rgba(245, 158, 11, 0.18);
              }
              .detail-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
                margin-top: 34px;
              }
              .detail-card {
                padding: 18px 18px 16px;
                border-radius: 18px;
                background: rgba(255,255,255,0.8);
                border: 1px solid rgba(15,23,42,0.08);
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
              }
              .detail-card strong,
              .side-card strong {
                display: block;
                margin-bottom: 8px;
                font-size: 0.95rem;
              }
              .detail-card p,
              .side-card p {
                margin: 0;
                color: var(--hq-slate);
                line-height: 1.65;
                font-size: 0.95rem;
              }
              .detail-card p a,
              .side-card p a { color: inherit; text-decoration: none; }
              .action-row {
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
                margin-top: 30px;
              }
              .action-btn {
                appearance: none;
                border: none;
                border-radius: 14px;
                padding: 14px 18px;
                font-size: 0.96rem;
                font-weight: 700;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
              }
              .action-btn:hover { transform: translateY(-1px); }
              .action-btn-primary {
                background: linear-gradient(135deg, var(--hq-gold), var(--hq-gold-deep));
                color: #111827;
                box-shadow: 0 14px 28px rgba(227, 163, 0, 0.24);
              }
              .action-btn-secondary {
                background: rgba(15, 23, 42, 0.06);
                color: var(--hq-ink);
                border: 1px solid rgba(15, 23, 42, 0.08);
              }
              .side-top h2 {
                margin: 0 0 10px;
                font-size: 1.4rem;
              }
              .side-top p {
                margin: 0;
                color: rgba(248, 250, 252, 0.8);
                line-height: 1.7;
              }
              .side-stack {
                display: grid;
                gap: 14px;
              }
              .side-card {
                padding: 16px 18px;
                border-radius: 18px;
                background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255,255,255,0.08);
              }
              .side-card p {
                color: rgba(248, 250, 252, 0.84);
              }
              .side-footer {
                padding-top: 8px;
                border-top: 1px solid rgba(255,255,255,0.1);
                color: rgba(248, 250, 252, 0.7);
                font-size: 0.92rem;
                line-height: 1.6;
              }
              @media (max-width: 920px) {
                .maintenance-panel {
                  grid-template-columns: 1fr;
                }
                .maintenance-main,
                .maintenance-side {
                  padding: 28px 22px;
                }
                .detail-grid {
                  grid-template-columns: 1fr;
                }
              }
              @media (max-width: 560px) {
                .maintenance-shell {
                  padding: 16px;
                }
                .maintenance-panel {
                  border-radius: 22px;
                }
                .brand-row {
                  align-items: flex-start;
                }
                .brand-logo {
                  width: 62px;
                  height: 62px;
                  border-radius: 18px;
                }
                .brand-logo img {
                  width: 40px;
                  height: 40px;
                }
                .action-row {
                  flex-direction: column;
                }
                .action-btn {
                  width: 100%;
                }
              }
            </style>
          </head>
          <body>
            <main class="maintenance-shell">
              <section class="maintenance-panel" aria-labelledby="maintenance-title">
                <div class="maintenance-main">
                  <div class="status-strip">
                    <span class="status-dot" aria-hidden="true"></span>
                    Scheduled maintenance in progress
                  </div>

                  <div class="brand-row">
                    <div class="brand-logo">
                      <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?> logo">
                    </div>
                    <div class="brand-text">
                      <small>HighQ Update Window</small>
                      <h1 id="maintenance-title">We’ll be back soon</h1>
                      <p>
                        <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?> is currently being updated to keep everything running smoothly.
                        We’re working to restore full access as quickly as possible.
                      </p>
                    </div>
                  </div>

                  <div class="detail-grid">
                    <article class="detail-card">
                      <strong>What’s happening</strong>
                      <p>We’re carrying out maintenance and service checks across the public site. Some pages and actions are temporarily unavailable while we finish that work.</p>
                    </article>
                    <article class="detail-card">
                      <strong>What to do next</strong>
                      <p>Please check back shortly. If you already have admin access, you can continue through the admin area while this maintenance window is active.</p>
                    </article>
                  </div>

                  <div class="action-row">
                    <button class="action-btn action-btn-primary" type="button" onclick="window.location.reload()">
                      Refresh Page
                    </button>
                  </div>
                </div>

                <aside class="maintenance-side">
                  <div class="side-top">
                    <h2>Need help while we’re away?</h2>
                    <p>If your visit is urgent, use the contact details below. If you are the administrator, please try to resolve the issue from your side.</p>
                  </div>

                  <div class="side-stack">
                    <div class="side-card">
                      <strong>Phone</strong>
                      <p><?= htmlspecialchars($supportPhone !== '' ? $supportPhone : 'Not available right now', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="side-card">
                      <strong>Email</strong>
                      <p>
                        <?php if ($supportEmail !== ''): ?>
                          <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                          Not available right now
                        <?php endif; ?>
                      </p>
                    </div>
                    <?php if ($supportAddress !== ''): ?>
                    <div class="side-card">
                      <strong>Centre Address</strong>
                      <p><?= nl2br(htmlspecialchars($supportAddress, ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>
                    <?php endif; ?>
                  </div>

                  <div class="side-footer">
                    Thank you for your patience. We’re using this window to keep the experience fast, reliable, and ready when you return.
                  </div>
                </aside>
              </section>
            </main>
          </body>
          </html>
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
          if ($ipLoggingEnabled && !empty($pdo)) {
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
  <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
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
  <link rel="stylesheet" href="<?= app_url('assets/css/mobile-animations.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/social-icons.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/post-toc.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/post-page.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/hero.css') ?>">
  <!-- Small site-specific hero overrides (loaded after hero.css) -->
  <link rel="stylesheet" href="<?= app_url('assets/css/hero-fixed.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/offcanvas.css') ?>">
  <link rel="stylesheet" href="<?= app_url('assets/css/contact-fixes.css') ?>">
  <!-- HQ UI/UX System (device-responsive design) -->
  <link rel="stylesheet" href="<?= app_url('assets/css/hq-ui-system.css') ?>">
  <!-- Home page particles animation -->
  <?php if (strpos($_SERVER['REQUEST_URI'] ?? '', 'index.php') !== false || rtrim($_SERVER['REQUEST_URI'] ?? '', '/') === '' || $_SERVER['SCRIPT_NAME'] === '/index.php' || basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'index.php'): ?>
  <link rel="stylesheet" href="<?= app_url('assets/css/home-particles.css') ?>">
  <?php endif; ?>
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
        <!-- NYSC Badge in Top Bar -->
        <span class="nysc-topbar-badge">
          <img src="<?= app_url('assets/images/nysc-logo.png') ?>" alt="NYSC" class="nysc-topbar-logo">
          <span class="nysc-topbar-text">NYSC Accredited</span>
        </span>
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
              <a href="<?= app_url('find-your-path-quiz.php') ?>" class="btn btn-primary w-100 py-2"><i class='bx bx-compass me-1'></i>Find Your Path</a>
            </div>
        </div>
    </div>

  <main class="public-main">

<?php
// admin/includes/header.php
// If this is an AJAX or API call expecting JSON, do not emit the full HTML header
$isAjaxRequest = false;
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $isAjaxRequest = true;
}
// Also consider requests that explicitly accept JSON
if (!$isAjaxRequest && !empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    $isAjaxRequest = true;
}
// If an 'action' parameter is present it's commonly used for both normal form POSTs and AJAX calls.
// Only treat the request as AJAX when it is explicitly requested by the client: either via
// the X-Requested-With header, an explicit ajax=1 parameter, or the Accept header indicates JSON.
if (!$isAjaxRequest && !empty($_REQUEST['action'])) {
    // Consider it AJAX only when the client explicitly marked it as such
    $explicitAjax = false;
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) $explicitAjax = true;
    if (!empty($_REQUEST['ajax']) && $_REQUEST['ajax'] == '1') $explicitAjax = true;
    if (!empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) $explicitAjax = true;
    if ($explicitAjax) $isAjaxRequest = true;
}
if ($isAjaxRequest) {
    // For AJAX requests we still want to perform lightweight setup (session/auth) but avoid HTML output.
    // Return early so that pages which include this header for UI don't accidentally send HTML when they
    // intended to return JSON. Caller scripts can still perform logging or authentication earlier.
    return;
}

// Ensure admin helper functions are available (defines admin_url/app_url). Some callers
// include this header early and may not have included admin/includes/functions.php.
if (!function_exists('admin_url') || !function_exists('app_url')) {
    $funcPath = __DIR__ . '/functions.php';
    if (file_exists($funcPath)) {
        require_once $funcPath;
    }
}

// Start output buffering so downstream header() calls succeed even if this file emits HTML
if (!headers_sent()) {
    ob_start();
}

// Compute admin base path once so asset URLs work from both:
//  - /.../admin/index.php?pages=...
//  - /.../admin/pages/index.php?pages=...
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$parts = explode('/', trim($script, '/'));
$idx = array_search('admin', $parts, true);
if ($idx !== false) {
    $adminBasePath = '/' . implode('/', array_slice($parts, 0, $idx + 1));
} else {
    $adminBasePath = rtrim(dirname($script), '/');
    if ($adminBasePath === '') $adminBasePath = '/admin';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title><?= isset($pageTitle) ? $pageTitle : 'Admin Panel'; ?> - HIGH Q SOLID ACADEMY</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?= htmlspecialchars($adminBasePath) ?>/assets/img/favicon.ico" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Modern Admin Theme (must come before legacy admin.css to set variables) -->
    <link rel="stylesheet" href="<?= htmlspecialchars($adminBasePath) ?>/assets/css/admin-modern.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($adminBasePath) ?>/assets/css/account-settings-modal.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($adminBasePath) ?>/assets/css/admin-style.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($adminBasePath) ?>/assets/css/admin-minimal.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($adminBasePath) ?>/assets/css/notifications.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($adminBasePath) ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($adminBasePath) ?>/assets/css/modern-tables.css">
    <!-- SweetAlert2 (used by many admin pages) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= htmlspecialchars($adminBasePath) ?>/assets/js/storage-helper.js"></script>
    <script src="<?= htmlspecialchars($adminBasePath) ?>/assets/js/device-capability.js"></script>
    <script src="<?= htmlspecialchars($adminBasePath) ?>/assets/js/sweetalert-config.js"></script>
    <script src="<?= htmlspecialchars($adminBasePath) ?>/assets/js/notifications.js" defer></script>
    <script src="<?= htmlspecialchars($adminBasePath) ?>/assets/js/header-notifications.js" defer></script>
    <script src="<?= htmlspecialchars($adminBasePath) ?>/assets/js/viewport-check.js" defer></script>
    <script src="<?= htmlspecialchars($adminBasePath) ?>/assets/js/admin-forms.js" defer></script>
    <script src="<?= htmlspecialchars($adminBasePath) ?>/assets/js/account-settings-modal.js" defer></script>
    <?php
    // Output the correct admin.css for the detected admin path
    echo "<link rel=\"stylesheet\" href=\"" . htmlspecialchars($adminBasePath) . "/assets/css/admin.css\">\n";
    // If a page-specific CSS was provided, allow two modes:
    //  - raw <link> tag or <style> tag (echo as-is)
    //  - relative path (treat as relative to admin base)
    if (!empty($pageCss)) {
        $trim = trim($pageCss);
        if (strpos($trim, '<link') === 0 || strpos($trim, '<style') === 0) {
            // Fix older pageCss values that used ../assets/... which breaks when served from /admin/index.php
            $fixed = str_replace('href="../assets/', 'href="' . htmlspecialchars($adminBasePath) . '/assets/', $pageCss);
            $fixed = str_replace("href='../assets/", "href='" . htmlspecialchars($adminBasePath) . "/assets/", $fixed);
            echo $fixed . "\n";
        } else {
            echo "<link rel=\"stylesheet\" href=\"" . htmlspecialchars($adminBasePath) . "/" . ltrim($pageCss, '/') . "\">\n";
        }
    }

    // Minimal critical inline fallback CSS (keeps UI readable if external CSS fails)
    // NOTE: keep these conservative and avoid overriding admin layout variables (no body padding or zero margin-left on .admin-main)
    echo "<style>\n" .
        "body{background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111;margin:0;padding:0;}\n" .
        ".admin-main{margin-left:260px;padding-top:84px;padding-bottom:68px;}\n" .
        ".page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;}\n" .
        ".card{background:#fff;padding:18px;border-radius:8px;border:1px solid #eee;box-shadow:0 4px 10px rgba(0,0,0,0.04);margin-bottom:12px;}\n" .
        ".header-cta{background:#ffd600;color:#111;padding:8px 12px;border-radius:8px;text-decoration:none;border:none;cursor:pointer;}\n" .
        ".tabs{display:flex;gap:6px;margin-bottom:8px;}\n" .
        ".tab-btn{padding:6px 8px;border:1px solid #ccc;background:#fff;border-radius:4px;cursor:pointer;}\n" .
        "label{display:block;margin:6px 0;font-weight:600;}input.input, textarea.input{width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;}\n" .
        "</style>\n";

    // Expose admin base dynamically from current request URL (just like public site)
    // Extract scheme and host from the actual request
    // Check for X-Forwarded-Proto header (set by reverse proxies/load balancers like CloudFlare, nginx, etc.)
    $scheme = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Extract the admin base path from the current request URI
    // E.g., /HIGH-Q/admin/pages/index.php?pages=dashboard -> /HIGH-Q/admin
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Find where '/admin' appears in the URI
    $adminPos = strpos($requestUri, '/admin');
    if ($adminPos !== false) {
        // Extract everything up to and including '/admin'
        $endPos = $adminPos + strlen('/admin');
        $adminPath = substr($requestUri, 0, $endPos);
    } else {
        // Fallback: extract from script name
        $adminPos = strpos($scriptName, '/admin');
        if ($adminPos !== false) {
            $adminPath = substr($scriptName, 0, $adminPos + strlen('/admin'));
        } else {
            // Last resort: use filesystem project name
            $rootName = basename(dirname(__DIR__, 2));
            $adminPath = '/' . $rootName . '/admin';
        }
    }
    
    // Build the full admin base URL
    $adminBaseFull = $scheme . '://' . $host . $adminPath;
    
    echo "<script>\n";
    echo "window.HQ_ADMIN_BASE = '" . $adminBaseFull . "';\n";
    echo "window.HQ_ADMIN_PATH = '" . $adminPath . "';\n";
    echo "window.HQ_APP_BASE = '" . $scheme . '://' . $host . "';\n";
    echo "console.log('ADMIN_BASE derived from request:', '" . $adminBaseFull . "');\n";
    echo "</script>\n";
    ?>
</head>

<body>
    <header class="admin-header">
        <div class="header-left">
            <i class='bx bx-menu' id="menuToggle"></i>
            <div class="header-title-group">
                <span class="header-title"><?= isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></span>
                <?php if (!empty($pageSubtitle)): ?>
                    <p class="header-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-right">
            <?php if (empty($_SESSION['user'])): ?>
                <a href="<?= htmlspecialchars($adminBasePath) ?>/signup.php" class="header-cta"><i class='bx bx-user-plus'></i> Sign up</a>
            <?php else: ?>
                    <div class="header-notifications">
                        <button id="notifBtn" class="header-cta" title="Notifications">
                            <i class='bx bx-bell'></i>
                            <span id="notifBadge" style="display:none;background:var(--hq-red);color:#fff;border-radius:999px;padding:2px 6px;margin-left:4px;font-size:0.75rem;font-weight:700;">0</span>
                        </button>
                    </div>
                    <div class="header-avatar-wrapper">
                        <?php
                        // Build a resilient avatar fallback using the computed app base so hardcoded /HIGH-Q paths are avoided
                        $avatar = $_SESSION['user']['avatar'] ?? null;
                        if (empty($avatar)) {
                            // Use the admin logo asset that exists in this project
                            $avatar = $adminBasePath . '/assets/img/hq-logo.jpeg';
                        }
                        $userName = $_SESSION['user']['name'] ?? 'User';
                        $userRole = $_SESSION['user']['role'] ?? 'Admin';
                        ?>
                        <div class="header-avatar" id="avatarDropdownBtn">
                            <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                            <i class='bx bx-chevron-down'></i>
                        </div>
                        <div class="profile-dropdown" id="profileDropdown">
                            <div class="profile-dropdown-header">
                                <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                                <div class="profile-info">
                                    <div class="profile-name"><?= htmlspecialchars($userName) ?></div>
                                    <div class="profile-role"><?= htmlspecialchars($userRole) ?></div>
                                </div>
                            </div>
                            <div class="profile-dropdown-divider"></div>
                            <a href="javascript:void(0)" onclick="openAccountSettings()" class="profile-dropdown-item">
                                <i class='bx bx-cog'></i>
                                <span>Profile &amp; Account Settings</span>
                            </a>
                            <div class="profile-dropdown-divider"></div>
                            <a href="<?= htmlspecialchars($adminBasePath) ?>/logout.php" class="profile-dropdown-item profile-dropdown-item--logout">
                                <i class='bx bx-log-out'></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
            <?php endif; ?>
        </div>
    </header>
    <?php
    // Render flash messages (if any)
    if (function_exists('getFlash')) {
        $flash = getFlash();
        if (!empty($flash)) {
            $type = $flash['type'] ?? 'info';
            $msg  = $flash['message'] ?? '';
            echo "<div class=\"admin-flash admin-flash-{$type}\">" . htmlspecialchars($msg) . "</div>";
        }
    }
    // --- Admin area IP/MAC logging and blocklist enforcement ---
    try {
        if (file_exists(__DIR__ . '/db.php')) {
            // ensure DB is available (admin pages often include db earlier)
            if (!isset($pdo)) require_once __DIR__ . '/db.php';
        }
        if (isset($pdo)) {
            $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $path = $_SERVER['REQUEST_URI'] ?? null;
            $referer = $_SERVER['HTTP_REFERER'] ?? null;
            $userId = $_SESSION['user']['id'] ?? null;
            $headers = [];
            foreach (['HTTP_X_DEVICE_MAC','HTTP_X_CLIENT_MAC','HTTP_MAC','HTTP_X_MAC_ADDRESS'] as $h) {
                if (!empty($_SERVER[$h])) $headers[$h] = $_SERVER[$h];
            }
            $hdrJson = !empty($headers) ? json_encode($headers) : null;
            $ins = $pdo->prepare('INSERT INTO ip_logs (ip, user_agent, path, referer, user_id, headers, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $ins->execute([$remoteIp, $ua, $path, $referer, $userId, $hdrJson]);

            // Decide enforcement mode: 'mac' | 'ip' | 'both' (default 'mac')
            $enforcement = 'mac';
            try {
                $stmtS = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
                $stmtS->execute(['system_settings']);
                $val = $stmtS->fetchColumn();
                $j = $val ? json_decode($val, true) : [];
                $enforcement = $j['security']['enforcement_mode'] ?? $j['security']['enforce_by'] ?? $enforcement;
            } catch (Throwable $e) { /* ignore */ }

            // Check MAC header if allowed
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

            // If enforcement allows IP checks or uses fallback, check blocked_ips table
            if (in_array($enforcement, ['ip','both'])) {
                try {
                    $bq = $pdo->prepare('SELECT 1 FROM blocked_ips WHERE ip = ? LIMIT 1');
                    $bq->execute([$remoteIp]);
                    if ($bq->fetch()) {
                        http_response_code(403);
                        echo "<h1>Access denied</h1><p>Your IP address is blocked.</p>";
                        exit;
                    }
                } catch (Throwable $e) { /* ignore */ }
            }
        }
    } catch (Throwable $e) { error_log('admin ip/mac logging error: ' . $e->getMessage()); }
    ?>
    <main class="admin-main">
        <style>
        /* Minimal header dropdown styles (kept inline for reliability) */
        .notif-dropdown{position:relative;display:inline-block}
        .notif-panel{position:absolute;right:0;top:40px;width:360px;max-height:420px;overflow:auto;background:#fff;border:1px solid #e6e6e6;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.12);display:none;z-index:1000}
        .notif-item{padding:10px;border-bottom:1px solid #f1f1f1}
        .notif-item:last-child{border-bottom:none}
        .notif-empty{padding:20px;text-align:center;color:#666}
    </style>
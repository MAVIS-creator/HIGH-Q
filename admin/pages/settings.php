<?php
// admin/pages/settings.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/scan.php';

// Only users with 'settings' permission may access
requirePermission('settings');

// We'll store settings in the `settings` DB table under key 'system_settings'
// (table created in highq.sql). Use JSON-encoded value.
function loadSettingsFromDb(PDO $pdo, string $key = 'system_settings') {
    // Prefer the named key, but fall back to the first row if not present.
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    if (!$val) {
        // fallback to first settings row
        try {
            $stmt = $pdo->query("SELECT value FROM settings LIMIT 1");
            $val = $stmt->fetchColumn();
        } catch (Exception $e) {
            $val = null;
        }
    }
    if ($val) {
        $j = json_decode($val, true);
        return is_array($j) ? $j : [];
    }
    return [];
}

function saveSettingsToDb(PDO $pdo, array $data, string $key = 'system_settings') {
    $json = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    // upsert
    $stmt = $pdo->prepare("SELECT id FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $upd = $pdo->prepare("UPDATE settings SET `value` = ? WHERE id = ?");
        return $upd->execute([$json, $id]);
    } else {
        $ins = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?, ?)");
        return $ins->execute([$key, $json]);
    }
}

// Upsert into the new `site_settings` structured table for client-side SQL reads
function upsertSiteSettings(PDO $pdo, array $data) {
    // Map to columns with safe defaults
    $site = $data['site'] ?? [];
    $contact = $data['contact'] ?? [];
    $security = $data['security'] ?? [];

    $params = [
        'site_name' => $site['name'] ?? null,
        'tagline' => $site['tagline'] ?? null,
        'logo_url' => $site['logo'] ?? null,
        'vision' => $site['vision'] ?? null,
        'about' => $site['about'] ?? null,
        'contact_phone' => $contact['phone'] ?? null,
        'contact_email' => $contact['email'] ?? null,
        'contact_address' => $contact['address'] ?? null,
        'contact_facebook' => $contact['facebook'] ?? null,
        'contact_twitter' => $contact['twitter'] ?? null,
        'contact_instagram' => $contact['instagram'] ?? null,
        'maintenance' => !empty($security['maintenance']) ? 1 : 0,
        'registration' => isset($security['registration']) ? ($security['registration'] ? 1 : 0) : 1,
        'email_verification' => isset($security['email_verification']) ? ($security['email_verification'] ? 1 : 0) : 1,
        'two_factor' => !empty($security['two_factor']) ? 1 : 0,
        'comment_moderation' => !empty($security['comment_moderation']) ? 1 : 0
    ];

    // If a row exists, update the first row; otherwise insert
    try {
        $stmt = $pdo->query('SELECT id FROM site_settings ORDER BY id ASC LIMIT 1');
        $id = $stmt->fetchColumn();
    } catch (Exception $e) { $id = false; }

    if ($id) {
        $sql = "UPDATE site_settings SET
            site_name = :site_name, tagline = :tagline, logo_url = :logo_url,
            vision = :vision, about = :about,
            contact_phone = :contact_phone, contact_email = :contact_email, contact_address = :contact_address,
            contact_facebook = :contact_facebook, contact_twitter = :contact_twitter, contact_instagram = :contact_instagram,
            maintenance = :maintenance, registration = :registration, email_verification = :email_verification,
            two_factor = :two_factor, comment_moderation = :comment_moderation, updated_at = NOW()
            WHERE id = :id";
        $params['id'] = $id;
        $upd = $pdo->prepare($sql);
        return $upd->execute($params);
    } else {
        $sql = "INSERT INTO site_settings
            (site_name, tagline, logo_url, vision, about,
             contact_phone, contact_email, contact_address,
             contact_facebook, contact_twitter, contact_instagram,
             maintenance, registration, email_verification, two_factor, comment_moderation)
            VALUES
            (:site_name, :tagline, :logo_url, :vision, :about,
             :contact_phone, :contact_email, :contact_address,
             :contact_facebook, :contact_twitter, :contact_instagram,
             :maintenance, :registration, :email_verification, :two_factor, :comment_moderation)";
        $ins = $pdo->prepare($sql);
        return $ins->execute($params);
    }
}

// performSecurityScan() has been moved to `admin/includes/scan.php` and is required earlier.

// Default settings structure
$defaults = [
    'site' => [
        'name' => 'HIGH Q SOLID ACADEMY',
        'tagline' => '',
        'logo' => '',
        'vision' => '',
        'about' => ''
    ],
    'contact' => [
        'phone' => '',
        'email' => '',
        'address' => '',
        'facebook' => '',
        'twitter' => '',
        'instagram' => ''
    ],
    'security' => [
        'maintenance' => false,
        'registration' => true,
        'email_verification' => true,
        'two_factor' => false,
        'comment_moderation' => true
    ],
    'notifications' => [
        'email' => true,
        'sms' => false,
        'push' => true
    ],
    'advanced' => [
        'ip_logging' => true,
        'security_scanning' => false,
        'brute_force' => true,
        'ssl_enforce' => false,
        'auto_backup' => true,
        'max_login_attempts' => 5,
        'session_timeout' => 30
    ]
];

// Load current settings from DB (merge with defaults)
$current = $defaults;
$dbSettings = [];
try { $dbSettings = loadSettingsFromDb($pdo); } catch (Exception $e) { $dbSettings = []; }
if (is_array($dbSettings) && !empty($dbSettings)) {
    $current = array_replace_recursive($defaults, $dbSettings);
}

// Handle save action (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken('settings_form', $token)) {
        http_response_code(400);
        $err = 'Invalid CSRF token.';
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['status' => 'error', 'message' => $err]);
            exit;
        }
        setFlash('error', $err);
        header('Location: ?pages=settings');
        exit;
    }

    // Extract posted settings (safe mapping)
    $posted = $_POST['settings'] ?? [];
    // Start from current and only replace known keys to avoid injection
    $next = $current;

    // Helper to set nested keys safely
    $safeSet = function (&$arr, $path, $value) {
        $ptr = &$arr;
        foreach ($path as $p) {
            if (!isset($ptr[$p]) || !is_array($ptr[$p])) $ptr[$p] = [];
            $ptr = &$ptr[$p];
        }
        $ptr = $value;
    };

    // Site
    if (isset($posted['site']) && is_array($posted['site'])) {
        foreach (['name','tagline','logo','vision','about'] as $k) {
            if (isset($posted['site'][$k])) $safeSet($next, ['site', $k], trim($posted['site'][$k]));
        }
    }

    // Contact
    if (isset($posted['contact']) && is_array($posted['contact'])) {
        foreach (['phone','email','address','facebook','twitter','instagram'] as $k) {
            if (isset($posted['contact'][$k])) $safeSet($next, ['contact', $k], trim($posted['contact'][$k]));
        }
    }

    // Security toggles
    if (isset($posted['security']) && is_array($posted['security'])) {
        foreach ($defaults['security'] as $k => $v) {
            $val = isset($posted['security'][$k]) ? (bool)$posted['security'][$k] : false;
            $safeSet($next, ['security', $k], $val);
        }
    }

    // Notifications
    if (isset($posted['notifications']) && is_array($posted['notifications'])) {
        foreach ($defaults['notifications'] as $k => $v) {
            $val = isset($posted['notifications'][$k]) ? (bool)$posted['notifications'][$k] : false;
            $safeSet($next, ['notifications', $k], $val);
        }
    }

    // Advanced
    if (isset($posted['advanced']) && is_array($posted['advanced'])) {
        foreach (['ip_logging','security_scanning','brute_force','ssl_enforce','auto_backup'] as $k) {
            $val = isset($posted['advanced'][$k]) ? (bool)$posted['advanced'][$k] : false;
            $safeSet($next, ['advanced', $k], $val);
        }
        // numeric fields
        $maxAttempts = intval($posted['advanced']['max_login_attempts'] ?? $current['advanced']['max_login_attempts']);
        $sessionTimeout = intval($posted['advanced']['session_timeout'] ?? $current['advanced']['session_timeout']);
        $safeSet($next, ['advanced', 'max_login_attempts'], max(1, $maxAttempts));
        $safeSet($next, ['advanced', 'session_timeout'], max(1, $sessionTimeout));
    }

    // Persist to DB
    $saved = false;
    try {
        $saved = saveSettingsToDb($pdo, $next);
    } catch (Exception $e) {
        $saved = false;
    }

    if ($saved) {
        // Also upsert into the structured site_settings table for client-side SQL reads
        try {
            upsertSiteSettings($pdo, $next);
        } catch (Exception $e) {
            // non-fatal; we'll continue but log an audit entry
            try { logAction($pdo, $_SESSION['user']['id'] ?? 0, 'site_settings_upsert_failed', ['error'=>$e->getMessage()]); } catch (Exception $ee) {}
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['status' => 'ok', 'message' => 'Settings saved.']);
            exit;
        }
        setFlash('success', 'Settings saved.');
        header('Location: ?pages=settings');
        exit;
    }

    // Save failed
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save settings. Check DB permissions.']);
        exit;
    }
    setFlash('error', 'Failed to save settings. Check DB permissions.');
    header('Location: ?pages=settings');
    exit;
}

// Handle AJAX actions (runScan / clearIPs / clearLogs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $action = $_POST['action'];
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken('settings_form', $token)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }
    try {
        if ($action === 'runScan') {
            // Queue the CLI scan runner asynchronously so large scans don't time out.
            $php = PHP_BINARY;
            $root = realpath(__DIR__ . '/../../');
            $runner = $root . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scan-runner.php';
            if (!is_file($runner)) {
                echo json_encode(['status' => 'error', 'message' => 'Scan runner not available']);
                exit;
            }

            // Launch background process
            if (strtoupper(substr(PHP_OS,0,3)) === 'WIN') {
                // Windows: use start /B
                $cmd = "start /B " . escapeshellarg($php) . ' ' . escapeshellarg($runner);
                pclose(popen($cmd, 'r'));
            } else {
                // Unix-like: nohup
                $cmd = "nohup " . escapeshellarg($php) . ' ' . escapeshellarg($runner) . " > /dev/null 2>&1 &";
                @exec($cmd);
            }

            // Log queue action
            try { logAction($pdo, $_SESSION['user']['id'] ?? 0, 'security_scan_queued', ['by' => $_SESSION['user']['email'] ?? null]); } catch (Exception $e) {}

            echo json_encode(['status' => 'ok', 'message' => 'Security scan queued; you will receive an email when it completes.']);
            exit;
        }
        if ($action === 'clearIPs') {
            // Clear login attempts table
            $count = $pdo->exec("DELETE FROM login_attempts");
            echo json_encode(['status' => 'ok', 'message' => 'Cleared blocked IPs.', 'rows' => $count]);
            exit;
        }
        if ($action === 'clearLogs') {
            // Delete audit logs (CAUTION) - we keep the very first seed entry id=1 if exists
            $count = $pdo->exec("DELETE FROM audit_logs WHERE id > 1");
            echo json_encode(['status' => 'ok', 'message' => 'Cleared audit logs (except first seed).', 'rows' => $count]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
    exit;
}

// Export/import endpoints (GET for export, POST for import file)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export') {
    // export settings and audit logs as a zip-like JSON payload
    header('Content-Type: application/json');
    $settings = loadSettingsFromDb($pdo);
    $logs = [];
    try {
        $stmt = $pdo->query('SELECT * FROM audit_logs ORDER BY id ASC');
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $logs = []; }
    echo json_encode(['settings' => $settings, 'audit_logs' => $logs], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}

// Secure report download (serve files from storage/reports to authenticated admins)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['report'])) {
    // requirePermission already called above; just validate filename
    $name = basename($_GET['report']);
    $root = realpath(__DIR__ . '/../../');
    $reportsDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'reports';
    $path = realpath($reportsDir . DIRECTORY_SEPARATOR . $name);
    if (!$path || strpos($path, $reportsDir) !== 0 || !is_readable($path)) {
        http_response_code(404);
        echo 'Report not found';
        exit;
    }
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    readfile($path);
    exit;
}

    // Download audit logs as CSV attachment
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'download_logs') {
        try {
            $stmt = $pdo->query('SELECT * FROM audit_logs ORDER BY id ASC');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $rows = []; }

        // send CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_logs_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        if ($out) {
            // header row
            fputcsv($out, ['id','user_id','action','ip','user_agent','meta','created_at']);
            foreach ($rows as $r) {
                // ensure meta is a JSON string (flatten) and preserve values
                $meta = is_string($r['meta']) ? $r['meta'] : json_encode($r['meta']);
                fputcsv($out, [
                    $r['id'] ?? '',
                    $r['user_id'] ?? '',
                    $r['action'] ?? '',
                    $r['ip'] ?? '',
                    $r['user_agent'] ?? '',
                    $meta,
                    $r['created_at'] ?? ''
                ]);
            }
            fclose($out);
        }
        exit;
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $token = $_POST['_csrf'] ?? '';
    if (!verifyToken('settings_form', $token)) { setFlash('error','Invalid CSRF token for import'); header('Location: ?pages=settings'); exit; }
    $tmp = $_FILES['import_file']['tmp_name'] ?? null;
    if (!$tmp || !is_readable($tmp)) { setFlash('error','No file uploaded or unreadable'); header('Location: ?pages=settings'); exit; }
    $raw = @file_get_contents($tmp);
    $json = @json_decode($raw, true);
    if (!is_array($json) || empty($json['settings'])) { setFlash('error','Invalid import file'); header('Location: ?pages=settings'); exit; }
    $ok = saveSettingsToDb($pdo, $json['settings']);
    if ($ok) { setFlash('success','Settings imported'); header('Location: ?pages=settings'); exit; }
    setFlash('error','Failed to import settings'); header('Location: ?pages=settings'); exit;
}

// Page rendering
$pageTitle = 'System Settings';
$pageSubtitle = 'Configure site settings and security options';
// header.php already includes the correct admin stylesheet; avoid adding a relative duplicate here
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// CSRF token for form
$csrf = generateToken('settings_form');
?>
<div class="roles-page">
    <div class="page-header">
        <div>
            <h1><i class="bx bxs-cog"></i> System Settings</h1>
            <p>Configure site settings and security options</p>
        </div>
        <div>
            <button id="saveTop" class="header-cta">Save Changes</button>
        </div>
    </div>

    <?php if ($flash = getFlash()): ?>
        <div class="alert <?= $flash['type'] === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <form id="settingsForm" method="post">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="tabs">
            <button type="button" class="tab-btn active" data-tab="site">Site Information</button>
            <button type="button" class="tab-btn" data-tab="contact">Contact Information</button>
            <button type="button" class="tab-btn" data-tab="security">Security & System Settings</button>
            <button type="button" class="tab-btn" data-tab="notifications">Notifications</button>
            <button type="button" class="tab-btn" data-tab="advanced">Advanced Security</button>
        </div>

        <div class="tab-panels">
            <div class="tab-panel" data-panel="site" style="display:block">
                <div class="card">
                    <h3><i class="bx bxs-building"></i> Site Information</h3>
                    <p class="muted">Basic details that appear across the site (name, logo and short descriptions).</p>
                    <label>Site Name</label>
                    <input name="settings[site][name]" placeholder="Education Academy" value="<?= htmlspecialchars($current['site']['name']) ?>" class="input">
                    <label>Tagline</label>
                    <input name="settings[site][tagline]" placeholder="Excellence in Education" value="<?= htmlspecialchars($current['site']['tagline']) ?>" class="input">
                    <label>Logo URL</label>
                    <input name="settings[site][logo]" placeholder="https://example.com/logo.png" value="<?= htmlspecialchars($current['site']['logo']) ?>" class="input">
                    <label>Vision Statement</label>
                    <textarea name="settings[site][vision]" placeholder="To be the leading educational institution in our region" class="input" rows="3"><?= htmlspecialchars($current['site']['vision']) ?></textarea>
                    <label>About Description</label>
                    <textarea name="settings[site][about]" placeholder="Providing quality education and training programs" class="input" rows="4"><?= htmlspecialchars($current['site']['about']) ?></textarea>
                </div>
            </div>

            <div class="tab-panel" data-panel="contact">
                <div class="card">
                                        <h3><i class="bx bxs-phone"></i> Contact Information</h3>
                                        <p class="muted">Contact details used on public pages and in communications.</p>
                                        <label>Phone Number</label>
                                        <input name="settings[contact][phone]" placeholder="+1234567890" value="<?= htmlspecialchars($current['contact']['phone']) ?>" class="input">
                                        <label>Email Address</label>
                                        <input name="settings[contact][email]" placeholder="contact@academy.com" value="<?= htmlspecialchars($current['contact']['email']) ?>" class="input">
                                        <label>Physical Address</label>
                                        <textarea name="settings[contact][address]" placeholder="123 Education Street, Learning City" class="input" rows="3"><?= htmlspecialchars($current['contact']['address']) ?></textarea>
                                        <div style="display:flex;gap:8px;">
                                            <input name="settings[contact][facebook]" placeholder="https://facebook.com/..." value="<?= htmlspecialchars($current['contact']['facebook']) ?>" class="input">
                                            <input name="settings[contact][twitter]" placeholder="https://twitter.com/..." value="<?= htmlspecialchars($current['contact']['twitter']) ?>" class="input">
                                            <input name="settings[contact][instagram]" placeholder="https://instagram.com/..." value="<?= htmlspecialchars($current['contact']['instagram']) ?>" class="input">
                                        </div>
                </div>
            </div>

            <div class="tab-panel" data-panel="security">
                <div class="card">
                    <h3><i class="bx bxs-shield"></i> Security & System Settings</h3>
                    <p class="muted">Core security features and user-related system options.</p>
                    <?php
                        $sec = [
                            'maintenance' => ['label'=>'Maintenance Mode','desc'=>'Temporarily disable public access to the site'],
                            'registration' => ['label'=>'User Registration','desc'=>'Allow new users to register for accounts'],
                            'email_verification' => ['label'=>'Email Verification','desc'=>'Require email verification for new accounts'],
                            'two_factor' => ['label'=>'Two-Factor Authentication','desc'=>'Enable 2FA for enhanced security'],
                            'comment_moderation' => ['label'=>'Comment Moderation','desc'=>'Require approval before comments are published']
                        ];
                        foreach ($sec as $k=>$meta) {
                            $v = !empty($current['security'][$k]);
                    ?>
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:8px 0;">
                            <div style="max-width:78%;">
                                <strong><?= htmlspecialchars($meta['label']) ?></strong>
                                <div class="muted" style="margin-top:6px"><?= htmlspecialchars($meta['desc']) ?></div>
                            </div>
                            <div>
                                <label class="toggle">
                                    <input type="checkbox" name="settings[security][<?= $k ?>]" value="1" <?= $v ? 'checked' : '' ?> />
                                    <span class="track"><span class="thumb"></span></span>
                                </label>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="tab-panel" data-panel="notifications">
                <div class="card">
                    <h3><i class="bx bxs-bell"></i> Notifications</h3>
                    <p class="muted">Choose which channels the system should use to notify users.</p>
                    <?php
                        $notes = [
                            'email' => ['label'=>'Email Notifications','desc'=>'Send email notifications for important events'],
                            'sms' => ['label'=>'SMS Notifications','desc'=>'Send SMS notifications for critical alerts'],
                            'push' => ['label'=>'Push Notifications','desc'=>'Send browser push notifications']
                        ];
                        foreach ($notes as $k=>$meta) {
                            $v = !empty($current['notifications'][$k]);
                    ?>
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:8px 0;">
                            <div style="max-width:78%;">
                                <strong><?= htmlspecialchars($meta['label']) ?></strong>
                                <div class="muted" style="margin-top:6px"><?= htmlspecialchars($meta['desc']) ?></div>
                            </div>
                            <div>
                                <label class="toggle">
                                    <input type="checkbox" name="settings[notifications][<?= $k ?>]" value="1" <?= $v ? 'checked' : '' ?> />
                                    <span class="track"><span class="thumb"></span></span>
                                </label>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="tab-panel" data-panel="advanced">
                <div class="card">
                    <h3><i class="bx bxs-shield-quarter"></i> Advanced Security</h3>
                    <p class="muted">Advanced protections and diagnostic options for administrators.</p>
                    <?php
                        $adv = [
                          'ip_logging'=>['label'=>'IP Address Logging','desc'=>'Log visitor IP addresses for security monitoring'],
                          'security_scanning'=>['label'=>'Security Scanning','desc'=>'Automatically scan for security vulnerabilities'],
                          'brute_force'=>['label'=>'Brute Force Protection','desc'=>'Block IPs after 5 failed login attempts'],
                          'ssl_enforce'=>['label'=>'SSL Enforcement','desc'=>'Force HTTPS connections for all users'],
                          'auto_backup'=>['label'=>'Automated Backups','desc'=>'Automatically backup site data daily']
                        ];
                        foreach ($adv as $k=>$meta) {
                            $v = !empty($current['advanced'][$k]);
                    ?>
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:8px 0;">
                            <div style="max-width:78%;">
                                <strong><?= htmlspecialchars($meta['label']) ?></strong>
                                <div class="muted" style="margin-top:6px"><?= htmlspecialchars($meta['desc']) ?></div>
                            </div>
                            <div>
                                <label class="toggle">
                                    <input type="checkbox" name="settings[advanced][<?= $k ?>]" value="1" <?= $v ? 'checked' : '' ?> />
                                    <span class="track"><span class="thumb"></span></span>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <label>Max Login Attempts</label>
                    <input type="number" min="1" name="settings[advanced][max_login_attempts]" value="<?= htmlspecialchars($current['advanced']['max_login_attempts']) ?>" class="input">
                    <label>Session Timeout (minutes)</label>
                    <input type="number" min="1" name="settings[advanced][session_timeout]" value="<?= htmlspecialchars($current['advanced']['session_timeout']) ?>" class="input">

                    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <button type="button" id="runScan" class="header-cta">Run Security Scan</button>
                        <button type="button" id="clearIPs" class="btn">Clear Blocked IPs</button>
                        <button type="button" id="clearLogs" class="btn">Clear Logs</button>
                        <button type="button" id="downloadLogs" class="btn">Download Logs</button>
                        <button type="button" id="exportClear" class="btn">Export &amp; Clear Logs</button>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:18px;">
            <button type="submit" class="header-cta">Save Changes</button>
        </div>
    </form>

    <script>
    (function(){
        // Tabs
        document.querySelectorAll('.tab-btn').forEach(function(b){
            b.addEventListener('click', function(){
                document.querySelectorAll('.tab-btn').forEach(x=>x.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(x=>x.style.display='none');
                b.classList.add('active');
                var t = b.getAttribute('data-tab');
                document.querySelector('.tab-panel[data-panel="'+t+'"]').style.display='block';
            });
        });

        // AJAX submit for better UX
        var form = document.getElementById('settingsForm');
        form.addEventListener('submit', function(e){
            e.preventDefault();
            var data = new FormData(form);
            // mark as ajax
            var xhr = new XMLHttpRequest();
            xhr.open('POST', location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function(){
                try { var res = JSON.parse(xhr.responseText); } catch(e){ alert('Unexpected response'); return; }
                if (res.status === 'ok') { alert(res.message || 'Saved'); location.reload(); }
                else alert(res.message || 'Save failed');
            };
            xhr.send(data);
        });

        // Top save button
        document.getElementById('saveTop').addEventListener('click', function(){ document.querySelector('#settingsForm button[type=submit]').click(); });

        // Simple actions (AJAX)
        function doAction(action) {
            var fd = new FormData();
            fd.append('action', action);
            fd.append('_csrf', document.querySelector('input[name="_csrf"]').value);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function(){
                try { var res = JSON.parse(xhr.responseText); } catch(e) { alert('Unexpected response'); return; }
                if (res.status === 'ok') {
                    alert(res.message || 'Done');
                    if (action === 'clearIPs' || action === 'clearLogs') location.reload();
                } else {
                    alert(res.message || 'Action failed');
                }
            };
            xhr.send(fd);
        }

        document.getElementById('runScan').addEventListener('click', function(){ if (confirm('Start security scan?')) doAction('runScan'); });
        document.getElementById('clearIPs').addEventListener('click', function(){ if (confirm('Clear blocked IPs?')) doAction('clearIPs'); });
        document.getElementById('clearLogs').addEventListener('click', function(){ if (confirm('Clear audit logs (except seed)?')) doAction('clearLogs'); });
        document.getElementById('downloadLogs').addEventListener('click', function(){
            // open download endpoint in new tab to stream CSV
            var token = document.querySelector('input[name="_csrf"]').value;
            var url = location.pathname + '?action=download_logs&_csrf=' + encodeURIComponent(token);
            window.open(url, '_blank');
        });
        document.getElementById('exportClear').addEventListener('click', function(){
            if (!confirm('This will export audit logs and then clear them. Continue?')) return;
            // trigger download first
            var token = document.querySelector('input[name="_csrf"]').value;
            var url = location.pathname + '?action=download_logs&_csrf=' + encodeURIComponent(token);
            // open download in new tab then call clear
            var w = window.open(url, '_blank');
            setTimeout(function(){
                // then clear via AJAX
                var fd = new FormData(); fd.append('action','clearLogs'); fd.append('_csrf', token);
                var xhr = new XMLHttpRequest(); xhr.open('POST', location.href, true); xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
                xhr.onload = function(){ try { var res = JSON.parse(xhr.responseText); } catch(e){ alert('Clear failed'); return; } if (res.status==='ok') { alert('Exported and cleared logs'); location.reload(); } else alert('Clear failed: '+(res.message||'unknown')); };
                xhr.send(fd);
            }, 1500);
        });
    })();
    </script>

<?php
require_once __DIR__ . '/../includes/footer.php';

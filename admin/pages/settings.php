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
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
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

/**
 * Perform a security scan across the project folder.
 * Returns an array report with summary and details.
 */
function performSecurityScan(PDO $pdo, array $currentSettings = []) {
    $root = realpath(__DIR__ . '/../../'); // project root
    $report = [
        'started_at' => date('c'),
        'root' => $root,
        'totals' => [
            'files_scanned' => 0,
            'php_syntax_errors' => 0,
            'suspicious_patterns' => 0,
            'writable_files' => 0,
            'exposed_files' => 0,
        ],
        'errors' => [],
        'suspicious' => [],
        'writable' => [],
        'exposed' => [],
    ];

    if (!$root || !is_dir($root)) {
        $report['errors'][] = 'Unable to determine project root.';
        return $report;
    }

    $allowedExt = ['php','phtml','inc','html','htm','js','css','env','sql'];
    $limitFiles = 5000; // safety cap
    $count = 0;

    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $count++;
        if ($count > $limitFiles) {
            $report['errors'][] = "File scan limit reached ({$limitFiles})";
            break;
        }
        $report['totals']['files_scanned']++;
        $path = $file->getPathname();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) continue;

        // skip vendor to reduce noise
        if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;

        // Skip very large files
        if ($file->getSize() > 2 * 1024 * 1024) continue;

        $content = @file_get_contents($path);
        if ($content === false) continue;

        // PHP syntax check
        if (in_array($ext, ['php','phtml','inc'])) {
            // try running `php -l` if available
            $escaped = escapeshellarg($path);
            $out = null; $rc = null;
            @exec("php -l $escaped 2>&1", $out, $rc);
            $outStr = is_array($out) ? implode("\n", $out) : trim((string)$out);
            if ($rc !== 0) {
                $report['totals']['php_syntax_errors']++;
                $report['errors'][] = ['file' => $path, 'error' => $outStr];
            }
        }

        // Suspicious patterns
        $patterns = [
            '/\beval\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bshell_exec\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\bbase64_decode\s*\(/i',
            '/preg_replace\s*\(.*\/[e][^\/]*\//i',
            '/\bassert\s*\(/i',
            '/\bcreate_function\s*\(/i',
            '/\bphpinfo\s*\(/i'
        ];
        foreach ($patterns as $pat) {
            if (preg_match($pat, $content)) {
                $report['totals']['suspicious_patterns']++;
                $report['suspicious'][] = ['file' => $path, 'pattern' => $pat];
                break;
            }
        }

        // Exposed .env or config values
        if (basename($path) === '.env' || stripos($content, 'DB_PASSWORD') !== false || stripos($content, 'DB_USER') !== false) {
            // if file is inside a public web folder (public/ or www/ or htdocs) mark exposed
            if (preg_match('#[\\/](public|www|htdocs)[\\/]#i', $path)) {
                $report['totals']['exposed_files']++;
                $report['exposed'][] = ['file' => $path, 'reason' => 'credentials or .env in public folder'];
            }
        }

        // Writable files
        if (is_writable($path)) {
            $report['totals']['writable_files']++;
            $report['writable'][] = $path;
        }
    }

    $report['finished_at'] = date('c');
    // build a short summary
    $report['summary'] = sprintf("Scanned %d files. Syntax errors: %d. Suspicious files: %d. Writable files: %d. Exposed files: %d",
        $report['totals']['files_scanned'], $report['totals']['php_syntax_errors'], $report['totals']['suspicious_patterns'], $report['totals']['writable_files'], $report['totals']['exposed_files']);

    return $report;
}

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
            // Run the full security scan, save report, log and email results
            $uid = $_SESSION['user']['id'] ?? null;
            $userEmail = $_SESSION['user']['email'] ?? null;

            // perform scan
            $report = performSecurityScan($pdo, $current);

            // ensure uploads folder exists under admin
            $reportsDir = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports';
            if (!is_dir($reportsDir)) @mkdir($reportsDir, 0755, true);
            $filename = 'scan_' . date('Ymd_His') . '.json';
            $filePath = $reportsDir . DIRECTORY_SEPARATOR . $filename;
            @file_put_contents($filePath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // log audit entry
            $meta = ['summary' => $report['summary'] ?? '', 'file' => '/admin/uploads/reports/' . $filename];
            try {
                logAction($pdo, $uid ?? 0, 'security_scan_completed', $meta);
            } catch (Exception $e) {
                // fallback insert
                $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip, user_agent, meta) VALUES (?, ?, ?, ?, ?)");
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $stmt->execute([$uid, 'security_scan_completed', $ip, $ua, json_encode($meta)]);
            }

            // Email recipients: logged-in user (if available), site admin from settings, and hard-coded address
            $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $downloadUrl = $siteUrl . '/admin/uploads/reports/' . $filename;

            $recipients = [];
            if (!empty($userEmail)) $recipients[] = $userEmail;
            // try to use contact email from settings
            $adminEmail = $current['contact']['email'] ?? null;
            if (!empty($adminEmail)) $recipients[] = $adminEmail;
            $recipients[] = 'highqsolidacademy@gmail.com';

            // build email content
            $subject = 'HIGH-Q Security Scan Report - ' . date('Y-m-d H:i:s');
            $html = "<h2>Security Scan Completed</h2>";
            $html .= "<p>Summary: " . htmlspecialchars($report['summary'] ?? '') . "</p>";
            $html .= "<p>Scanned: " . intval($report['totals']['files_scanned'] ?? 0) . " files. PHP syntax errors: " . intval($report['totals']['php_syntax_errors'] ?? 0) . ". Suspicious files: " . intval($report['totals']['suspicious_patterns'] ?? 0) . ". Writable files: " . intval($report['totals']['writable_files'] ?? 0) . ". Exposed files: " . intval($report['totals']['exposed_files'] ?? 0) . "</p>";
            $html .= "<p>Download full report: <a href='" . htmlspecialchars($downloadUrl) . "'>" . htmlspecialchars($downloadUrl) . "</a></p>";

            // include a short list of issues (limited)
            $html .= "<h3>Top findings</h3>";
            $html .= "<ul>";
            $max = 20; $n = 0;
            foreach (['errors','suspicious','writable','exposed'] as $key) {
                if (!empty($report[$key])) {
                    foreach ($report[$key] as $item) {
                        if ($n++ > $max) break 2;
                        if (is_array($item)) $html .= '<li>' . htmlspecialchars(json_encode($item)) . '</li>';
                        else $html .= '<li>' . htmlspecialchars((string)$item) . '</li>';
                    }
                }
            }
            $html .= "</ul>";

            $sent = [];
            foreach (array_unique($recipients) as $to) {
                if (empty($to)) continue;
                // use sendEmail from includes/functions.php
                try {
                    $ok = sendEmail($to, $subject, $html);
                    $sent[$to] = $ok ? 'ok' : 'failed';
                } catch (Exception $e) {
                    $sent[$to] = 'error';
                }
            }

            echo json_encode(['status' => 'ok', 'message' => 'Security scan completed. Emails attempted to: ' . implode(', ', array_keys($sent)), 'report' => $report]);
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
$pageCss = '<link rel="stylesheet" href="../assets/css/admin.css">';
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
                    <h3>Site Information</h3>
                    <label>Site Name</label>
                    <input name="settings[site][name]" value="<?= htmlspecialchars($current['site']['name']) ?>" class="input">
                    <label>Tagline</label>
                    <input name="settings[site][tagline]" value="<?= htmlspecialchars($current['site']['tagline']) ?>" class="input">
                    <label>Logo URL</label>
                    <input name="settings[site][logo]" value="<?= htmlspecialchars($current['site']['logo']) ?>" class="input">
                    <label>Vision Statement</label>
                    <textarea name="settings[site][vision]" class="input" rows="3"><?= htmlspecialchars($current['site']['vision']) ?></textarea>
                    <label>About Description</label>
                    <textarea name="settings[site][about]" class="input" rows="4"><?= htmlspecialchars($current['site']['about']) ?></textarea>
                </div>
            </div>

            <div class="tab-panel" data-panel="contact">
                <div class="card">
                    <h3>Contact Information</h3>
                    <label>Phone Number</label>
                    <input name="settings[contact][phone]" value="<?= htmlspecialchars($current['contact']['phone']) ?>" class="input">
                    <label>Email Address</label>
                    <input name="settings[contact][email]" value="<?= htmlspecialchars($current['contact']['email']) ?>" class="input">
                    <label>Physical Address</label>
                    <textarea name="settings[contact][address]" class="input" rows="3"><?= htmlspecialchars($current['contact']['address']) ?></textarea>
                    <div style="display:flex;gap:8px;">
                      <input name="settings[contact][facebook]" placeholder="Facebook URL" value="<?= htmlspecialchars($current['contact']['facebook']) ?>" class="input">
                      <input name="settings[contact][twitter]" placeholder="Twitter URL" value="<?= htmlspecialchars($current['contact']['twitter']) ?>" class="input">
                      <input name="settings[contact][instagram]" placeholder="Instagram URL" value="<?= htmlspecialchars($current['contact']['instagram']) ?>" class="input">
                    </div>
                </div>
            </div>

            <div class="tab-panel" data-panel="security">
                <div class="card">
                    <h3>Security & System Settings</h3>
                    <?php foreach ($current['security'] as $k => $v): ?>
                        <label style="display:flex;justify-content:space-between;align-items:center;">
                            <span><?= htmlspecialchars(ucwords(str_replace('_',' ', $k))) ?></span>
                            <input type="checkbox" name="settings[security][<?= $k ?>]" value="1" <?= $v ? 'checked' : '' ?> />
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-panel" data-panel="notifications">
                <div class="card">
                    <h3>Notifications</h3>
                    <?php foreach ($current['notifications'] as $k => $v): ?>
                        <label style="display:flex;justify-content:space-between;align-items:center;">
                            <span><?= htmlspecialchars(ucwords($k)) ?></span>
                            <input type="checkbox" name="settings[notifications][<?= $k ?>]" value="1" <?= $v ? 'checked' : '' ?> />
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-panel" data-panel="advanced">
                <div class="card">
                    <h3>Advanced Security</h3>
                    <?php foreach (['ip_logging','security_scanning','brute_force','ssl_enforce','auto_backup'] as $k): $v = $current['advanced'][$k] ?? false; ?>
                        <label style="display:flex;justify-content:space-between;align-items:center;">
                            <span><?= htmlspecialchars(ucwords(str_replace('_',' ', $k))) ?></span>
                            <input type="checkbox" name="settings[advanced][<?= $k ?>]" value="1" <?= $v ? 'checked' : '' ?> />
                        </label>
                    <?php endforeach; ?>

                    <label>Max Login Attempts</label>
                    <input type="number" min="1" name="settings[advanced][max_login_attempts]" value="<?= htmlspecialchars($current['advanced']['max_login_attempts']) ?>" class="input">
                    <label>Session Timeout (minutes)</label>
                    <input type="number" min="1" name="settings[advanced][session_timeout]" value="<?= htmlspecialchars($current['advanced']['session_timeout']) ?>" class="input">

                    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" id="runScan" class="header-cta">Run Security Scan</button>
                        <button type="button" id="clearIPs" class="btn">Clear Blocked IPs</button>
                        <button type="button" id="clearLogs" class="btn">Clear Logs</button>
                        <button type="button" id="downloadLogs" class="btn">Download Logs</button>
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
    })();
    </script>

<?php
require_once __DIR__ . '/../includes/footer.php';

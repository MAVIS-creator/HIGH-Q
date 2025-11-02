<?php
// admin/api/save-settings.php
// JSON-only endpoint to save system settings (used by admin JS)

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$token = $_POST['_csrf'] ?? '';
if (!verifyToken('settings_form', $token)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

// Parse settings payload: accept either a JSON string in 'settings' or form fields prefixed with settings[...] via PHP's form parsing
// Build $posted from either a JSON payload or nested form fields (settings[...] )
$posted = [];
if (isset($_POST['settings'])) {
    // If PHP parsed settings[...] into an array, use it directly
    if (is_array($_POST['settings'])) {
        $posted = $_POST['settings'];
    } else {
        // Could be JSON string
        $raw = $_POST['settings'];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $posted = $decoded;
        }
    }
}

// Fallback: if form fields were sent as flat keys like 'settings[notifications][email]'
// they may not be present as a nested array (some clients or servers differ). Parse
// those keys into a nested $posted array so we can save correctly.
if (empty($posted)) {
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'settings[') === 0) {
            // Parse key like settings[security][two_factor]
            // Extract parts between brackets
            $parts = preg_split('/\[|\]/', $k, -1, PREG_SPLIT_NO_EMPTY);
            // parts[0] should be 'settings'
            if (count($parts) > 1 && $parts[0] === 'settings') {
                $path = array_slice($parts, 1);
                $ptr = &$posted;
                foreach ($path as $p) {
                    if ($p === '') continue;
                    if (!isset($ptr[$p]) || !is_array($ptr[$p])) $ptr[$p] = [];
                    $ptr = &$ptr[$p];
                }
                $ptr = $v;
                unset($ptr);
            }
        }
    }
}

// Fallback: if nothing was provided, return an error
if (!is_array($posted) || empty($posted)) {
    // Accept empty update as no-op but return OK
    echo json_encode(['status' => 'ok', 'message' => 'No settings provided; nothing to save']);
    exit;
}

// Minimal saveSettingsToDb logic (isolated copy to avoid including admin page)
try {
    $json = json_encode($posted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) throw new Exception('Failed to encode settings to JSON');

    // Log payload for debugging
    error_log('save-settings payload: ' . $json);

    // Upsert into settings table by key 'system_settings'
    $stmt = $pdo->prepare("SELECT id FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute(['system_settings']);
    $id = $stmt->fetchColumn();
    if ($id) {
        $upd = $pdo->prepare("UPDATE settings SET `value` = ? WHERE id = ?");
        $ok = $upd->execute([$json, $id]);
    } else {
        $ins = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?, ?)");
        $ok = $ins->execute(['system_settings', $json]);
    }

    if (!$ok) throw new Exception('Database save failed');

    // Try to upsert into site_settings structured table (best-effort)
    try {
        // Map to columns with safe defaults (mirrors admin/pages/settings.php)
        $site = $posted['site'] ?? [];
        $contact = $posted['contact'] ?? [];
        $security = $posted['security'] ?? [];

        $params = [
            'site_name' => $site['name'] ?? null,
            'tagline' => $site['tagline'] ?? null,
            'logo_url' => $site['logo'] ?? null,
            'bank_name' => $site['bank_name'] ?? null,
            'bank_account_name' => $site['bank_account_name'] ?? null,
            'bank_account_number' => $site['bank_account_number'] ?? null,
            'vision' => $site['vision'] ?? null,
            'about' => $site['about'] ?? null,
            'contact_phone' => $contact['phone'] ?? null,
            'contact_email' => $contact['email'] ?? null,
            'contact_address' => $contact['address'] ?? null,
            'contact_facebook' => $contact['facebook'] ?? null,
            // Map tiktok field into contact_tiktok column (fallback to legacy twitter)
            'contact_tiktok' => $contact['tiktok'] ?? $contact['twitter'] ?? null,
            'contact_instagram' => $contact['instagram'] ?? null,
            'maintenance' => !empty($security['maintenance']) ? 1 : 0,
            'maintenance_allowed_ips' => !empty($security['maintenance_allowed_ips']) ? $security['maintenance_allowed_ips'] : null,
            'allow_admin_public_view_during_maintenance' => !empty($security['allow_admin_public_view_during_maintenance']) ? 1 : 0,
            'registration' => isset($security['registration']) ? ($security['registration'] ? 1 : 0) : 1,
            'email_verification' => isset($security['email_verification']) ? ($security['email_verification'] ? 1 : 0) : 1,
            'two_factor' => !empty($security['two_factor']) ? 1 : 0,
            'comment_moderation' => !empty($security['comment_moderation']) ? 1 : 0
        ];
        error_log('save-settings: site_settings params: ' . json_encode($params));

        // Detect existing row
        $stmt = $pdo->query('SELECT id FROM site_settings ORDER BY id ASC LIMIT 1');
        $sid = $stmt->fetchColumn();
        if ($sid) {
            $params['id'] = $sid;
            $sql = "UPDATE site_settings SET
                site_name = :site_name, tagline = :tagline, logo_url = :logo_url,
                bank_name = :bank_name, bank_account_name = :bank_account_name, bank_account_number = :bank_account_number,
                vision = :vision, about = :about,
                contact_phone = :contact_phone, contact_email = :contact_email, contact_address = :contact_address,
                contact_facebook = :contact_facebook, contact_tiktok = :contact_tiktok, contact_instagram = :contact_instagram,
                maintenance = :maintenance, maintenance_allowed_ips = :maintenance_allowed_ips, allow_admin_public_view_during_maintenance = :allow_admin_public_view_during_maintenance, registration = :registration, email_verification = :email_verification,
                two_factor = :two_factor, comment_moderation = :comment_moderation, updated_at = NOW()
                WHERE id = :id";
            $upd = $pdo->prepare($sql);
            $res = $upd->execute($params);
            error_log('save-settings: site_settings update result: ' . json_encode(['sid'=>$sid,'res'=>$res,'params'=>$params]));
        } else {
            $sql = "INSERT INTO site_settings
                (site_name, tagline, logo_url, vision, about,
                 bank_name, bank_account_name, bank_account_number,
                 contact_phone, contact_email, contact_address,
                 contact_facebook, contact_tiktok, contact_instagram,
                 maintenance, maintenance_allowed_ips, allow_admin_public_view_during_maintenance, registration, email_verification, two_factor, comment_moderation)
                VALUES
                (:site_name, :tagline, :logo_url, :vision, :about,
                 :bank_name, :bank_account_name, :bank_account_number,
                 :contact_phone, :contact_email, :contact_address,
                 :contact_facebook, :contact_tiktok, :contact_instagram,
                 :maintenance, :maintenance_allowed_ips, :allow_admin_public_view_during_maintenance, :registration, :email_verification, :two_factor, :comment_moderation)";
            $ins = $pdo->prepare($sql);
            $res = $ins->execute($params);
            error_log('save-settings: site_settings insert result: ' . json_encode(['res'=>$res,'params'=>$params]));
        }
    } catch (Exception $e) {
        // Best-effort; log and continue
        error_log('save-settings: site_settings upsert error: ' . $e->getMessage());
    }

    // Log action
    try { logAction($pdo, $_SESSION['user']['id'] ?? 0, 'settings_saved', ['by' => $_SESSION['user']['email'] ?? null]); } catch (Exception $e) {}

    echo json_encode(['status' => 'ok', 'message' => 'Settings saved successfully']);
    exit;

} catch (Exception $e) {
    error_log('save-settings: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save settings: ' . $e->getMessage()]);
    exit;
}

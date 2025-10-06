<?php
// admin/api/maintenance-debug.php - Read-only diagnostic for maintenance mode
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';

$debug = ['ok' => true];
try {
    $row = null;
    try {
        $stmt = $pdo->query('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $row = null; }

    $legacy = [];
    try {
        $s = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $s->execute(['system_settings']);
        $val = $s->fetchColumn();
        if (!$val) {
            $s = $pdo->query('SELECT value FROM settings LIMIT 1');
            $val = $s->fetchColumn();
        }
        $legacy = $val ? json_decode($val, true) : [];
    } catch (Throwable $e) { $legacy = []; }

    // Determine maintenance flag
    $maintenance = false;
    if (!empty($row) && isset($row['maintenance'])) $maintenance = (bool)$row['maintenance'];
    elseif (!empty($legacy['security']['maintenance'])) $maintenance = (bool)$legacy['security']['maintenance'];

    // Determine allowed IPs
    $allowedIps = [];
    if (!empty($row) && !empty($row['maintenance_allowed_ips'])) {
        $allowedIps = array_filter(array_map('trim', explode(',', $row['maintenance_allowed_ips'])));
    } elseif (!empty($legacy['security']['maintenance_allowed_ips'])) {
        $ipCandidates = $legacy['security']['maintenance_allowed_ips'];
        if (is_array($ipCandidates)) $allowedIps = $ipCandidates;
        else $allowedIps = array_filter(array_map('trim', explode(',', $ipCandidates)));
    }

    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $ipAllowed = false;
    foreach ($allowedIps as $candidate) {
        if ($candidate === $remoteIp) { $ipAllowed = true; break; }
        if ($candidate !== '' && strpos($remoteIp, $candidate) === 0) { $ipAllowed = true; break; }
    }

    $debug['maintenance'] = $maintenance;
    $debug['allowed_ips'] = $allowedIps;
    $debug['remote_ip'] = $remoteIp;
    $debug['ip_allowed'] = $ipAllowed;
    $debug['site_settings_row'] = $row ? array_intersect_key($row, array_flip(['maintenance','maintenance_allowed_ips'])) : null;
    $debug['legacy_security'] = $legacy['security'] ?? null;

    // Also compute whether public header would block this request (approx)
    $isAdminBySession = false;
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
        $u = $_SESSION['user'];
        if (!empty($u['is_admin'])) $isAdminBySession = true;
        if (!empty($u['role']) && strtolower($u['role']) === 'admin') $isAdminBySession = true;
        if (!empty($u['role_slug']) && strtolower($u['role_slug']) === 'admin') $isAdminBySession = true;
        if (!empty($u['role_id']) && intval($u['role_id']) === 1) $isAdminBySession = true;
    }
    $debug['is_admin_session'] = $isAdminBySession;
    $debug['would_block_public'] = $maintenance && !($isAdminBySession || $ipAllowed);

    echo json_encode($debug, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

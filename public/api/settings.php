<?php
// public/api/settings.php
// Returns site settings as JSON for client-side code.
require_once __DIR__ . '/../../config/db.php';
// Try to load from structured site_settings table
try {
    $stmt = $pdo->query('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $row = false;
}

$out = [];
if ($row && is_array($row)) {
    // Map columns to a clean JSON structure
    $out = [
        'site' => [
            'name' => $row['site_name'] ?? '',
            'tagline' => $row['tagline'] ?? '',
            'logo' => $row['logo_url'] ?? '',
            'bank_name' => $row['bank_name'] ?? '',
            'bank_account_name' => $row['bank_account_name'] ?? '',
            'bank_account_number' => $row['bank_account_number'] ?? '',
            'vision' => $row['vision'] ?? '',
            'about' => $row['about'] ?? ''
        ],
        'contact' => [
            'phone' => $row['contact_phone'] ?? '',
            'email' => $row['contact_email'] ?? '',
            'address' => $row['contact_address'] ?? '',
            'facebook' => $row['contact_facebook'] ?? '',
            // expose tiktok key (stored in contact_twitter column for backward compatibility)
            'tiktok' => $row['contact_twitter'] ?? '',
            'instagram' => $row['contact_instagram'] ?? ''
        ],
        'security' => [
            'maintenance' => (bool)($row['maintenance'] ?? 0),
            'registration' => (bool)($row['registration'] ?? 1),
            'email_verification' => (bool)($row['email_verification'] ?? 1),
            'two_factor' => (bool)($row['two_factor'] ?? 0),
            'comment_moderation' => (bool)($row['comment_moderation'] ?? 1),
        ]
    ];
} else {
    // fallback to legacy `settings` JSON
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute(['system_settings']);
        $val = $stmt->fetchColumn();
        if (!$val) {
            $stmt = $pdo->query("SELECT value FROM settings LIMIT 1");
            $val = $stmt->fetchColumn();
        }
        $data = $val ? json_decode($val, true) : [];
        if (is_array($data)) $out = $data;
    } catch (Exception $e) { $out = []; }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

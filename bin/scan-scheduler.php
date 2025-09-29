<?php
// bin/scan-scheduler.php
// Run periodically (cron / Task Scheduler). This script checks settings for desired
// schedule (daily, weekly, monthly) and will invoke the scan-runner if due.
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../admin/includes/db.php';

$now = new DateTimeImmutable('now');

// Default scheduling: daily
$schedule = 'daily';
$lastRun = null;

try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute(['system_settings']);
    $val = $stmt->fetchColumn();
    if ($val) {
        $json = json_decode($val, true);
        if (is_array($json) && !empty($json['security']['scan_schedule'])) {
            $schedule = $json['security']['scan_schedule']; // expected: daily|weekly|monthly
        }
        if (is_array($json) && !empty($json['security']['last_scan_at'])) {
            $lastRun = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $json['security']['last_scan_at']);
        }
    }
} catch (Throwable $e) { error_log('scan-scheduler read settings failed: ' . $e->getMessage()); }

// determine if we should run now
$shouldRun = false;
if ($lastRun === null) {
    $shouldRun = true;
} else {
    switch ($schedule) {
        case 'weekly':
            $next = $lastRun->modify('+7 days');
            if ($now >= $next) $shouldRun = true;
            break;
        case 'monthly':
            $next = $lastRun->modify('+1 month');
            if ($now >= $next) $shouldRun = true;
            break;
        case 'daily':
        default:
            $next = $lastRun->modify('+1 day');
            if ($now >= $next) $shouldRun = true;
            break;
    }
}

if ($shouldRun) {
    // call the scan runner as a separate PHP process to avoid blocking
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/scan-runner.php') . ' ' . escapeshellarg(__DIR__ . '/../');
    // On Windows the PHP_BINARY may include spaces; using escapeshellarg is safer
    @exec($cmd . ' > NUL 2>&1 &');

    // update last_scan_at in settings
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute(['system_settings']);
        $val = $stmt->fetchColumn();
        $j = $val ? json_decode($val, true) : [];
        if (!is_array($j)) $j = [];
        if (!isset($j['security'])) $j['security'] = [];
        $j['security']['last_scan_at'] = $now->format('Y-m-d H:i:s');
        // Upsert back into settings table
        $upd = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
        $upd->execute([':k' => 'system_settings', ':v' => json_encode($j, JSON_UNESCAPED_SLASHES), ':v2' => json_encode($j, JSON_UNESCAPED_SLASHES)]);
    } catch (Throwable $e) { error_log('scan-scheduler update settings failed: ' . $e->getMessage()); }
}

echo "scan-scheduler completed. shouldRun=" . ($shouldRun ? '1' : '0') . "\n";

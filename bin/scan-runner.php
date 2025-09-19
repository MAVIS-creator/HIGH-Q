<?php
// bin/scan-runner.php
// Run from project root: php bin/scan-runner.php /path/to/project
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../admin/includes/db.php';
require __DIR__ . '/../admin/includes/functions.php';
require __DIR__ . '/../admin/includes/scan.php';

$root = realpath(__DIR__ . '/../');
$reportsDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'reports';
if (!is_dir($reportsDir)) mkdir($reportsDir, 0755, true);

// perform scan
$report = performSecurityScan($pdo, []);
$filename = 'scan_' . date('Ymd_His') . '.json';
$path = $reportsDir . DIRECTORY_SEPARATOR . $filename;
file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// log
logAction($pdo, 0, 'security_scan_completed_cli', ['file' => '/storage/reports/' . $filename, 'summary' => $report['summary'] ?? '']);

// email to main admin
$recipients = [];
// try to get admin email from settings table
try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute(['system_settings']);
    $val = $stmt->fetchColumn();
    if ($val) {
        $settings = json_decode($val, true);
        $adminEmail = $settings['contact']['email'] ?? null;
        if ($adminEmail) $recipients[] = $adminEmail;
    }
} catch (Exception $e) {}
$recipients[] = 'highqsolidacademy@gmail.com';

$subject = 'HIGH-Q Security Scan Report (CLI) - ' . date('Y-m-d H:i:s');
$html = '<h2>Security Scan Completed (CLI)</h2>';
$html .= '<p>' . htmlspecialchars($report['summary'] ?? '') . '</p>';

foreach (array_unique($recipients) as $to) {
    if (empty($to)) continue;
    sendEmail($to, $subject, $html, [$path]);
}

echo "Scan complete. Report saved to: $path\n";

<?php
// Sentinel: Multi-layer Security Scanner
// Layer A: Static Regex Scan
// Layer B: Integrity Monitor (MD5 baseline/check)
// Layer C: Supply Chain Auditor (composer.lock CVE check)
// Reporting: JSON report, email on critical

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Config
$scanDirs = [
	realpath(__DIR__ . '/../../public'),
	realpath(__DIR__ . '/../../admin'),
	realpath(__DIR__ . '/../../bin'),
	realpath(__DIR__ . '/../../scripts'),
];
$excludeFiles = ['.env', 'config.php', 'config/db.php'];
$baselineFile = __DIR__ . '/sentinel_baseline.json';
$reportDir = __DIR__ . '/../../storage/sentinel_reports';
if (!is_dir($reportDir)) @mkdir($reportDir, 0755, true);

// Layer A: Static Regex Scan
$patterns = [
$    'webshell' => '/eval\s*\(\s*base64_decode\s*\(/i',
$    'debug'    => '/APP_DEBUG\s*=\s*true/i',
$    'vuln_inc' => '/include\s*\(\s*\$_GET/i',
];

function scanFile($file, $patterns) {
	$results = [];
	$code = @file_get_contents($file);
	if ($code === false) return $results;
	foreach ($patterns as $name => $pat) {
		if (preg_match($pat, $code)) {
			$results[] = $name;
		}
	}
	return $results;
}

// Layer B: Integrity Monitor
function hashFile($file) {
	return @md5_file($file) ?: null;
}

// Layer C: Supply Chain Auditor
function auditComposer($lockFile) {
	$issues = [];
	if (!file_exists($lockFile)) return $issues;
	$json = @file_get_contents($lockFile);
	$data = json_decode($json, true);
	if (!$data || empty($data['packages'])) return $issues;
	// Example: check for PHPMailer < 6.6.0 (CVE-2023-5173)
	foreach ($data['packages'] as $pkg) {
		if ($pkg['name'] === 'phpmailer/phpmailer' && version_compare($pkg['version'], '6.6.0', '<')) {
			$issues[] = 'PHPMailer < 6.6.0 vulnerable (CVE-2023-5173)';
		}
	}
	return $issues;
}

// Scan logic
$findings = [];
$hashes = [];
foreach ($scanDirs as $dir) {
	if (!is_dir($dir)) continue;
	$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
	foreach ($rii as $file) {
		if ($file->isDir()) continue;
		$rel = str_replace(realpath(__DIR__ . '/../..') . '/', '', $file->getPathname());
		if (in_array(basename($file), $excludeFiles, true)) continue;
		// Layer A
		$scan = scanFile($file, $patterns);
		if ($scan) $findings[$rel] = $scan;
		// Layer B
		$hashes[$rel] = hashFile($file);
	}
}

// Baseline logic
$baseline = [];
if (file_exists($baselineFile)) {
	$baseline = json_decode(@file_get_contents($baselineFile), true) ?: [];
}
$changed = [];
foreach ($hashes as $file => $md5) {
	if (isset($baseline[$file]) && $baseline[$file] !== $md5) {
		$changed[$file] = ['old' => $baseline[$file], 'new' => $md5];
	}
}

// Layer C: Composer audit
$composerIssues = auditComposer(__DIR__ . '/../../composer.lock');

// Save report
$report = [
	'timestamp' => date('c'),
	'findings' => $findings,
	'changed' => $changed,
	'composer_issues' => $composerIssues,
];
$reportFile = $reportDir . '/sentinel_report_' . date('Ymd_His') . '.json';
@file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));

// Update baseline if requested
if (isset($_GET['set_baseline']) && $_GET['set_baseline'] === '1') {
	@file_put_contents($baselineFile, json_encode($hashes, JSON_PRETTY_PRINT));
	echo "Baseline updated.";
	exit;
}

// Email alert if critical
if (!empty($findings) || !empty($changed) || !empty($composerIssues)) {
	$critical = count($findings) + count($changed) + count($composerIssues);
	if ($critical > 0) {
		$to = $_ENV['ADMIN_EMAIL'] ?? ($_ENV['MAIL_FROM_ADDRESS'] ?? 'admin@example.com');
		$subject = "Sentinel Alert: $critical issue(s) detected";
		$body = "Security scan detected issues:\n" . json_encode($report, JSON_PRETTY_PRINT);
		sendEmail($to, $subject, nl2br(htmlspecialchars($body)));
	}
}

// Output summary
header('Content-Type: application/json');
echo json_encode($report);
Create Sentinel scanner backend with static regex scan, integrity monitor, supply chain audit, and reporting.
<?php
/**
 * bin/scan-scheduler.php
 * 
 * Scheduled Security Scan Orchestrator
 * Run periodically via cron or Windows Task Scheduler
 * Example cron: 0 2 * * * php /path/to/bin/scan-scheduler.php
 * 
 * Reads scan_schedule setting (daily/weekly/monthly) and runs security scans
 * Uses the consolidated admin/api/scan-engine.php for all scan logic
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../admin/includes/db.php';
require __DIR__ . '/../admin/includes/functions.php';

$now = new DateTimeImmutable('now');
error_log('scan-scheduler started at ' . $now->format('Y-m-d H:i:s'));

// Default schedule: daily
$schedule = 'daily';
$lastRun = null;
$scanType = 'full'; // Default to full scan

try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute(['system_settings']);
    $val = $stmt->fetchColumn();
    if ($val) {
        $json = json_decode($val, true);
        if (is_array($json)) {
            $schedule = $json['security']['scan_schedule'] ?? 'daily';
            $scanType = $json['security']['scan_type'] ?? 'full';
            if (!empty($json['security']['last_scan_at'])) {
                $lastRun = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $json['security']['last_scan_at']);
            }
        }
    }
} catch (Throwable $e) {
    error_log('scan-scheduler: Failed to read settings: ' . $e->getMessage());
}

// Determine if scan should run
$shouldRun = false;
$reason = '';

if ($lastRun === null) {
    $shouldRun = true;
    $reason = 'First scan scheduled';
} else {
    switch ($schedule) {
        case 'weekly':
            $next = $lastRun->modify('+7 days');
            if ($now >= $next) {
                $shouldRun = true;
                $reason = 'Weekly schedule due';
            }
            break;
            
        case 'monthly':
            $next = $lastRun->modify('+1 month');
            if ($now >= $next) {
                $shouldRun = true;
                $reason = 'Monthly schedule due';
            }
            break;
            
        case 'daily':
        default:
            $next = $lastRun->modify('+1 day');
            if ($now >= $next) {
                $shouldRun = true;
                $reason = 'Daily schedule due';
            }
            break;
    }
}

if (!$shouldRun) {
    error_log('scan-scheduler: Scan not due. Next scan: ' . ($next->format('Y-m-d H:i:s') ?? 'unknown'));
    echo "Scan not due.\n";
    exit(0);
}

error_log("scan-scheduler: Running $scanType scan. Reason: $reason");

// ============================================================================
// RUN THE SCAN
// ============================================================================

require_once __DIR__ . '/../admin/api/scan-engine.php';
require_once __DIR__ . '/../admin/includes/report-generator.php';

try {
    // Create engine instance (PDO available from includes/db.php)
    $engine = new SecurityScanEngine($pdo, $scanType);
    $report = $engine->run();
    
    // Save report to file
    $reportDir = realpath(__DIR__ . '/../storage') . DIRECTORY_SEPARATOR . 'scan_reports';
    if (!is_dir($reportDir)) {
        @mkdir($reportDir, 0755, true);
    }
    
    $filename = 'scan_' . date('Ymd_His') . '_' . $scanType . '.json';
    $filepath = $reportDir . DIRECTORY_SEPARATOR . $filename;
    
    file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    error_log("scan-scheduler: Report saved to $filepath");
    
    // Log action
    try {
        logAction(
            $pdo,
            0, // System user
            'security_scan_scheduled',
            [
                'scan_type' => $scanType,
                'critical' => count($report['critical'] ?? []),
                'warnings' => count($report['warnings'] ?? []),
                'file' => '/storage/scan_reports/' . $filename,
            ]
        );
    } catch (Throwable $e) {
        error_log('scan-scheduler: Failed to log action: ' . $e->getMessage());
    }
    
    // Update last_scan_at in settings
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
        $stmt->execute(['system_settings']);
        $val = $stmt->fetchColumn();
        $j = $val ? json_decode($val, true) : [];
        if (!is_array($j)) $j = [];
        if (!isset($j['security'])) $j['security'] = [];
        
        $j['security']['last_scan_at'] = $now->format('Y-m-d H:i:s');
        $j['security']['last_scan_type'] = $scanType;
        $j['security']['last_scan_critical'] = count($report['critical'] ?? []);
        $j['security']['last_scan_warnings'] = count($report['warnings'] ?? []);
        
        $upd = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
        $upd->execute([
            ':k' => 'system_settings',
            ':v' => json_encode($j, JSON_UNESCAPED_SLASHES),
            ':v2' => json_encode($j, JSON_UNESCAPED_SLASHES)
        ]);
        
        error_log('scan-scheduler: Updated last_scan_at in settings');
    } catch (Throwable $e) {
        error_log('scan-scheduler: Failed to update settings: ' . $e->getMessage());
    }
    
    // Email notification if critical issues found
    if (!empty($report['critical'])) {
        $criticalCount = count($report['critical']);
        error_log("scan-scheduler: Found $criticalCount critical issues, sending email alert");
        
        $recipients = [];
        
        // Get admin email from settings
        try {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
            $stmt->execute(['system_settings']);
            $val = $stmt->fetchColumn();
            if ($val) {
                $settings = json_decode($val, true);
                $adminEmail = $settings['contact']['email'] ?? null;
                if ($adminEmail) $recipients[] = $adminEmail;
            }
        } catch (Throwable $e) {}
        
        // Fallback to env email
        $recipients[] = $_ENV['MAIL_FROM_ADDRESS'] ?? 'admin@example.com';
        
        // Get all admin users
        try {
            $stmt = $pdo->prepare("SELECT email FROM users WHERE role_id = ? AND email IS NOT NULL");
            $stmt->execute([1]); // Admin role
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($users as $u) {
                if (!empty($u)) $recipients[] = $u;
            }
        } catch (Throwable $e) {}
        
        // Generate professional report
        $reportData = [
            'status' => 'completed',
            'report' => $report
        ];
        $generator = new ReportGenerator($reportData);
        $htmlReport = $generator->generateHtmlEmail();
        
        $subject = "🔒 [HIGH Q] Security Scan Report - " . ucfirst($scanType) . " Scan";
        
        foreach (array_unique($recipients) as $to) {
            if (empty($to)) continue;
            try {
                sendEmail($to, $subject, $htmlReport, [$filepath]);
                error_log("scan-scheduler: Report email sent to $to");
            } catch (Throwable $e) {
                error_log("scan-scheduler: Failed to send email to $to: " . $e->getMessage());
            }
        }
    }
    
    error_log('scan-scheduler: Scan completed successfully');
    echo "Scan completed successfully. Report: $filepath\n";
    
} catch (Throwable $e) {
    error_log('scan-scheduler: Scan failed: ' . $e->getMessage());
    echo "Scan failed: " . $e->getMessage() . "\n";
    exit(1);
}


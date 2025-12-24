<?php
// tmp_test_scan_engine.php - Test the SecurityScanEngine

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300);

require 'admin/includes/db.php';

// Load just the class part of scan-engine.php
$file = file_get_contents('admin/api/scan-engine.php');
$classStart = strpos($file, 'class SecurityScanEngine');
$classEnd = strpos($file, '// ============================================================================\n// API ENDPOINT');
if ($classStart !== false && $classEnd !== false) {
    $classCode = substr($file, $classStart, $classEnd - $classStart);
    eval('?>' . $classCode);
}

try {
    echo "========================================\n";
    echo "Testing SecurityScanEngine - QUICK SCAN\n";
    echo "========================================\n\n";

    $engine = new SecurityScanEngine($pdo, 'quick');
    $result = $engine->run();

    echo "Status: " . $result['status'] . "\n";
    echo "Duration: " . round($result['duration'], 2) . " seconds\n\n";

    echo "SCAN RESULTS:\n";
    echo "─────────────────────────\n";
    echo "Files Scanned: " . $result['report']['totals']['files_scanned'] . "\n";
    echo "Suspicious Files: " . $result['report']['totals']['suspicious_files'] . "\n";
    echo "Syntax Errors: " . $result['report']['totals']['syntax_errors'] . "\n";
    echo "Total Critical: " . count($result['report']['critical']) . "\n";
    echo "Total Warnings: " . count($result['report']['warnings']) . "\n";
    echo "Total Info: " . count($result['report']['info']) . "\n\n";

    if (!empty($result['report']['critical'])) {
        echo "SAMPLE CRITICAL ISSUES (first 3):\n";
        echo "─────────────────────────\n";
        foreach (array_slice($result['report']['critical'], 0, 3) as $issue) {
            echo "• " . $issue['message'] . "\n";
            echo "  File: " . basename($issue['file']) . "\n\n";
        }
    }

    if (!empty($result['report']['warnings'])) {
        echo "SAMPLE WARNINGS (first 3):\n";
        echo "─────────────────────────\n";
        foreach (array_slice($result['report']['warnings'], 0, 3) as $warning) {
            echo "• " . $warning['message'] . "\n";
            if (isset($warning['file'])) echo "  File: " . basename($warning['file']) . "\n";
            echo "\n";
        }
    }

    echo "\n✅ SCAN ENGINE TEST PASSED!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

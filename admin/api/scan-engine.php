<?php
/**
 * admin/api/scan-engine.php
 * 
 * Consolidated Security Scan Engine
 * Provides backend scanning logic for all scan types:
 * - Quick Scan: Surface-level checks (suspicious patterns, syntax errors)
 * - Full Scan: Comprehensive audit (integrity, dependencies, static analysis)
 * - Malware Scan: Focus on file hashing, integrity verification, suspicious code
 * 
 * This consolidates logic from:
 * - admin/includes/scan.php (performSecurityScan)
 * - admin/modules/sentinel.php (multi-layer approach)
 * - admin/api/run-scan.php (API endpoint structure)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ============================================================================
// SCAN ENGINE CLASS
// ============================================================================

class SecurityScanEngine {
    private $pdo;
    private $root;
    private $scanType;
    private $report;
    
    public function __construct(PDO $pdo, $scanType = 'quick') {
        $this->pdo = $pdo;
        $this->root = realpath(__DIR__ . '/../../');
        $this->scanType = $scanType; // 'quick', 'full', 'malware'
        $this->initializeReport();
    }
    
    /**
     * Initialize report structure
     */
    private function initializeReport() {
        $this->report = [
            'scan_type' => $this->scanType,
            'started_at' => date('c'),
            'finished_at' => null,
            'root' => $this->root,
            'status' => 'running',
            'totals' => [
                'files_scanned' => 0,
                'critical_issues' => 0,
                'warnings' => 0,
                'info_messages' => 0,
            ],
            'critical' => [],   // High-priority findings
            'warnings' => [],   // Medium-priority findings
            'info' => [],       // Low-priority findings
        ];
    }
    
    /**
     * Run the scan based on type
     */
    public function run() {
        try {
            switch ($this->scanType) {
                case 'quick':
                    $this->quickScan();
                    break;
                case 'full':
                    $this->fullScan();
                    break;
                case 'malware':
                    $this->malwareScan();
                    break;
                default:
                    $this->quickScan();
            }
            
            $this->report['status'] = 'completed';
        } catch (Exception $e) {
            $this->report['status'] = 'error';
            $this->report['error'] = $e->getMessage();
        }
        
        $this->report['finished_at'] = date('c');
        return $this->report;
    }
    
    /**
     * QUICK SCAN: Fast surface-level checks (2-5 minutes)
     * - Suspicious patterns in files
     * - PHP syntax errors
     * - Exposed sensitive files
     */
    private function quickScan() {
        $allowedExt = ['php', 'phtml', 'inc', 'html', 'htm', 'js'];
        $fileLimit = 1000;
        $count = 0;
        
        // Suspicious patterns that indicate malware/vulnerability
        $criticalPatterns = [
            'webshell' => '/eval\s*\(\s*base64_decode\s*\(/i',
            'exec_shell' => '/\bexec\s*\(|shell_exec\s*\(|system\s*\(/i',
            'file_inclusion' => '/\binclude\s*\(\s*\$_(GET|POST|REQUEST)\b/i',
            'assert' => '/\bassert\s*\(/i',
        ];
        
        $warningPatterns = [
            'phpinfo' => '/\bphpinfo\s*\(/i',
            'base64_decode' => '/\bbase64_decode\s*\(/i',
            'preg_replace' => '/preg_replace\s*\(.*\/[e][^\/]*\//i',
        ];
        
        // Scan files recursively
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root)
        );
        
        foreach ($iter as $file) {
            if (!$file->isFile()) continue;
            
            $count++;
            if ($count > $fileLimit) {
                $this->report['warnings'][] = [
                    'type' => 'scan_limit',
                    'message' => "Quick scan limited to {$fileLimit} files",
                ];
                break;
            }
            
            $path = $file->getPathname();
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            
            // Skip non-code files
            if (!in_array($ext, $allowedExt)) continue;
            
            // Skip vendor and node_modules for quick scan
            if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
            if (strpos($path, DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR) !== false) continue;
            
            // Skip very large files
            if ($file->getSize() > 5 * 1024 * 1024) continue;
            
            $this->report['totals']['files_scanned']++;
            $content = @file_get_contents($path);
            if ($content === false) continue;
            
            $rel = str_replace($this->root . DIRECTORY_SEPARATOR, '', $path);
            
            // Check critical patterns
            foreach ($criticalPatterns as $name => $pattern) {
                if (preg_match($pattern, $content)) {
                    $this->report['critical'][] = [
                        'type' => $name,
                        'file' => $rel,
                        'message' => "Suspicious pattern detected: {$name}",
                    ];
                    $this->report['totals']['critical_issues']++;
                    break; // Only report first match per file
                }
            }
            
            // Check warning patterns
            foreach ($warningPatterns as $name => $pattern) {
                if (preg_match($pattern, $content)) {
                    $this->report['warnings'][] = [
                        'type' => $name,
                        'file' => $rel,
                        'message' => "Potentially suspicious code: {$name}",
                    ];
                    $this->report['totals']['warnings']++;
                    break;
                }
            }
            
            // PHP syntax check
            if (in_array($ext, ['php', 'phtml', 'inc'])) {
                $escaped = escapeshellarg($path);
                @exec("php -l $escaped 2>&1", $out, $rc);
                if ($rc !== 0) {
                    $this->report['warnings'][] = [
                        'type' => 'syntax_error',
                        'file' => $rel,
                        'message' => 'PHP syntax error detected',
                    ];
                    $this->report['totals']['warnings']++;
                }
            }
        }
    }
    
    /**
     * FULL SCAN: Comprehensive security audit (10-15 minutes)
     * - Everything from Quick Scan
     * - File integrity (MD5 hashing)
     * - Composer/dependency vulnerabilities
     * - PHPStan static analysis
     * - .env security check
     * - File permission audit
     */
    private function fullScan() {
        // Start with quick scan
        $this->quickScan();
        
        // Layer B: Integrity Monitor (File Hashing)
        $this->checkFileIntegrity();
        
        // Layer C: Composer/Dependency Audit
        $this->auditComposer();
        
        // Static Analysis
        $this->runStaticAnalysis();
        
        // Config/Environment Checks
        $this->checkEnvironment();
        
        // File Permission Audit
        $this->checkFilePermissions();
    }
    
    /**
     * MALWARE SCAN: Focus on file integrity and suspicious code (5-10 minutes)
     * - File integrity baseline comparison
     * - Malware signature patterns
     * - Webshell detection
     * - File size analysis
     */
    private function malwareScan() {
        $allowedExt = ['php', 'phtml', 'inc', 'js', 'html', 'htm'];
        $fileLimit = 2000;
        $count = 0;
        
        // Webshell signatures
        $webshellPatterns = [
            'base64_shell' => '/eval\s*\(\s*base64_decode\s*\(/i',
            'simple_system_call' => '/\bsystem\s*\(\s*\$_(GET|POST|REQUEST)\b/i',
            'passthru_shell' => '/\bpassthru\s*\(\s*\$_(GET|POST|REQUEST)\b/i',
            'shell_exec' => '/\bshell_exec\s*\(\s*\$_(GET|POST|REQUEST)\b/i',
            'exec_shell' => '/\bexec\s*\(\s*\$_(GET|POST|REQUEST)\b/i',
            'create_function' => '/\bcreate_function\s*\(/i',
        ];
        
        // Build baseline file
        $baselineFile = $this->root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'scan_baseline.json';
        $baseline = $this->loadBaseline($baselineFile);
        $currentHashes = [];
        
        // Scan files
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root)
        );
        
        foreach ($iter as $file) {
            if (!$file->isFile()) continue;
            
            $count++;
            if ($count > $fileLimit) break;
            
            $path = $file->getPathname();
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExt)) continue;
            if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
            
            $this->report['totals']['files_scanned']++;
            $rel = str_replace($this->root . DIRECTORY_SEPARATOR, '', $path);
            
            // Calculate file hash
            $hash = @md5_file($path);
            if ($hash) $currentHashes[$rel] = $hash;
            
            // Check for file modifications
            if (isset($baseline[$rel]) && $baseline[$rel] !== $hash) {
                $this->report['warnings'][] = [
                    'type' => 'file_modified',
                    'file' => $rel,
                    'message' => 'File has been modified since last scan',
                ];
                $this->report['totals']['warnings']++;
            }
            
            // Webshell detection
            $content = @file_get_contents($path);
            if ($content === false) continue;
            
            foreach ($webshellPatterns as $name => $pattern) {
                if (preg_match($pattern, $content)) {
                    $this->report['critical'][] = [
                        'type' => 'malware_signature',
                        'file' => $rel,
                        'message' => "Malware signature detected: {$name}",
                    ];
                    $this->report['totals']['critical_issues']++;
                    break;
                }
            }
            
            // Suspicious file size (potentially obfuscated code)
            $size = filesize($path);
            if ($size > 1024 * 1024 && $ext === 'php') {
                $this->report['info'][] = [
                    'type' => 'large_file',
                    'file' => $rel,
                    'size' => $size,
                    'message' => 'Unusually large PHP file (potential obfuscation)',
                ];
                $this->report['totals']['info_messages']++;
            }
        }
        
        // Update baseline for next scan
        if (!empty($currentHashes)) {
            @file_put_contents(
                $baselineFile,
                json_encode($currentHashes, JSON_PRETTY_PRINT)
            );
        }
    }
    
    /**
     * Check file integrity against baseline
     */
    private function checkFileIntegrity() {
        $baselineFile = $this->root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'scan_baseline.json';
        $baseline = $this->loadBaseline($baselineFile);
        $currentHashes = [];
        
        $allowedExt = ['php', 'phtml', 'inc', 'html', 'css', 'js'];
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root)
        );
        
        $changedCount = 0;
        foreach ($iter as $file) {
            if (!$file->isFile()) continue;
            
            $path = $file->getPathname();
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExt)) continue;
            if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
            if (strpos($path, DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR) !== false) continue;
            
            $rel = str_replace($this->root . DIRECTORY_SEPARATOR, '', $path);
            $hash = @md5_file($path);
            if (!$hash) continue;
            
            $currentHashes[$rel] = $hash;
            
            // Detect changes
            if (isset($baseline[$rel]) && $baseline[$rel] !== $hash) {
                $changedCount++;
                if ($changedCount <= 10) { // Report first 10 changes
                    $this->report['warnings'][] = [
                        'type' => 'integrity_change',
                        'file' => $rel,
                        'message' => 'File integrity changed',
                    ];
                    $this->report['totals']['warnings']++;
                }
            }
        }
        
        if ($changedCount > 10) {
            $this->report['info'][] = [
                'type' => 'integrity_summary',
                'message' => "Total files changed: {$changedCount} (showing first 10)",
                'count' => $changedCount,
            ];
        }
        
        // Update baseline
        if (!empty($currentHashes)) {
            @file_put_contents(
                $baselineFile,
                json_encode($currentHashes, JSON_PRETTY_PRINT)
            );
        }
    }
    
    /**
     * Audit composer dependencies for vulnerabilities
     */
    private function auditComposer() {
        $composerOut = null;
        @exec('composer audit --format=json 2>&1', $composerOut, $rc);
        
        if (!empty($composerOut)) {
            $joined = implode("\n", $composerOut);
            $json = json_decode($joined, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($json['advisories'])) {
                foreach ($json['advisories'] as $advisory) {
                    $this->report['critical'][] = [
                        'type' => 'composer_vulnerability',
                        'package' => $advisory['package'] ?? 'unknown',
                        'message' => $advisory['title'] ?? 'Vulnerability detected',
                        'url' => $advisory['link'] ?? null,
                    ];
                    $this->report['totals']['critical_issues']++;
                }
            }
        }
    }
    
    /**
     * Run PHPStan static analysis
     */
    private function runStaticAnalysis() {
        $phpstanPaths = [
            $this->root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan',
            $this->root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan.bat',
        ];
        
        $phpstanFound = null;
        foreach ($phpstanPaths as $p) {
            if (file_exists($p)) { $phpstanFound = $p; break; }
        }
        
        if ($phpstanFound) {
            $targets = [];
            if (is_dir($this->root . DIRECTORY_SEPARATOR . 'src')) {
                $targets[] = escapeshellarg($this->root . DIRECTORY_SEPARATOR . 'src');
            }
            if (is_dir($this->root . DIRECTORY_SEPARATOR . 'admin')) {
                $targets[] = escapeshellarg($this->root . DIRECTORY_SEPARATOR . 'admin');
            }
            
            if (!empty($targets)) {
                $cmd = escapeshellarg($phpstanFound) . ' analyse ' . implode(' ', $targets) . ' --error-format=json';
                @exec($cmd . ' 2>&1', $psOut, $psRc);
                
                if ($psRc === 0) {
                    $this->report['info'][] = [
                        'type' => 'phpstan',
                        'message' => 'PHPStan analysis: No issues detected',
                    ];
                }
            }
        }
    }
    
    /**
     * Check environment and configuration
     */
    private function checkEnvironment() {
        $envFile = $this->root . DIRECTORY_SEPARATOR . '.env';
        if (is_readable($envFile)) {
            $content = file_get_contents($envFile);
            
            if (stripos($content, 'APP_DEBUG=true') !== false) {
                $this->report['warnings'][] = [
                    'type' => 'debug_enabled',
                    'message' => 'APP_DEBUG=true detected in .env (should be false in production)',
                ];
                $this->report['totals']['warnings']++;
            }
            
            if (stripos($content, 'APP_ENV=development') !== false) {
                $this->report['info'][] = [
                    'type' => 'dev_environment',
                    'message' => 'APP_ENV=development detected (consider production)',
                ];
                $this->report['totals']['info_messages']++;
            }
        }
    }
    
    /**
     * Check file and directory permissions
     */
    private function checkFilePermissions() {
        $criticalFiles = [
            '.env',
            'config' . DIRECTORY_SEPARATOR . 'db.php',
            'admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db.php',
        ];
        
        foreach ($criticalFiles as $relPath) {
            $fullPath = $this->root . DIRECTORY_SEPARATOR . $relPath;
            if (is_file($fullPath)) {
                $perms = fileperms($fullPath);
                $octal = substr(sprintf('%o', $perms), -4);
                
                // Warn if world-readable
                if ($perms & 0x0004) {
                    $this->report['warnings'][] = [
                        'type' => 'file_permission',
                        'file' => $relPath,
                        'permissions' => $octal,
                        'message' => 'Sensitive file is world-readable',
                    ];
                    $this->report['totals']['warnings']++;
                }
            }
        }
    }
    
    /**
     * Load baseline from file
     */
    private function loadBaseline($file) {
        if (!file_exists($file)) return [];
        
        $content = @file_get_contents($file);
        if (!$content) return [];
        
        $baseline = json_decode($content, true);
        return is_array($baseline) ? $baseline : [];
    }
    
    /**
     * Get report
     */
    public function getReport() {
        return $this->report;
    }
}

// ============================================================================
// API ENDPOINT
// ============================================================================

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Require admin permission
try {
    requirePermission('sentinel');
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
    exit;
}

$scanType = $_POST['scan_type'] ?? 'quick';
if (!in_array($scanType, ['quick', 'full', 'malware'])) {
    $scanType = 'quick';
}

try {
    $engine = new SecurityScanEngine($pdo, $scanType);
    $report = $engine->run();
    
    // Log the scan
    try {
        logAction(
            $pdo,
            $_SESSION['user']['id'] ?? 0,
            'security_scan_completed',
            [
                'scan_type' => $scanType,
                'critical' => count($report['critical']),
                'warnings' => count($report['warnings']),
                'info' => count($report['info']),
            ]
        );
    } catch (Exception $e) {
        error_log('Failed to log scan action: ' . $e->getMessage());
    }
    
    echo json_encode([
        'status' => 'ok',
        'report' => $report,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}

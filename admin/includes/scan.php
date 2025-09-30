<?php
// admin/includes/scan.php
/**
 * performSecurityScan moved to shared include so CLI and web can call it.
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

    $allowedExt = ['php','phtml','inc','html','htm','js','css','env','sql','lock'];
    $limitFiles = 5000; // safety cap
    $count = 0;
    // allow override from provided settings
    $includeVendor = $currentSettings['include_vendor'] ?? false;

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

    // skip vendor to reduce noise unless explicitly requested
    if (!$includeVendor && strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;

        // Skip very large files
        if ($file->getSize() > 2 * 1024 * 1024) continue;

        $content = @file_get_contents($path);
        if ($content === false) continue;

        // PHP syntax check
        if (in_array($ext, ['php','phtml','inc'])) {
            $escaped = escapeshellarg($path);
            $out = null; $rc = null;
            @exec("php -l $escaped 2>&1", $out, $rc);
            $outStr = is_array($out) ? implode("\n", $out) : trim((string)$out);
            if ($rc !== 0) {
                $report['totals']['php_syntax_errors']++;
                $report['errors'][] = ['file' => $path, 'error' => $outStr];
            }
        }

        // Suspicious patterns (expanded with OWASP-like checks)
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
            '/\bphpinfo\s*\(/i',
            '/\bfile_get_contents\s*\(\s*\$_(GET|POST|REQUEST)\b/i',
            '/\binclude\s*\(\s*\$_(GET|POST|REQUEST)\b/i',
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

    // Additional config checks
    // Check .env for debug settings
    $envFile = $root . DIRECTORY_SEPARATOR . '.env';
    if (is_readable($envFile)) {
        $envContent = file_get_contents($envFile);
        if (stripos($envContent, 'APP_DEBUG=true') !== false) {
            $report['errors'][] = 'APP_DEBUG=true in .env (should be false in production)';
        }
        if (stripos($envContent, 'APP_ENV=development') !== false) {
            $report['errors'][] = 'APP_ENV=development in .env (consider production)';
        }
    }

    // Attempt composer audit if composer available; parse JSON output when present
    $composerOut = null; $composerRc = null;
    @exec('composer audit --format=json 2>&1', $composerOut, $composerRc);
    if (!empty($composerOut)) {
        $joined = implode("\n", $composerOut);
        // try to parse JSON
        $json = json_decode($joined, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json['advisories'])) {
            $report['composer_audit_parsed'] = $json;
            $report['totals']['composer_vulns'] = count($json['advisories']);
        } else {
            // store raw output and return code for debugging
            $report['composer_audit'] = $joined;
            $report['composer_audit_rc'] = $composerRc;
            // best-effort count of the word 'advisories'
            $report['totals']['composer_vulns'] = substr_count($joined, 'advisories');
        }
    } else {
        $report['composer_audit_rc'] = $composerRc;
    }

    // Run PHPStan if installed (vendor/bin/phpstan or vendor/bin/phpstan.bat on Windows)
    $phpstanPaths = [
        $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan',
        $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan.bat',
    ];
    $phpstanFound = null;
    foreach ($phpstanPaths as $p) {
        if (is_executable($p) || file_exists($p)) { $phpstanFound = $p; break; }
    }
    if ($phpstanFound) {
        $psOut = null; $psRc = null;
        // run basic analysis on src and public folders if they exist
        $targets = [];
        if (is_dir($root . DIRECTORY_SEPARATOR . 'src')) $targets[] = escapeshellarg($root . DIRECTORY_SEPARATOR . 'src');
        if (is_dir($root . DIRECTORY_SEPARATOR . 'public')) $targets[] = escapeshellarg($root . DIRECTORY_SEPARATOR . 'public');
        if (!empty($targets)) {
            $cmd = escapeshellarg($phpstanFound) . ' analyse ' . implode(' ', $targets) . ' --error-format=json';
            @exec($cmd . ' 2>&1', $psOut, $psRc);
            $joined = is_array($psOut) ? implode("\n", $psOut) : (string)$psOut;
            $json = json_decode($joined, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $report['phpstan'] = $json;
            } else {
                $report['phpstan_raw'] = $joined;
                $report['phpstan_rc'] = $psRc;
            }
        }
    } else {
        $report['phpstan_available'] = false;
    }

    $report['finished_at'] = date('c');
    $report['summary'] = sprintf("Scanned %d files. Syntax errors: %d. Suspicious files: %d. Writable files: %d. Exposed files: %d",
        $report['totals']['files_scanned'], $report['totals']['php_syntax_errors'], $report['totals']['suspicious_patterns'], $report['totals']['writable_files'], $report['totals']['exposed_files']);

    return $report;
}

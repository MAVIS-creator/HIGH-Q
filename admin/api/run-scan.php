<?php
// admin/api/run-scan.php
// JSON-only endpoint to queue the background security scan.
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Only accept POST
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

// Require settings permission
try { requirePermission('settings'); } catch (Exception $e) { /* fall through */ }

$php = PHP_BINARY;
$root = realpath(__DIR__ . '/../../');
$runner = $root . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scan-runner.php';

if (!is_file($runner) || !is_readable($runner)) {
    error_log('run-scan: runner not found at ' . $runner);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Scan runner not available on server']);
    exit;
}

try {
    if (strtoupper(substr(PHP_OS,0,3)) === 'WIN') {
        $cmd = 'start /B ' . escapeshellarg($php) . ' ' . escapeshellarg($runner);
        $proc = @popen($cmd, 'r');
        if ($proc !== false) { pclose($proc); }
        else throw new Exception('Failed to spawn background process on Windows');
    } else {
        $cmd = "nohup " . escapeshellarg($php) . ' ' . escapeshellarg($runner) . " > /dev/null 2>&1 &";
        @exec($cmd, $out, $rc);
        if ($rc !== 0) throw new Exception('Non-zero exit when launching runner: ' . intval($rc));
    }
} catch (Exception $e) {
    error_log('run-scan: failed to queue runner: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to queue security scan: ' . $e->getMessage()]);
    exit;
}

try { logAction($pdo, $_SESSION['user']['id'] ?? 0, 'security_scan_queued', ['by' => $_SESSION['user']['email'] ?? null]); } catch (Exception $e) { error_log('run-scan logAction failed: ' . $e->getMessage()); }

echo json_encode(['status' => 'ok', 'message' => 'Security scan queued; you will receive an email when it completes.']);
exit;

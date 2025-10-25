<?php
// admin/api/export_registration.php
$logFile = __DIR__ . '/../../storage/logs/export_errors.log';
// Basic error/shutdown handlers to capture fatal errors when run under Apache
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile){
    $msg = "[".date('c')."] PHP Error: $errstr in $errfile:$errline (errno=$errno)\n";
    @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
});
register_shutdown_function(function() use ($logFile){
    $err = error_get_last();
    if ($err) {
        $msg = "[".date('c')."] Shutdown: " . ($err['message'] ?? '') . " in " . ($err['file'] ?? '') . ":" . ($err['line'] ?? '') . "\n";
        @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    }
});

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

// Quick runtime checks
if (!class_exists('ZipArchive')) {
    $m = 'ZipArchive extension is not available on this PHP installation. Please enable the zip extension.';
    @file_put_contents($logFile, "[".date('c')."] Export error: $m\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['success'=>false,'error'=>$m]);
    exit;
}
$regId = intval($_GET['id'] ?? 0);
$type = trim($_GET['type'] ?? 'registration'); // 'registration' (student_registrations) or 'post'
if (!$regId) { echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
try {
    if ($type === 'post') {
        $stmt = $pdo->prepare('SELECT * FROM post_utme_registrations WHERE id = ? LIMIT 1');
        $stmt->execute([$regId]); $r = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare('SELECT sr.*, GROUP_CONCAT(sp.course_id) AS courses FROM student_registrations sr LEFT JOIN student_programs sp ON sp.registration_id = sr.id WHERE sr.id = ? GROUP BY sr.id');
        $stmt->execute([$regId]); $r = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$r) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }
    $tmp = sys_get_temp_dir() . '/hq_export_' . uniqid();
    @mkdir($tmp);
    // create a simple HTML copy
    $html = '<html><body>';
    $html .= '<h1>Registration #' . $r['id'] . '</h1>';
    foreach ($r as $k=>$v) { $val = is_null($v) ? '' : (string)$v; $html .= '<p><strong>' . htmlspecialchars((string)$k) . ':</strong> ' . htmlspecialchars($val) . '</p>'; }
    $html .= '</body></html>';
    file_put_contents($tmp . '/registration.html', $html);
    // copy passport if exists. passport_path may be an absolute URL or a local path.
    if (!empty($r['passport_path'])) {
        $pp = $r['passport_path'];
        $ext = '';
        if (preg_match('#^https?://#i', $pp)) {
            // download remote file
            $tmpFile = $tmp . '/passport_download';
            $ch = curl_init($pp);
            $fp = fopen($tmpFile, 'w');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);
            if ($httpCode >= 200 && $httpCode < 300 && filesize($tmpFile) > 0) {
                // try to detect extension from MIME
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpFile);
                finfo_close($finfo);
                $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/jpg'=>'jpg'];
                $ext = isset($map[$mime]) ? $map[$mime] : pathinfo(parse_url($pp, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (!$ext) $ext = 'jpg';
                rename($tmpFile, $tmp . '/passport.' . $ext);
            } else {
                @unlink($tmpFile);
            }
        } else {
            // treat as local path (may be absolute or site-relative)
            $candidate = $pp;
            // if starts with /HIGH-Q, try realpath relative to project root
            if (strpos($candidate, '/HIGH-Q') === 0) {
                $candidate = __DIR__ . '/../../' . ltrim($candidate, '/');
            }
            if (file_exists($candidate)) {
                $ext = pathinfo($candidate, PATHINFO_EXTENSION);
                copy($candidate, $tmp . '/passport.' . $ext);
            }
        }
    }
    $zipPath = $tmp . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE)!==TRUE) { echo json_encode(['success'=>false,'error'=>'Cannot create zip']); exit; }
    $zip->addFile($tmp . '/registration.html','registration.html');
    if (file_exists($tmp . '/passport.jpg')) $zip->addFile($tmp . '/passport.jpg','passport.jpg');
    if (file_exists($tmp . '/passport.png')) $zip->addFile($tmp . '/passport.png','passport.png');
    $zip->close();
    // serve zip
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="registration_' . $r['id'] . '.zip"');
    readfile($zipPath);
    // cleanup
    @unlink($zipPath);
    array_map('unlink', glob($tmp . '/*'));
    @rmdir($tmp);
    exit;
} catch (Throwable $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); exit; }

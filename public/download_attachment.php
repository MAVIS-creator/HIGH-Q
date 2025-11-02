<?php
// public/download_attachment.php - secure download for chat attachments (serves files from uploads/chat)
$file = $_GET['file'] ?? '';
if (!$file) {
    $err = __DIR__ . '/errors/400.php';
    if (file_exists($err)) { include $err; } else { http_response_code(400); echo 'Missing file'; }
    exit;
}

$baseDir = __DIR__ . '/uploads/chat/';
$path = realpath($baseDir . $file);
// Prevent path traversal
if ($path === false || strpos($path, realpath($baseDir)) !== 0 || !is_file($path)) {
    $err = __DIR__ . '/errors/404.php';
    if (file_exists($err)) { include $err; } else { http_response_code(404); echo 'Not found'; }
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $path) ?: 'application/octet-stream';
finfo_close($finfo);

// Force download for non-images; images can be displayed inline but we still set headers safely
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=10800');

$downloadName = basename($file);
// If we can look up original_name from DB, prefer it (best-effort)
try {
    if (file_exists(__DIR__ . '/../public/config/db.php')) {
        require_once __DIR__ . '/../public/config/db.php';
        $stmt = $pdo->prepare('SELECT original_name FROM chat_attachments WHERE file_url LIKE ? LIMIT 1');
        $stmt->execute(['%'.$file]);
        $orig = $stmt->fetchColumn();
        if ($orig) $downloadName = $orig;
    }
} catch (Throwable $_) { /* ignore DB errors */ }

header('Content-Disposition: attachment; filename="' . str_replace('\"','', basename($downloadName)) . '"');
readfile($path);
exit;

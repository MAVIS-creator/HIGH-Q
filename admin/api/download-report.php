<?php
/**
 * admin/api/download-report.php
 * 
 * Secure PDF report download endpoint
 * Validates user permission before serving file
 */

require_once __DIR__ . '/../includes/auth.php';
requirePermission('sentinel');

$filename = $_GET['file'] ?? '';

// Security: Only allow .pdf files from reports directory
if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-]+\.pdf$/', $filename)) {
    http_response_code(400);
    die('Invalid file requested');
}

$filepath = __DIR__ . '/../../storage/reports/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die('Report not found');
}

// Serve the PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filepath);
exit;

<?php
// Unified receipt (HTML + optional PDF)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/welcome-kit-generator.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$ref = $_GET['ref'] ?? '';
if ($ref === '') { http_response_code(400); echo 'Missing reference'; exit; }

// Fetch payment and site name/logo
$stmt = $pdo->prepare('SELECT p.*, s.site_name FROM payments p LEFT JOIN site_settings s ON s.id = 1 WHERE p.reference = ? LIMIT 1');
$stmt->execute([$ref]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { http_response_code(404); echo 'Receipt not found'; exit; }

$siteName = $p['site_name'] ?? 'HIGH Q SOLID ACADEMY';

// Build base URL for assets (logo/css) that respects subfolder installs
if (function_exists('app_url')) {
    $BASE = rtrim(app_url(''), '/');
} else {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
    $BASE = rtrim($scheme . '://' . $host, '/') . ($scriptDir ? $scriptDir : '');
}
$logoUrl = app_url('assets/images/hq-logo.jpeg');
$cssUrl  = app_url('assets/css/public.css');

// Shared HTML (also used by PDF)
$html = '<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt - ' . htmlspecialchars($siteName) . '</title>
  <link rel="stylesheet" href="' . htmlspecialchars($cssUrl) . '">
  <style>
    body{background:#f7f7f7}
    .receipt-card{max-width:800px;margin:24px auto;padding:24px;border:1px solid #eee;border-radius:8px;background:#fff}
    .receipt-header{display:flex;align-items:center;gap:12px}
    .receipt-meta{margin-top:12px;color:#555}
    .actions{margin-top:18px}
    .amount{font-size:22px;font-weight:700;color:#0a8a3a}
    .muted{color:#777}
  </style>
</head>
<body>
  <div class="receipt-card">
    <div class="receipt-header">
      <img src="' . htmlspecialchars($logoUrl) . '" alt="logo" style="height:60px;">
      <div>
        <h2 style="margin:0">Payment Receipt</h2>
        <div class="muted">' . htmlspecialchars($siteName) . '</div>
      </div>
      <div style="margin-left:auto;text-align:right">
        <div class="amount">₦' . number_format((float)$p['amount'], 2) . '</div>
        <div class="muted">Status: ' . htmlspecialchars($p['status']) . '</div>
      </div>
    </div>

    <hr>
    <div>
      <strong>Reference:</strong> ' . htmlspecialchars($p['reference']) . '<br>
      <strong>Name:</strong> ' . htmlspecialchars($p['payer_account_name'] ?? $p['payer_name'] ?? '') . '<br>
      <strong>Email:</strong> ' . htmlspecialchars($p['payer_email'] ?? $p['email'] ?? '') . '<br>
      <strong>Account:</strong> ' . htmlspecialchars($p['payer_account_number'] ?? '') . ' ' . htmlspecialchars($p['payer_bank_name'] ?? '') . '<br>
      <div class="receipt-meta">Payment recorded at: ' . htmlspecialchars($p['created_at'] ?? '') . '</div>
    </div>

    <div class="actions">
      <a href="?ref=' . urlencode($ref) . '&format=pdf" class="btn-primary">Download PDF</a>
      <button onclick="window.print();" class="btn" style="margin-left:8px;">Print</button>
      <a href="' . htmlspecialchars($BASE . '/index.php') . '" class="btn" style="margin-left:8px;">Return to site</a>
    </div>
  </div>
</body>
</html>';

// If PDF requested, render with Dompdf
if (isset($_GET['format']) && strtolower($_GET['format']) === 'pdf') {
    // Ensure autoloaders are available (functions.php already loads vendor)
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $filename = 'receipt-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $ref) . '.pdf';
    
    // TRIGGER: Generate welcome kit and send email
    try {
        // Get student registration details from payments table
        $stmt = $pdo->prepare('SELECT program_type FROM payments WHERE reference = ? LIMIT 1');
        $stmt->execute([$ref]);
        $paymentData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paymentData && !empty($paymentData['program_type'])) {
            $studentEmail = $p['payer_email'] ?? $p['email'] ?? '';
            $studentName = $p['payer_account_name'] ?? $p['payer_name'] ?? 'Student';
            $programType = $paymentData['program_type'];
            $registrationId = $p['id'] ?? $ref;
            
            if (!empty($studentEmail)) {
                // Generate welcome kit PDF
                $kitResult = generateWelcomeKitPDF($programType, $studentName, $studentEmail, $registrationId);
                
                if ($kitResult['success']) {
                    // Send welcome kit email
                    sendWelcomeKitEmail($studentEmail, $studentName, $programType, $registrationId, $kitResult['filepath']);
                    
                    // Log the action
                    @file_put_contents(
                        __DIR__ . '/../storage/logs/welcome-kit-sent.log', 
                        date('Y-m-d H:i:s') . " | Payment: {$ref} | Email: {$studentEmail} | Program: {$programType}\n",
                        FILE_APPEND | LOCK_EX
                    );
                }
            }
        }
    } catch (Exception $e) {
        // Log error but don't break receipt download
        @file_put_contents(
            __DIR__ . '/../storage/logs/welcome-kit-error.log',
            date('Y-m-d H:i:s') . " | Payment: {$ref} | Error: " . $e->getMessage() . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
    
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

// Otherwise, output HTML
echo $html;
?>

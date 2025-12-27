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
    
    <!-- WhatsApp Channel CTA -->
    <div class="whatsapp-cta" style="margin-top: 24px; padding: 20px; background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); border-radius: 12px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);">
      <div class="whatsapp-icon" style="flex-shrink: 0; width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
      </div>
      <div class="whatsapp-text" style="flex: 1; color: white;">
        <h3 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 600;">Stay Connected!</h3>
        <p style="margin: 0; font-size: 14px; opacity: 0.95;">Get the latest updates, exam tips, and exclusive content — join our WhatsApp channel.</p>
      </div>
      <a href="https://whatsapp.com/channel/0029Va59zqrHFxP5GBRvBE1l" target="_blank" rel="noopener" class="whatsapp-btn" style="flex-shrink: 0; padding: 12px 24px; background: white; color: #128c7e; font-weight: 600; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
        Join Channel
      </a>
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

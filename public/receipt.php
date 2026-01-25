<?php
// Unified receipt (HTML + optional PDF) - Enhanced with more details and color
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/welcome-kit-generator.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$ref = $_GET['ref'] ?? '';
if ($ref === '') { http_response_code(400); echo 'Missing reference'; exit; }

// Fetch payment and site settings
$stmt = $pdo->prepare('SELECT p.*, s.site_name, s.contact_email, s.contact_phone, s.contact_address, s.bank_name, s.bank_account_number, s.bank_account_name FROM payments p LEFT JOIN site_settings s ON s.id = 1 WHERE p.reference = ? LIMIT 1');
$stmt->execute([$ref]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { http_response_code(404); echo 'Receipt not found'; exit; }

// Try to get registration details
$registration = null;
$programDetails = null;

// Check universal_registrations first
try {
    $regStmt = $pdo->prepare('SELECT * FROM universal_registrations WHERE payment_reference = ? LIMIT 1');
    $regStmt->execute([$ref]);
    $registration = $regStmt->fetch(PDO::FETCH_ASSOC);
    if ($registration && !empty($registration['payload'])) {
        $programDetails = json_decode($registration['payload'], true);
    }
} catch (Throwable $e) {}

// Fallback to metadata from payment
if (!$registration && !empty($p['metadata'])) {
    $programDetails = json_decode($p['metadata'], true);
}

$siteName = $p['site_name'] ?? 'HIGH Q SOLID ACADEMY';
$contactEmail = $p['contact_email'] ?? 'info@hqacademy.com';
$contactPhone = $p['contact_phone'] ?? '0807 208 8794';
$contactAddress = $p['contact_address'] ?? '8 Pineapple Avenue, Aiyetoro, Maya';

// Extract student info
$studentName = $p['payer_account_name'] ?? $p['payer_name'] ?? $programDetails['name'] ?? ($registration['first_name'] ?? '') . ' ' . ($registration['last_name'] ?? '');
$studentEmail = $p['payer_email'] ?? $p['email'] ?? $programDetails['email'] ?? $registration['email'] ?? '';
$studentPhone = $programDetails['phone'] ?? $registration['phone'] ?? '';
$programType = $p['registration_type'] ?? $p['program_type'] ?? $registration['program_type'] ?? $programDetails['program_type'] ?? 'Registration';

// Format program type for display
$programTypeLabels = [
    'jamb' => 'JAMB/UTME Preparation',
    'waec' => 'WAEC/NECO/GCE Preparation',
    'postutme' => 'Post-UTME Preparation',
    'digital' => 'Digital Skills Training',
    'international' => 'International Programs (SAT/IELTS/TOEFL)',
];
$programLabel = $programTypeLabels[strtolower($programType)] ?? ucfirst($programType);

// Status badge colors
$statusColors = [
    'confirmed' => ['bg' => '#dcfce7', 'text' => '#166534'],
    'paid' => ['bg' => '#dcfce7', 'text' => '#166534'],
    'pending' => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'expired' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
];
$statusColor = $statusColors[strtolower($p['status'] ?? 'pending')] ?? $statusColors['pending'];

// Build base URL for assets
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

// Enhanced HTML with colors (also used by PDF)
$html = '<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt - ' . htmlspecialchars($siteName) . '</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      min-height: 100vh;
      padding: 20px;
    }
    .receipt-container {
      max-width: 800px;
      margin: 0 auto;
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,0.12);
    }
    .receipt-header {
      background: linear-gradient(135deg, #0b1a2c 0%, #1e3a5f 100%);
      color: white;
      padding: 30px;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .receipt-header img {
      width: 80px;
      height: 80px;
      border-radius: 12px;
      border: 3px solid #ffd600;
      object-fit: contain;
      background: white;
    }
    .header-info h1 {
      font-size: 1.5rem;
      margin-bottom: 4px;
      color: #ffd600;
    }
    .header-info p {
      font-size: 0.9rem;
      opacity: 0.9;
    }
    .amount-badge {
      margin-left: auto;
      text-align: right;
    }
    .amount-badge .amount {
      font-size: 2rem;
      font-weight: 800;
      color: #22c55e;
    }
    .amount-badge .status {
      display: inline-block;
      padding: 6px 16px;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      margin-top: 8px;
      background: ' . $statusColor['bg'] . ';
      color: ' . $statusColor['text'] . ';
    }
    .receipt-body {
      padding: 30px;
    }
    .info-section {
      background: #f8fafc;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .section-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .section-title::before {
      content: "";
      width: 4px;
      height: 20px;
      background: linear-gradient(135deg, #ffd600 0%, #f59e0b 100%);
      border-radius: 2px;
    }
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }
    .info-item {
      display: flex;
      flex-direction: column;
    }
    .info-item.full-width {
      grid-column: 1 / -1;
    }
    .info-label {
      font-size: 0.75rem;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 4px;
    }
    .info-value {
      font-size: 0.95rem;
      color: #1e293b;
      font-weight: 600;
    }
    .reference-box {
      background: linear-gradient(135deg, #ffd600 0%, #f59e0b 100%);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      margin-bottom: 20px;
    }
    .reference-box .label {
      font-size: 0.85rem;
      color: #0b1a2c;
      font-weight: 600;
      margin-bottom: 8px;
    }
    .reference-box .ref-number {
      font-size: 1.5rem;
      font-weight: 800;
      color: #0b1a2c;
      font-family: monospace;
      letter-spacing: 2px;
    }
    .footer-section {
      border-top: 1px solid #e2e8f0;
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 15px;
    }
    .contact-info {
      font-size: 0.85rem;
      color: #64748b;
      line-height: 1.6;
    }
    .contact-info strong {
      color: #0b1a2c;
    }
    .print-date {
      font-size: 0.8rem;
      color: #94a3b8;
    }
    .actions {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-top: 20px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      cursor: pointer;
      border: none;
      transition: all 0.3s ease;
    }
    .btn-primary {
      background: linear-gradient(135deg, #0b1a2c 0%, #1e3a5f 100%);
      color: #ffd600;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(11, 26, 44, 0.3);
    }
    .btn-secondary {
      background: #f1f5f9;
      color: #475569;
    }
    .btn-secondary:hover {
      background: #e2e8f0;
    }
    @media print {
      body { background: white; padding: 0; }
      .receipt-container { box-shadow: none; border-radius: 0; }
      .actions { display: none; }
    }
    @media (max-width: 600px) {
      .receipt-header { flex-direction: column; text-align: center; }
      .amount-badge { margin-left: 0; margin-top: 15px; }
      .info-grid { grid-template-columns: 1fr; }
      .footer-section { flex-direction: column; text-align: center; }
    }
  </style>
</head>
<body>
  <div class="receipt-container">
    <div class="receipt-header">
      <img src="' . htmlspecialchars($logoUrl) . '" alt="Logo">
      <div class="header-info">
        <h1>Payment Receipt</h1>
        <p>' . htmlspecialchars($siteName) . '</p>
      </div>
      <div class="amount-badge">
        <div class="amount">₦' . number_format((float)$p['amount'], 2) . '</div>
        <div class="status">' . htmlspecialchars(ucfirst($p['status'] ?? 'Pending')) . '</div>
      </div>
    </div>

    <div class="receipt-body">
      <div class="reference-box">
        <div class="label">Payment Reference</div>
        <div class="ref-number">' . htmlspecialchars($p['reference']) . '</div>
      </div>

      <div class="info-section">
        <div class="section-title">Student Information</div>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Full Name</span>
            <span class="info-value">' . htmlspecialchars($studentName ?: 'N/A') . '</span>
          </div>
          <div class="info-item">
            <span class="info-label">Email Address</span>
            <span class="info-value">' . htmlspecialchars($studentEmail ?: 'N/A') . '</span>
          </div>
          <div class="info-item">
            <span class="info-label">Phone Number</span>
            <span class="info-value">' . htmlspecialchars($studentPhone ?: 'N/A') . '</span>
          </div>
          <div class="info-item">
            <span class="info-label">Program</span>
            <span class="info-value">' . htmlspecialchars($programLabel) . '</span>
          </div>
        </div>
      </div>

      <div class="info-section">
        <div class="section-title">Payment Details</div>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Amount Paid</span>
            <span class="info-value">₦' . number_format((float)$p['amount'], 2) . '</span>
          </div>
          <div class="info-item">
            <span class="info-label">Payment Method</span>
            <span class="info-value">' . htmlspecialchars(ucfirst($p['payment_method'] ?? 'Bank Transfer')) . '</span>
          </div>
          <div class="info-item">
            <span class="info-label">Payment Date</span>
            <span class="info-value">' . htmlspecialchars(isset($p['created_at']) ? date('F j, Y g:i A', strtotime($p['created_at'])) : 'N/A') . '</span>
          </div>
          <div class="info-item">
            <span class="info-label">Status</span>
            <span class="info-value" style="color: ' . $statusColor['text'] . ';">' . htmlspecialchars(ucfirst($p['status'] ?? 'Pending')) . '</span>
          </div>
          ' . (!empty($p['payer_account_number']) ? '
          <div class="info-item">
            <span class="info-label">Payer Account</span>
            <span class="info-value">' . htmlspecialchars($p['payer_account_number']) . '</span>
          </div>
          <div class="info-item">
            <span class="info-label">Bank Name</span>
            <span class="info-value">' . htmlspecialchars($p['payer_bank_name'] ?? 'N/A') . '</span>
          </div>' : '') . '
        </div>
      </div>

      <div class="info-section">
        <div class="section-title">Receiving Account</div>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Bank Name</span>
            <span class="info-value">' . htmlspecialchars($p['bank_name'] ?? 'N/A') . '</span>
          </div>
          <div class="info-item">
            <span class="info-label">Account Number</span>
            <span class="info-value">' . htmlspecialchars($p['bank_account_number'] ?? 'N/A') . '</span>
          </div>
          <div class="info-item full-width">
            <span class="info-label">Account Name</span>
            <span class="info-value">' . htmlspecialchars($p['bank_account_name'] ?? $siteName) . '</span>
          </div>
        </div>
      </div>

      <div class="actions">
        <a href="?ref=' . urlencode($ref) . '&format=pdf" class="btn btn-primary">
          <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 16l-4-4h3V4h2v8h3l-4 4zm-7 4v-2h14v2H5z"/></svg>
          Download PDF
        </a>
        <button onclick="window.print();" class="btn btn-secondary">
          <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.1 0-2 .9-2 2v6h4v4h10v-4h4v-6c0-1.1-.9-2-2-2zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
          Print
        </button>
        <a href="' . htmlspecialchars($BASE . '/index.php') . '" class="btn btn-secondary">
          <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
          Home
        </a>
      </div>
    </div>

    <div class="footer-section">
      <div class="contact-info">
        <strong>' . htmlspecialchars($siteName) . '</strong><br>
        ' . htmlspecialchars($contactAddress) . '<br>
        📞 ' . htmlspecialchars($contactPhone) . ' | ✉️ ' . htmlspecialchars($contactEmail) . '
      </div>
      <div class="print-date">
        Generated: ' . date('F j, Y g:i A') . '
      </div>
    </div>
  </div>
</body>
</html>';

// If PDF requested, render with Dompdf
if (isset($_GET['format']) && strtolower($_GET['format']) === 'pdf') {
    // Ensure autoloaders are available (functions.php already loads vendor)
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
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
            $studentEmailForKit = $p['payer_email'] ?? $p['email'] ?? '';
            $studentNameForKit = $p['payer_account_name'] ?? $p['payer_name'] ?? 'Student';
            $programTypeForKit = $paymentData['program_type'];
            $registrationId = $p['id'] ?? $ref;
            
            if (!empty($studentEmailForKit)) {
                // Generate welcome kit PDF
                $kitResult = generateWelcomeKitPDF($programTypeForKit, $studentNameForKit, $studentEmailForKit, $registrationId);
                
                if ($kitResult['success']) {
                    // Send welcome kit email
                    sendWelcomeKitEmail($studentEmailForKit, $studentNameForKit, $programTypeForKit, $registrationId, $kitResult['filepath']);
                    
                    // Log the action
                    @file_put_contents(
                        __DIR__ . '/../storage/logs/welcome-kit-sent.log', 
                        date('Y-m-d H:i:s') . " | Payment: {$ref} | Email: {$studentEmailForKit} | Program: {$programTypeForKit}\n",
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

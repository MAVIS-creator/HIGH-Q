<?php
// Generate Admission Letter (HTML or PDF)
// Uses the company letterhead PDF template from uploads/Admission Letter.pdf
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

use setasign\Fpdi\Fpdi;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Resolve registration id from query or session
$rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
if ($rid <= 0 && !empty($_SESSION['last_registration_id'])) {
    $rid = (int)$_SESSION['last_registration_id'];
}
if ($rid <= 0) {
    http_response_code(400); echo 'Missing registration id (rid)'; exit;
}

// Fetch registration and selected programs
$stmt = $pdo->prepare('SELECT sr.*, s.site_name, s.contact_address, s.contact_phone, s.contact_email FROM student_registrations sr LEFT JOIN site_settings s ON s.id = 1 WHERE sr.id = ? LIMIT 1');
$stmt->execute([$rid]);
$reg = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reg) { http_response_code(404); echo 'Registration not found'; exit; }

$p = $pdo->prepare('SELECT c.title FROM student_programs sp JOIN courses c ON c.id = sp.course_id WHERE sp.registration_id = ? ORDER BY c.title ASC');
$p->execute([$rid]);
$programTitles = array_map(function($r){ return $r['title']; }, $p->fetchAll(PDO::FETCH_ASSOC));

$fullName = trim(($reg['first_name'] ?? '') . ' ' . ($reg['last_name'] ?? ''));
if ($fullName === '') $fullName = 'Student';
$siteName = $reg['site_name'] ?? 'HIGH Q SOLID ACADEMY';
$address  = $reg['contact_address'] ?? '';
$phone    = $reg['contact_phone'] ?? '';
$email    = $reg['contact_email'] ?? '';
$today    = date('F j, Y');
$programsText = !empty($programTitles) ? implode(', ', $programTitles) : 'your chosen programme(s)';

// Path to the PDF template
$templatePath = __DIR__ . '/uploads/Admission Letter.pdf';

// PDF output when requested - use FPDI to overlay on template
if (isset($_GET['format']) && strtolower($_GET['format']) === 'pdf') {
    
    if (file_exists($templatePath)) {
        // Use FPDI to import the template and add text
        $pdf = new Fpdi();
        
        // Import the template page
        $pdf->AddPage();
        $pdf->setSourceFile($templatePath);
        $templateId = $pdf->importPage(1);
        $pdf->useTemplate($templateId, 0, 0, 210); // A4 width in mm
        
        // Set font for the content
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->SetTextColor(30, 30, 30);
        
        // Position the content - adjust Y position to fit within the letterhead
        // Start below the header (around 70mm from top)
        $startY = 75;
        $leftMargin = 25;
        $rightMargin = 25;
        $pageWidth = 210 - $leftMargin - $rightMargin;
        
        // Title
        $pdf->SetXY($leftMargin, $startY);
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell($pageWidth, 10, 'ADMISSION LETTER', 0, 1, 'C');
        
        // Date
        $pdf->SetXY($leftMargin, $startY + 20);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell($pageWidth, 6, 'Date: ' . $today, 0, 1, 'L');
        
        // Greeting
        $pdf->SetXY($leftMargin, $startY + 32);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 6, 'Dear ' . $fullName . ',', 0, 1, 'L');
        
        // Body paragraphs - using MultiCell for word wrap
        $pdf->SetXY($leftMargin, $startY + 45);
        $pdf->SetFont('Helvetica', '', 11);
        
        $bodyText = "We are pleased to offer you provisional admission into {$programsText} at {$siteName}.

This admission is granted based on your expressed interest and initial screening. Further enrolment steps will be communicated to you, including documentation and class schedule.

Please keep this letter for your records. If you have any questions, contact us via the details in the letterhead above.

We look forward to your success with us.";
        
        $pdf->MultiCell($pageWidth, 7, $bodyText, 0, 'L');
        
        // Signature section
        $currentY = $pdf->GetY() + 15;
        $pdf->SetXY($leftMargin, $currentY);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 6, '______________________________', 0, 1, 'L');
        
        $pdf->SetXY($leftMargin, $currentY + 8);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Admissions Office', 0, 1, 'L');
        
        $pdf->SetXY($leftMargin, $currentY + 14);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, $siteName, 0, 1, 'L');
        
        // Registration ID at bottom
        $pdf->SetXY($leftMargin, $currentY + 28);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 6, 'Registration ID: ' . $rid, 0, 1, 'L');
        
        // Output the PDF
        $filename = 'admission-letter-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $fullName) . '.pdf';
        $pdf->Output('D', $filename);
        exit;
        
    } else {
        // Fallback to Dompdf if template doesn't exist
        use Dompdf\Dompdf;
        use Dompdf\Options;
        
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(generateFallbackHtml($fullName, $programsText, $siteName, $address, $phone, $email, $today, $rid));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'admission-letter-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $fullName) . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}

// HTML preview (for browser viewing)
$logoUrl = app_url('assets/images/hq-logo.jpeg');
$cssUrl  = app_url('assets/css/public.css');

$html = '<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admission Letter - ' . htmlspecialchars($siteName) . '</title>
  <link rel="stylesheet" href="' . htmlspecialchars($cssUrl) . '">
  <style>
    body{background:#f7f7f7}
    .letter{max-width:820px;margin:24px auto;padding:28px;border:1px solid #eee;border-radius:8px;background:#fff}
    .lh-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
    .lh-meta{color:#555;font-size:13px;white-space:pre-line}
    .title{margin:10px 0 14px 0;text-align:center;font-size:24px;letter-spacing:0.4px}
    .content{font-size:16px;line-height:1.7;color:#222}
    .sig{margin-top:40px}
    .muted{color:#777}
    .notice{background:#fff3cd;border:1px solid #ffc107;padding:12px 16px;border-radius:6px;margin-bottom:20px;font-size:14px}
    .notice strong{color:#856404}
  </style>
</head>
<body>
  <div class="letter">
    <div class="notice">
      <strong>📄 Note:</strong> The PDF version uses your official company letterhead template. Click "Download PDF" to get the formatted version with the header and footer.
    </div>
    <div class="lh-header">
      <img src="' . htmlspecialchars($logoUrl) . '" alt="logo" style="height:64px;">
      <div>
        <h2 style="margin:0">' . htmlspecialchars($siteName) . '</h2>
        <div class="lh-meta">' . htmlspecialchars($address ?: '') . ($phone ? "\n$phone" : '') . ($email ? "\n$email" : '') . '</div>
      </div>
    </div>
    <hr>
    <div class="title"><strong>Admission Letter</strong></div>
    <div class="content">
      <p>Date: ' . htmlspecialchars($today) . '</p>
      <p>Dear <strong>' . htmlspecialchars($fullName) . '</strong>,</p>
      <p>We are pleased to offer you provisional admission into <strong>' . htmlspecialchars($programsText) . '</strong> at ' . htmlspecialchars($siteName) . '.</p>
      <p>This admission is granted based on your expressed interest and initial screening. Further enrolment steps will be communicated to you, including documentation and class schedule.</p>
      <p>Please keep this letter for your records. If you have any questions, contact us via the details above.</p>
      <p>We look forward to your success with us.</p>
      <div class="sig">
        <div class="muted">______________________________</div>
        <div><strong>Admissions Office</strong><br>' . htmlspecialchars($siteName) . '</div>
      </div>
    </div>
    <div class="muted" style="margin-top:22px">Registration ID: ' . (int)$rid . '</div>
    <div style="margin-top:18px">
      <a class="btn-primary" href="?rid=' . (int)$rid . '&format=pdf">Download PDF</a>
      <button class="btn" style="margin-left:8px" onclick="window.print()">Print</button>
      <a class="btn" style="margin-left:8px" href="' . htmlspecialchars(app_url('index.php')) . '">Return to site</a>
    </div>
  </div>
</body>
</html>';

echo $html;

// Fallback HTML generator function
function generateFallbackHtml($fullName, $programsText, $siteName, $address, $phone, $email, $today, $rid) {
    $logoUrl = app_url('assets/images/hq-logo.jpeg');
    return '<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admission Letter</title>
  <style>
    body{font-family:Arial,sans-serif;margin:40px}
    .letter{max-width:700px;margin:0 auto;padding:30px}
    .header{text-align:center;margin-bottom:30px}
    .title{font-size:24px;font-weight:bold;text-align:center;margin:20px 0}
    .content{font-size:14px;line-height:1.8}
    .sig{margin-top:40px}
  </style>
</head>
<body>
  <div class="letter">
    <div class="header">
      <h2>' . htmlspecialchars($siteName) . '</h2>
      <div>' . htmlspecialchars($address) . '</div>
      <div>' . htmlspecialchars($phone) . ' | ' . htmlspecialchars($email) . '</div>
    </div>
    <div class="title">ADMISSION LETTER</div>
    <div class="content">
      <p>Date: ' . htmlspecialchars($today) . '</p>
      <p>Dear <strong>' . htmlspecialchars($fullName) . '</strong>,</p>
      <p>We are pleased to offer you provisional admission into <strong>' . htmlspecialchars($programsText) . '</strong> at ' . htmlspecialchars($siteName) . '.</p>
      <p>This admission is granted based on your expressed interest and initial screening. Further enrolment steps will be communicated to you, including documentation and class schedule.</p>
      <p>Please keep this letter for your records.</p>
      <p>We look forward to your success with us.</p>
      <div class="sig">
        <div>______________________________</div>
        <div><strong>Admissions Office</strong><br>' . htmlspecialchars($siteName) . '</div>
      </div>
    </div>
    <div style="margin-top:20px;color:#777;font-size:12px">Registration ID: ' . (int)$rid . '</div>
  </div>
</body>
</html>';
}

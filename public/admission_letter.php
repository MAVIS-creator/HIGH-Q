<?php
// Generate Admission Letter using FPDI with template overlay
// Uses the official "Admission Letter.pdf" as background template
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

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

// PDF output when requested - use FPDI with template
if (isset($_GET['format']) && strtolower($_GET['format']) === 'pdf') {
    
    $templatePath = __DIR__ . '/uploads/Admission Letter.pdf';
    
    if (!file_exists($templatePath)) {
        // Fallback to Dompdf if template doesn't exist
        $pdfHtml = generateAdmissionLetterHtml($fullName, $programsText, $siteName, $address, $phone, $email, $today, $rid);
        
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($pdfHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'admission-letter-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $fullName) . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
    
    try {
        // Create FPDI instance
        $pdf = new Fpdi();
        
        // Add a page from the template
        $pdf->AddPage();
        
        // Import the template PDF
        $pdf->setSourceFile($templatePath);
        $tplId = $pdf->importPage(1);
        
        // Use the template as background (fit to page)
        $pdf->useTemplate($tplId, 0, 0, 210, 297);
        
        // Now write the dynamic content on top of template
        // Position carefully to not overlap with header/footer
        
        // Title - ADMISSION LETTER (centered, positioned after header)
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, 75);
        $pdf->Cell(210, 10, 'ADMISSION LETTER', 0, 1, 'C');
        
        // Draw underline for title
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(70, 86, 140, 86);
        
        // Set font for body text
        $pdf->SetFont('Arial', '', 12);
        
        // Date line
        $pdf->SetXY(25, 100);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Write(6, 'Date: ');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Write(6, $today);
        
        // Greeting
        $pdf->SetXY(25, 115);
        $pdf->Write(6, 'Dear ');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Write(6, $fullName);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Write(6, ',');
        
        // Body paragraph 1
        $pdf->SetXY(25, 130);
        $pdf->MultiCell(160, 7, "We are pleased to offer you provisional admission into " . $programsText . " at " . $siteName . ".", 0, 'J');
        
        // Body paragraph 2
        $pdf->SetXY(25, 155);
        $pdf->MultiCell(160, 7, "This admission is granted based on your expressed interest and initial screening. Further enrolment steps will be communicated to you, including documentation and class schedule.", 0, 'J');
        
        // Body paragraph 3
        $pdf->SetXY(25, 185);
        $pdf->MultiCell(160, 7, "Please keep this letter for your records. If you have any questions, contact us via the details in the letterhead above.", 0, 'J');
        
        // Body paragraph 4
        $pdf->SetXY(25, 210);
        $pdf->Write(6, "We look forward to your success with us.");
        
        // Signature section
        $pdf->SetXY(25, 235);
        $pdf->Cell(60, 0.5, '', 'T', 1, 'L'); // Signature line
        
        $pdf->SetXY(25, 240);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Write(6, 'Admissions Office');
        
        $pdf->SetXY(25, 248);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Write(6, $siteName);
        
        // Registration ID (small, positioned above footer)
        $pdf->SetXY(140, 255);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Write(5, 'Reg ID: ' . $rid);
        
        // Output the PDF
        $filename = 'admission-letter-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $fullName) . '.pdf';
        $pdf->Output('D', $filename); // D = download
        exit;
        
    } catch (Exception $e) {
        // If FPDI fails, use Dompdf fallback
        error_log("FPDI Error: " . $e->getMessage());
        
        $pdfHtml = generateAdmissionLetterHtml($fullName, $programsText, $siteName, $address, $phone, $email, $today, $rid);
        
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($pdfHtml);
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
    .btn-primary{display:inline-block;background:#FFD600;color:#000;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold}
    .btn-primary:hover{background:#e6c200}
    .btn{display:inline-block;background:#f0f0f0;color:#333;padding:10px 20px;border-radius:6px;text-decoration:none;border:1px solid #ddd;cursor:pointer}
    .btn:hover{background:#e0e0e0}
  </style>
</head>
<body>
  <div class="letter">
    <div class="notice">
      <strong>Note:</strong> The PDF version uses your official company letterhead template. Click "Download PDF" to get the formatted version with the header and footer.
    </div>
    <div class="lh-header">
      <img src="' . htmlspecialchars($logoUrl) . '" alt="logo" style="height:64px;border-radius:8px;">
      <div>
        <h2 style="margin:0;color:#000;"><span style="background:#000;color:#FFD600;padding:2px 8px;margin-right:5px;">HQ</span>' . htmlspecialchars($siteName) . '</h2>
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

// Fallback HTML generator function for Dompdf
function generateAdmissionLetterHtml($fullName, $programsText, $siteName, $address, $phone, $email, $today, $rid) {
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; color: #222; }
        
        .header {
            background: #FFD600;
            padding: 15px 30px;
            border-bottom: 3px solid #000;
        }
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #000;
        }
        .company-name .hq-box {
            background: #000;
            color: #FFD600;
            padding: 2px 8px;
            font-size: 18pt;
            margin-right: 5px;
        }
        .motto {
            color: #C00;
            font-style: italic;
            font-size: 11pt;
        }
        .address-line {
            font-size: 10pt;
            color: #333;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            font-size: 180pt;
            font-weight: bold;
            color: #888;
            z-index: -1;
        }
        
        .content {
            padding: 40px 50px;
            min-height: 550px;
        }
        .letter-title {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 25px;
        }
        
        .footer {
            background: #FFD600;
            padding: 12px 30px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 2px solid #000;
        }
        .social-item {
            font-size: 10pt;
            margin-bottom: 3px;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="watermark">HQ</div>
    
    <div class="header">
        <div class="company-name"><span class="hq-box">HQ</span> HIGH - Q SOLID ACADEMY</div>
        <div class="motto">Motto: Always Ahead of Others</div>
        <div class="address-line">Shop 18, World Star Complex Opposite London Street,</div>
        <div class="address-line">Ayetoro Maya, Ikorodu Lagos State. | 0807 208 8794</div>
    </div>
    
    <div class="content">
        <div class="letter-title">ADMISSION LETTER</div>
        <p><strong>Date:</strong> ' . htmlspecialchars($today) . '</p><br>
        <p>Dear <strong>' . htmlspecialchars($fullName) . '</strong>,</p><br>
        <p>We are pleased to offer you provisional admission into <strong>' . htmlspecialchars($programsText) . '</strong> at ' . htmlspecialchars($siteName) . '.</p><br>
        <p>This admission is granted based on your expressed interest and initial screening. Further enrolment steps will be communicated to you, including documentation and class schedule.</p><br>
        <p>Please keep this letter for your records.</p><br>
        <p>We look forward to your success with us.</p><br><br>
        <p>______________________________</p>
        <p><strong>Admissions Office</strong></p>
        <p>' . htmlspecialchars($siteName) . '</p>
        <p style="color:#777;font-size:9pt;margin-top:30px">Registration ID: ' . (int)$rid . '</p>
    </div>
    
    <div class="footer">
        <div class="social-item">f Highqsolidacademy | @ highqsolidacademy | highqsolidacademy@gmail.com</div>
    </div>
</body>
</html>';
}

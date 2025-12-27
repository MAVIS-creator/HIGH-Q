<?php
// Generate Admission Letter (HTML or PDF)
// Creates styled PDF with company letterhead design
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

// PDF output when requested - use Dompdf with styled letterhead
if (isset($_GET['format']) && strtolower($_GET['format']) === 'pdf') {
    
    // Create styled HTML that matches the company letterhead
    $pdfHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #222;
            position: relative;
            min-height: 100vh;
        }
        
        /* Header - Yellow banner with company info */
        .header {
            background: #FFD600;
            padding: 15px 30px;
            border-bottom: 3px solid #000;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 30%;
        }
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 2px;
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
            margin-bottom: 5px;
        }
        .address-line {
            font-size: 10pt;
            color: #333;
        }
        .rc-number {
            font-weight: bold;
            font-size: 11pt;
        }
        
        /* Watermark */
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
            white-space: nowrap;
        }
        
        /* Content area */
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
        .date-line {
            margin-bottom: 20px;
        }
        .greeting {
            margin-bottom: 15px;
        }
        .body-text {
            margin-bottom: 15px;
            text-align: justify;
        }
        .signature {
            margin-top: 40px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-bottom: 5px;
        }
        .reg-id {
            margin-top: 30px;
            font-size: 10pt;
            color: #666;
        }
        
        /* Footer - Yellow banner with social info */
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
        .social-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            background: #1877F2;
            color: white;
            text-align: center;
            border-radius: 3px;
            margin-right: 5px;
            font-size: 10pt;
            line-height: 18px;
        }
        .social-icon.ig { background: #E4405F; }
        .social-icon.mail { background: #EA4335; }
    </style>
</head>
<body>
    <!-- Watermark -->
    <div class="watermark">HQ</div>
    
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <div class="company-name"><span class="hq-box">HQ</span> HIGH - Q SOLID ACADEMY</div>
                <div class="motto">Motto: Always Ahead of Others</div>
                <div class="address-line">Shop 18, World Star Complex Opposite London Street,</div>
                <div class="address-line">Ayetoro Maya, Ikorodu Lagos State.</div>
                <div class="address-line">0807 208 8794, 07018412450</div>
            </div>
            <div class="header-right">
                <div class="rc-number">RC: 1910459</div>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="content">
        <div class="letter-title">ADMISSION LETTER</div>
        
        <div class="date-line"><strong>Date:</strong> ' . htmlspecialchars($today) . '</div>
        
        <div class="greeting">Dear <strong>' . htmlspecialchars($fullName) . '</strong>,</div>
        
        <div class="body-text">
            We are pleased to offer you provisional admission into <strong>' . htmlspecialchars($programsText) . '</strong> at ' . htmlspecialchars($siteName) . '.
        </div>
        
        <div class="body-text">
            This admission is granted based on your expressed interest and initial screening. Further enrolment steps will be communicated to you, including documentation and class schedule.
        </div>
        
        <div class="body-text">
            Please keep this letter for your records. If you have any questions, contact us via the details in the letterhead above.
        </div>
        
        <div class="body-text">
            We look forward to your success with us.
        </div>
        
        <div class="signature">
            <div class="signature-line"></div>
            <div><strong>Admissions Office</strong></div>
            <div>' . htmlspecialchars($siteName) . '</div>
        </div>
        
        <div class="reg-id">Registration ID: ' . (int)$rid . '</div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div class="social-item"><span class="social-icon">f</span> Highqsolidacademy</div>
        <div class="social-item"><span class="social-icon ig">@</span> highqsolidacademy</div>
        <div class="social-item"><span class="social-icon mail">✉</span> adebulequamokikiola@gmail.com</div>
    </div>
</body>
</html>';
    
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($pdfHtml);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $filename = 'admission-letter-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $fullName) . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
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

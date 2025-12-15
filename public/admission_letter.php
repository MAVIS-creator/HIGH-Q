<?php
// Generate Admission Letter (HTML or PDF)
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

// Asset URLs
$logoUrl = app_url('assets/images/hq-logo.jpeg');
$cssUrl  = app_url('assets/css/public.css');
// Best-effort letterhead image: if an image variant exists, prefer it; else we render a clean HTML header.
$letterheadImage = null;
foreach (['uploads/Admission%20Letter.png', 'uploads/Admission%20Letter.jpg', 'uploads/Admission%20Letter.jpeg', 'uploads/letterhead.png', 'uploads/letterhead.jpg'] as $candidate) {
    $url = app_url($candidate);
    // Do not remote-fetch; let Dompdf attempt if PDF is requested.
    $letterheadImage = $url; break;
}

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
    .watermark{position:absolute;inset:0;opacity:0.06;background-size:contain;background-repeat:no-repeat;background-position:center;pointer-events:none}
    .rel{position:relative}
  </style>
  </head>
  <body>
    <div class="letter rel">';
if ($letterheadImage) {
  $html .= '<div class="watermark" style="background-image:url(' . htmlspecialchars($letterheadImage) . ');"></div>';
}
$html .= '  <div class="lh-header">
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

// PDF output when requested
if (isset($_GET['format']) && strtolower($_GET['format']) === 'pdf') {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $filename = 'admission-letter-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $fullName) . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

echo $html;

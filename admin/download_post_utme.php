<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM post_utme_registrations WHERE id = ?");
$stmt->execute([$id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registration) {
    die('Registration not found');
}

// Decode JSON data
$jambSubjects = json_decode($registration['jamb_subjects'] ?? '[]', true);
$jambGrades = json_decode($registration['jamb_grades'] ?? '[]', true);
$olevelSubjects = json_decode($registration['olevel_subjects'] ?? '[]', true);
$olevelGrades = json_decode($registration['olevel_grades'] ?? '[]', true);

// Generate HTML
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>POST UTME Registration Form</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { max-width: 100px; }
        .section { margin-bottom: 20px; }
        .section h3 { color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .passport { text-align: center; margin: 20px 0; }
        .passport img { max-width: 150px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="assets/images/hq-logo.jpeg" alt="HQ Logo" class="logo">
        <h2>HIGH Q SOLID ACADEMY</h2>
        <h3>POST UTME Registration Form</h3>
    </div>
    
    <div class="section">
        <h3>Personal Information</h3>
        <table>
            <tr>
                <td><strong>Institution:</strong> ' . htmlspecialchars($registration['institution_name']) . '</td>
                <td><strong>Full Name:</strong> ' . htmlspecialchars($registration['surname'] . ' ' . $registration['first_name']) . '</td>
            </tr>
            <tr>
                <td><strong>Gender:</strong> ' . ucfirst(htmlspecialchars($registration['gender'])) . '</td>
                <td><strong>Email:</strong> ' . htmlspecialchars($registration['email']) . '</td>
            </tr>
            <tr>
                <td><strong>Phone:</strong> ' . htmlspecialchars($registration['parents_phone']) . '</td>
                <td><strong>State of Origin:</strong> ' . htmlspecialchars($registration['state_of_origin']) . '</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <h3>JAMB Details</h3>
        <table>
            <tr>
                <td><strong>JAMB Reg Number:</strong> ' . htmlspecialchars($registration['jamb_registration_number']) . '</td>
                <td><strong>JAMB Score:</strong> ' . htmlspecialchars($registration['jamb_score']) . '</td>
            </tr>
        </table>
        
        <h4>JAMB Subjects and Scores</h4>
        <table>
            <tr>
                <th>Subject</th>
                <th>Score</th>
            </tr>';

foreach ($jambSubjects as $i => $subject) {
    $html .= '<tr>
                <td>' . htmlspecialchars($subject) . '</td>
                <td>' . htmlspecialchars($jambGrades[$i] ?? '') . '</td>
            </tr>';
}

$html .= '</table>
    </div>
    
    <div class="section">
        <h3>O\'Level Results</h3>
        <table>
            <tr>
                <td><strong>Exam Type:</strong> ' . htmlspecialchars($registration['exam_type']) . '</td>
                <td><strong>Exam Number:</strong> ' . htmlspecialchars($registration['exam_number']) . '</td>
            </tr>
        </table>
        
        <table>
            <tr>
                <th>Subject</th>
                <th>Grade</th>
            </tr>';

foreach ($olevelSubjects as $i => $subject) {
    $html .= '<tr>
                <td>' . htmlspecialchars($subject) . '</td>
                <td>' . htmlspecialchars($olevelGrades[$i] ?? '') . '</td>
            </tr>';
}

$html .= '</table>
    </div>';

if ($registration['passport_photo']) {
    $html .= '<div class="passport">
        <img src="' . $registration['passport_photo'] . '" alt="Passport Photo">
    </div>';
}

$html .= '
    <div style="margin-top: 30px; text-align: center;">
        <p>Form ID: ' . str_pad($registration['id'], 6, '0', STR_PAD_LEFT) . '</p>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';

// Create PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();

// Output PDF
$filename = 'POST_UTME_Registration_' . $registration['id'] . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
<?php
/**
 * Welcome Kit Generator - HIGH Q SOLID ACADEMY
 * Generates a professionally styled PDF welcome kit with real logo
 * Uses company brand colors: Yellow #FFD600, Black #000000
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generateWelcomeKitPDF($programType, $studentName, $studentEmail, $registrationId) {
    global $pdo;

    // Get site settings for contact info
    $siteSettings = getSiteSettings();
    $contactPhone = $siteSettings['contact_phone'] ?? '0807 208 8794';
    $contactEmail = $siteSettings['contact_email'] ?? 'highqsolidacademy@gmail.com';
    
    // Get HQ Logo as base64
    $logoPath = __DIR__ . '/../assets/images/hq-logo.jpeg';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode($logoData);
    }
    
    // Program-specific content
    $programContent = [
        'jamb' => [
            'title' => 'JAMB/UTME University Admission',
            'syllabus' => [
                'English Language' => 'Comprehension, Essay, Objective questions',
                'Mathematics' => 'Algebra, Geometry, Trigonometry, Calculus',
                'Biology' => 'Cell Biology, Ecology, Human Physiology, Genetics',
                'Chemistry' => 'Inorganic, Organic, Physical Chemistry',
                'Physics' => 'Mechanics, Waves, Electricity, Modern Physics'
            ],
            'dressCode' => 'Smart casual attire. White shirt/blouse with dark trousers/skirt. No torn clothes, no excessive jewelry.',
            'center' => [
                'name' => 'High-Q Learning Center',
                'address' => 'Shop 18, World Star Complex, Ayetoro Maya, Ikorodu Lagos',
                'phone' => $contactPhone,
                'hours' => 'Monday - Friday: 4:00 PM - 7:00 PM, Saturday: 10:00 AM - 4:00 PM'
            ]
        ],
        'waec' => [
            'title' => 'SSCE/GCE O-Levels Examination',
            'syllabus' => [
                'English Language' => 'Comprehension, Grammar, Essay writing',
                'Mathematics' => 'Algebra, Geometry, Trigonometry',
                'Integrated Science' => 'Physics, Chemistry, Biology concepts',
                'Social Studies' => 'History, Geography, Civic education',
                'Literature' => 'Prose, Poetry, Drama analysis'
            ],
            'dressCode' => 'Clean school uniform or white shirt with dark trousers/skirt required. Closed shoes.',
            'center' => [
                'name' => 'High-Q Learning Center',
                'address' => 'Shop 18, World Star Complex, Ayetoro Maya, Ikorodu Lagos',
                'phone' => $contactPhone,
                'hours' => 'Monday - Friday: 4:00 PM - 7:00 PM, Saturday: 10:00 AM - 4:00 PM'
            ]
        ],
        'postutme' => [
            'title' => 'Post-UTME Screening Preparation',
            'syllabus' => [
                'General Studies' => 'Current affairs, civics, reasoning',
                'Subject-Specific Topics' => 'University-specific exam formats',
                'Aptitude Tests' => 'Logic, comprehension, calculation tests',
                'Essay Writing' => 'Academic writing and argumentation',
                'Interview Skills' => 'Mock interviews and presentation'
            ],
            'dressCode' => 'Business formal attire. White shirt/blouse, dark jacket/blazer, formal trousers/skirt.',
            'center' => [
                'name' => 'High-Q Learning Center',
                'address' => 'Shop 18, World Star Complex, Ayetoro Maya, Ikorodu Lagos',
                'phone' => $contactPhone,
                'hours' => 'Monday - Friday: 5:00 PM - 8:00 PM, Saturday: 11:00 AM - 5:00 PM'
            ]
        ],
        'digital' => [
            'title' => 'Digital Skills & Tech Training',
            'syllabus' => [
                'Web Fundamentals' => 'HTML, CSS, JavaScript basics',
                'Web Development' => 'Responsive design, frameworks',
                'Back-End Development' => 'Server-side programming, databases',
                'Version Control' => 'Git, GitHub collaboration',
                'Project Development' => 'Real-world project execution'
            ],
            'dressCode' => 'Casual comfortable clothing. Laptop/computer required for all classes.',
            'center' => [
                'name' => 'High-Q Tech Hub',
                'address' => 'Shop 18, World Star Complex, Ayetoro Maya, Ikorodu Lagos',
                'phone' => $contactPhone,
                'hours' => 'Monday - Friday: 6:00 PM - 9:00 PM, Saturday: 12:00 PM - 6:00 PM'
            ]
        ],
        'international' => [
            'title' => 'International English Proficiency',
            'syllabus' => [
                'Listening Skills' => 'Accent adaptation, comprehension exercises',
                'Speaking Practice' => 'Pronunciation, fluency, accent reduction',
                'Reading Comprehension' => 'Academic texts, news articles, essays',
                'Writing Skills' => 'Academic essays, formal letters, reports',
                'Grammar & Vocabulary' => 'Advanced grammar, idioms, collocations'
            ],
            'dressCode' => 'Smart casual attire. Comfortable clothing for speaking practice.',
            'center' => [
                'name' => 'High-Q International Center',
                'address' => 'Shop 18, World Star Complex, Ayetoro Maya, Ikorodu Lagos',
                'phone' => $contactPhone,
                'hours' => 'Monday - Friday: 3:00 PM - 6:00 PM, Saturday & Sunday: 10:00 AM - 3:00 PM'
            ]
        ]
    ];

    $content = $programContent[$programType] ?? $programContent['jamb'];
    $generatedDate = date('F j, Y');
    $escapedName = htmlspecialchars($studentName);
    $escapedEmail = htmlspecialchars($studentEmail);
    $escapedTitle = htmlspecialchars($content['title']);
    $escapedPhone = htmlspecialchars($contactPhone);
    $escapedContactEmail = htmlspecialchars($contactEmail);

    // Build syllabus HTML
    $syllabusHtml = '';
    foreach ($content['syllabus'] as $topic => $description) {
        $syllabusHtml .= '
            <div class="syllabus-item">
                <div class="syllabus-topic">' . htmlspecialchars($topic) . '</div>
                <div class="syllabus-desc">' . htmlspecialchars($description) . '</div>
            </div>';
    }

    // Generate styled HTML for PDF with real logo
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: A4;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            line-height: 1.5;
            font-size: 11pt;
        }
        
        /* ===== HEADER ===== */
        .header {
            background: #FFD600;
            padding: 15px 25px;
            border-bottom: 4px solid #000;
        }
        .header-table {
            width: 100%;
        }
        .header-logo {
            width: 70px;
            vertical-align: middle;
        }
        .header-logo img {
            width: 65px;
            height: 65px;
            border-radius: 8px;
            border: 2px solid #000;
        }
        .header-text {
            vertical-align: middle;
            padding-left: 12px;
        }
        .company-name {
            font-size: 20pt;
            font-weight: bold;
            color: #000;
        }
        .hq-badge {
            background: #000;
            color: #FFD600;
            padding: 2px 8px;
            font-size: 14pt;
            font-weight: bold;
            margin-right: 6px;
        }
        .motto {
            color: #C00;
            font-style: italic;
            font-size: 10pt;
            margin-top: 2px;
        }
        .contact-info {
            font-size: 9pt;
            color: #333;
            margin-top: 3px;
        }
        
        /* ===== WELCOME BANNER ===== */
        .welcome-banner {
            background: #000;
            color: #FFD600;
            padding: 20px 25px;
            text-align: center;
        }
        .welcome-title {
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .welcome-subtitle {
            font-size: 11pt;
            color: #fff;
            margin-top: 5px;
        }
        
        /* ===== CONTENT ===== */
        .content {
            padding: 20px 25px;
            padding-bottom: 100px;
        }
        
        /* Welcome Message */
        .welcome-message {
            background: #FFFDE7;
            border-left: 5px solid #FFD600;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        .welcome-message h2 {
            color: #000;
            font-size: 13pt;
            margin-bottom: 8px;
        }
        .welcome-message p {
            color: #444;
            font-size: 10pt;
            margin-bottom: 6px;
        }
        
        /* Student Info Card */
        .student-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .student-card-title {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .student-info-row {
            margin-bottom: 4px;
            font-size: 10pt;
        }
        
        /* Section Headers */
        .section-header {
            background: #000;
            color: #FFD600;
            padding: 8px 12px;
            font-size: 11pt;
            font-weight: bold;
            margin: 18px 0 12px 0;
            border-radius: 4px;
        }
        
        /* Syllabus Items */
        .syllabus-item {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #FFD600;
            padding: 10px 12px;
            margin-bottom: 8px;
            border-radius: 0 4px 4px 0;
        }
        .syllabus-topic {
            font-weight: bold;
            color: #000;
            font-size: 10pt;
        }
        .syllabus-desc {
            color: #555;
            font-size: 9pt;
        }
        
        /* Info Boxes */
        .dress-code-box {
            background: #FFF8E1;
            border: 2px solid #FFC107;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 15px;
        }
        .dress-code-box h4 {
            color: #E65100;
            margin-bottom: 6px;
            font-size: 10pt;
        }
        .dress-code-box p {
            color: #5D4037;
            font-size: 9pt;
        }
        
        .rules-box {
            background: #FFEBEE;
            border: 2px solid #EF5350;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 15px;
        }
        .rules-box h4 {
            color: #C62828;
            margin-bottom: 8px;
            font-size: 10pt;
        }
        .rules-list {
            margin-left: 18px;
            color: #5D4037;
            font-size: 9pt;
        }
        .rules-list li {
            margin-bottom: 3px;
        }
        
        .center-box {
            background: #E3F2FD;
            border: 2px solid #2196F3;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 15px;
        }
        .center-box h4 {
            color: #1565C0;
            margin-bottom: 8px;
            font-size: 11pt;
        }
        .center-detail {
            margin-bottom: 4px;
            font-size: 9pt;
            color: #1A237E;
        }
        
        /* Steps */
        .steps-grid {
            margin-top: 10px;
        }
        .step-item {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 8px;
            border-left: 4px solid #FFD600;
        }
        .step-number {
            background: #FFD600;
            color: #000;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 24px;
            font-weight: bold;
            font-size: 11pt;
            margin-right: 10px;
        }
        .step-title {
            font-weight: bold;
            color: #000;
            font-size: 10pt;
            display: inline;
        }
        .step-desc {
            color: #666;
            font-size: 9pt;
            margin-top: 3px;
            margin-left: 34px;
        }
        
        /* ===== FOOTER ===== */
        .footer {
            background: #FFD600;
            padding: 12px 25px;
            border-top: 3px solid #000;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }
        .footer-table {
            width: 100%;
        }
        .footer-social {
            font-size: 9pt;
            color: #000;
        }
        .footer-social a {
            color: #000;
            text-decoration: none;
            margin-right: 15px;
        }
        .social-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            border-radius: 3px;
            text-align: center;
            line-height: 18px;
            font-size: 10pt;
            margin-right: 3px;
            font-weight: bold;
        }
        .fb-icon { background: #1877F2; color: #fff; }
        .ig-icon { background: #E4405F; color: #fff; }
        .email-icon { background: #EA4335; color: #fff; }
        .footer-motto {
            text-align: right;
            font-style: italic;
            color: #C00;
            font-size: 9pt;
        }
        
        .generated-date {
            text-align: center;
            font-size: 9pt;
            color: #888;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- HEADER with Real Logo -->
    <div class="header">
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="header-logo">
                    <img src="{$logoBase64}" alt="HQ Logo">
                </td>
                <td class="header-text">
                    <div class="company-name"><span class="hq-badge">HQ</span> HIGH-Q SOLID ACADEMY</div>
                    <div class="motto">Motto: Always Ahead of Others</div>
                    <div class="contact-info">Shop 18, World Star Complex, Ayetoro Maya, Ikorodu Lagos | {$escapedPhone}</div>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- WELCOME BANNER -->
    <div class="welcome-banner">
        <div class="welcome-title">WELCOME KIT</div>
        <div class="welcome-subtitle">Your Complete Guide to Success</div>
    </div>
    
    <!-- CONTENT -->
    <div class="content">
        <!-- Welcome Message -->
        <div class="welcome-message">
            <h2>Hey {$escapedName}, We're Excited to Have You!</h2>
            <p>You just took a BIG step toward your goals, and we're honored to be part of your journey!</p>
            <p>You're now enrolled in our <strong>{$escapedTitle}</strong> program. Our expert team is ready to help you succeed.</p>
            <p style="font-style:italic;color:#888;">Think of this as your "get started in 5 minutes" guide!</p>
        </div>
        
        <!-- Student Info -->
        <div class="student-card">
            <div class="student-card-title">Your Registration Details</div>
            <div class="student-info-row"><strong>Registration ID:</strong> {$registrationId}</div>
            <div class="student-info-row"><strong>Program:</strong> {$escapedTitle}</div>
            <div class="student-info-row"><strong>Email:</strong> {$escapedEmail}</div>
        </div>
        
        <!-- Syllabus -->
        <div class="section-header">Program Syllabus & Learning Topics</div>
        {$syllabusHtml}
        
        <!-- Dress Code -->
        <div class="section-header">Dress Code & Appearance</div>
        <div class="dress-code-box">
            <h4>Required Dress Code:</h4>
            <p>{$content['dressCode']}</p>
        </div>
        
        <!-- Rules -->
        <div class="rules-box">
            <h4>Important Center Rules</h4>
            <ul class="rules-list">
                <li>Arrive 10 minutes early to class</li>
                <li>Keep mobile phone on silent mode</li>
                <li>No eating in class (water allowed)</li>
                <li>Maintain professional behavior</li>
                <li>Inform instructors if you'll miss class</li>
                <li>Respect all facilities and equipment</li>
            </ul>
        </div>
        
        <!-- Center Info -->
        <div class="section-header">Center Location & Hours</div>
        <div class="center-box">
            <h4>{$content['center']['name']}</h4>
            <div class="center-detail"><strong>Address:</strong> {$content['center']['address']}</div>
            <div class="center-detail"><strong>Phone:</strong> {$content['center']['phone']}</div>
            <div class="center-detail"><strong>Hours:</strong> {$content['center']['hours']}</div>
        </div>
        
        <!-- Getting Started -->
        <div class="section-header">Getting Started - Your Next Steps</div>
        <div class="steps-grid">
            <div class="step-item">
                <span class="step-number">1</span>
                <span class="step-title">Review this Welcome Kit</span>
                <div class="step-desc">Read through all sections to understand what to expect</div>
            </div>
            <div class="step-item">
                <span class="step-number">2</span>
                <span class="step-title">Prepare Your Materials</span>
                <div class="step-desc">Get notepads, pens, and any required textbooks</div>
            </div>
            <div class="step-item">
                <span class="step-number">3</span>
                <span class="step-title">Attend Your First Class</span>
                <div class="step-desc">Show up early, dress properly, and come ready to learn</div>
            </div>
            <div class="step-item">
                <span class="step-number">4</span>
                <span class="step-title">Stay Connected</span>
                <div class="step-desc">Check emails regularly for updates and announcements</div>
            </div>
        </div>
        
        <div class="generated-date">Generated on: {$generatedDate}</div>
    </div>
    
    <!-- FOOTER with Social Links -->
    <div class="footer">
        <table class="footer-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="footer-social">
                    <a href="https://facebook.com/Highqsolidacademy"><span class="social-icon fb-icon">f</span> Highqsolidacademy</a>
                    <a href="https://instagram.com/highqsolidacademy"><span class="social-icon ig-icon">@</span> highqsolidacademy</a>
                    <a href="mailto:{$escapedContactEmail}"><span class="social-icon email-icon">✉</span> {$escapedContactEmail}</a>
                </td>
                <td class="footer-motto">"Always Ahead of Others"</td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;

    try {
        // Create Dompdf instance
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Generate filename
        $filename = "welcome-kit-{$registrationId}-" . date('Y-m-d') . ".pdf";
        $filepath = __DIR__ . "/../storage/welcome-kits/{$filename}";
        
        // Ensure directory exists
        @mkdir(dirname($filepath), 0755, true);
        
        // Save file
        file_put_contents($filepath, $dompdf->output());
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Send welcome kit email with real HQ logo and clickable social links
 */
function sendWelcomeKitEmail($studentEmail, $studentName, $programType, $registrationId, $pdfPath) {
    global $pdo;
    
    $siteSettings = getSiteSettings();
    $contactPhone = $siteSettings['contact_phone'] ?? '0807 208 8794';
    $contactEmail = $siteSettings['contact_email'] ?? 'highqsolidacademy@gmail.com';
    
    // Get HQ Logo as base64 for email
    $logoPath = __DIR__ . '/../assets/images/hq-logo.jpeg';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode($logoData);
    }
    
    // Escape values
    $escapedName = htmlspecialchars($studentName);
    $escapedProgram = htmlspecialchars(ucfirst($programType));
    $escapedPhone = htmlspecialchars($contactPhone);
    $escapedEmail = htmlspecialchars($contactEmail);
    $currentYear = date('Y');

    // Professional subject line (no emojis for compatibility)
    $subject = "Your Welcome Kit - High-Q Registration #{$registrationId}";
    
    // Styled email body with real logo and clickable social links
    $message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Ensure images display properly in email */
        img { display: block; border: 0; }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f4f4;">
        <tr>
            <td align="center" style="padding:30px 10px;">
                <!-- Main Container -->
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                    
                    <!-- Header with Yellow Background and Real Logo -->
                    <tr>
                        <td style="background:#FFD600;padding:25px;border-bottom:4px solid #000000;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="70" valign="middle">
                                        <img src="{$logoBase64}" alt="HQ Logo" width="60" height="60" style="display:block;border-radius:8px;border:2px solid #000;">
                                    </td>
                                    <td valign="middle" style="padding-left:15px;">
                                        <div style="font-size:22px;font-weight:bold;color:#000;"><span style="background:#000;color:#FFD600;padding:2px 8px;margin-right:5px;">HQ</span> HIGH-Q SOLID ACADEMY</div>
                                        <div style="color:#C00;font-style:italic;font-size:12px;margin-top:3px;">Motto: Always Ahead of Others</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Welcome Banner -->
                    <tr>
                        <td style="background:#000;padding:20px;text-align:center;">
                            <div style="color:#FFD600;font-size:24px;font-weight:bold;letter-spacing:2px;">Welcome to HIGH-Q!</div>
                            <div style="color:#fff;font-size:14px;margin-top:5px;">Your journey to success starts NOW</div>
                        </td>
                    </tr>
                    
                    <!-- Body Content -->
                    <tr>
                        <td style="padding:30px 25px;">
                            <p style="margin:0 0 18px;color:#222;font-size:16px;">Hey <strong>{$escapedName}</strong>,</p>
                            
                            <p style="margin:0 0 18px;color:#444;font-size:15px;line-height:1.6;">
                                Congratulations on taking this amazing step! You've just joined High-Q Solid Academy, and we're absolutely thrilled to have you as part of our learning family.
                            </p>
                            
                            <!-- Attachment Notice Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;">
                                <tr>
                                    <td style="background:#FFFDE7;border-left:5px solid #FFD600;padding:18px;border-radius:0 8px 8px 0;">
                                        <p style="margin:0;color:#000;font-size:15px;font-weight:bold;">Attached: Your Personalized Welcome Kit (PDF)</p>
                                        <p style="margin:8px 0 0;color:#555;font-size:13px;">Everything you need to succeed in one document!</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin:0 0 10px;color:#222;font-size:15px;font-weight:bold;">Your Welcome Kit Includes:</p>
                            <ul style="margin:0 0 20px;padding-left:25px;color:#444;font-size:14px;line-height:1.8;">
                                <li><strong>Syllabus:</strong> Exactly what you'll learn and master</li>
                                <li><strong>Dress Code:</strong> How to dress for success</li>
                                <li><strong>Center Details:</strong> Location, hours, and getting there</li>
                                <li><strong>Center Rules:</strong> Guidelines for a great experience</li>
                                <li><strong>Getting Started:</strong> 4 easy steps to rock your first day</li>
                            </ul>
                            
                            <!-- Registration Info Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;">
                                <tr>
                                    <td style="background:#f8f9fa;border:2px solid #e0e0e0;padding:18px;border-radius:8px;">
                                        <p style="margin:0 0 5px;color:#666;font-size:10px;text-transform:uppercase;letter-spacing:1px;font-weight:bold;">Your Registration Details</p>
                                        <p style="margin:8px 0;color:#222;font-size:14px;"><strong>Registration ID:</strong> {$registrationId}</p>
                                        <p style="margin:8px 0;color:#222;font-size:14px;"><strong>Program:</strong> {$escapedProgram} Program</p>
                                        <p style="margin:8px 0 0;color:#222;font-size:14px;"><strong>Email:</strong> {$studentEmail}</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Help Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;">
                                <tr>
                                    <td style="background:#E3F2FD;border-left:5px solid #2196F3;padding:15px 18px;border-radius:0 8px 8px 0;">
                                        <p style="margin:0;color:#1565C0;font-size:14px;"><strong>Questions?</strong> We're here to help!</p>
                                        <p style="margin:8px 0 0;color:#1A237E;font-size:13px;">Call us at <strong>{$escapedPhone}</strong> or reply to this email.</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin:25px 0 0;text-align:center;color:#000;font-size:16px;font-weight:bold;">
                                You've got this! Let's make magic happen together.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer with Social Links -->
                    <tr>
                        <td style="background:#FFD600;padding:20px 25px;border-top:3px solid #000000;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="padding-bottom:15px;">
                                        <p style="margin:0;color:#000;font-size:16px;font-weight:bold;">HIGH-Q SOLID ACADEMY</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:12px;">
                                        <!-- Social Links with Boxicons -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                                            <tr>
                                                <td style="padding:0 8px;">
                                                    <a href="https://facebook.com/Highqsolidacademy" style="text-decoration:none;display:inline-block;">
                                                        <div style="background:#1877F2;color:#fff;width:32px;height:32px;line-height:32px;text-align:center;border-radius:6px;font-size:18px;">
                                                            <i class='bx bxl-facebook' style="color:#fff;"></i>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td style="padding:0 8px;">
                                                    <a href="https://instagram.com/highqsolidacademy" style="text-decoration:none;display:inline-block;">
                                                        <div style="background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);color:#fff;width:32px;height:32px;line-height:32px;text-align:center;border-radius:6px;font-size:18px;">
                                                            <i class='bx bxl-instagram' style="color:#fff;"></i>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td style="padding:0 8px;">
                                                    <a href="mailto:{$escapedEmail}" style="text-decoration:none;display:inline-block;">
                                                        <div style="background:#EA4335;color:#fff;width:32px;height:32px;line-height:32px;text-align:center;border-radius:6px;font-size:18px;">
                                                            <i class='bx bx-envelope' style="color:#fff;"></i>
                                                        </div>
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <p style="margin:0;color:#333;font-size:12px;">{$escapedPhone} | {$escapedEmail}</p>
                                        <p style="margin:10px 0 0;color:#C00;font-size:11px;font-style:italic;">"Always Ahead of Others"</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Copyright -->
                    <tr>
                        <td style="background:#000000;padding:12px;text-align:center;">
                            <p style="margin:0;color:#888;font-size:11px;">&copy; {$currentYear} High-Q Solid Academy. All rights reserved.</p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    try {
        // Use sendEmail function which uses PHPMailer with proper SMTP config
        $attachments = [];
        if (file_exists($pdfPath)) {
            $attachments[] = $pdfPath;
        }
        
        return sendEmail($studentEmail, $subject, $message, $attachments);
    } catch (Exception $e) {
        error_log("Welcome kit email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get site settings from database
 */
function getSiteSettings() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}
?>

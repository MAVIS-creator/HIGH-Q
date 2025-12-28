<?php
/**
 * Welcome Kit Generator - HIGH Q SOLID ACADEMY
 * Generates a professionally styled PDF welcome kit
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

    // Generate styled HTML for PDF
    $html = <<<HTML
<!DOCTYPE html>
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
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            line-height: 1.5;
            font-size: 11pt;
        }
        
        /* ===== HEADER ===== */
        .header {
            background: #FFD600;
            padding: 20px 30px;
            border-bottom: 4px solid #000;
            position: relative;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .header-logo {
            display: table-cell;
            vertical-align: middle;
            width: 80px;
        }
        .header-logo img {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            border: 2px solid #000;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            padding-left: 15px;
        }
        .company-name {
            font-size: 22pt;
            font-weight: bold;
            color: #000;
        }
        .hq-badge {
            background: #000;
            color: #FFD600;
            padding: 3px 10px;
            font-size: 16pt;
            font-weight: bold;
            margin-right: 8px;
        }
        .motto {
            color: #C00;
            font-style: italic;
            font-size: 10pt;
            margin-top: 3px;
        }
        .contact-info {
            font-size: 9pt;
            color: #333;
            margin-top: 5px;
        }
        
        /* ===== WELCOME BANNER ===== */
        .welcome-banner {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: #FFD600;
            padding: 25px 30px;
            text-align: center;
        }
        .welcome-title {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .welcome-subtitle {
            font-size: 11pt;
            color: #fff;
        }
        
        /* ===== CONTENT AREA ===== */
        .content {
            padding: 25px 30px;
        }
        
        /* Welcome Message */
        .welcome-message {
            background: #FFFDE7;
            border-left: 5px solid #FFD600;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 0 8px 8px 0;
        }
        .welcome-message h2 {
            color: #000;
            font-size: 14pt;
            margin-bottom: 10px;
        }
        .welcome-message p {
            color: #444;
            font-size: 10pt;
        }
        
        /* Student Info Card */
        .student-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        .student-card-title {
            font-size: 9pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .student-info-row {
            margin-bottom: 6px;
            font-size: 10pt;
        }
        .student-info-row strong {
            color: #000;
        }
        
        /* Section Headers */
        .section-header {
            background: #000;
            color: #FFD600;
            padding: 10px 15px;
            font-size: 12pt;
            font-weight: bold;
            margin: 25px 0 15px 0;
            border-radius: 5px;
        }
        .section-icon {
            margin-right: 8px;
        }
        
        /* Syllabus Items */
        .syllabus-item {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-left: 4px solid #FFD600;
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 0 5px 5px 0;
        }
        .syllabus-topic {
            font-weight: bold;
            color: #000;
            font-size: 11pt;
            margin-bottom: 3px;
        }
        .syllabus-desc {
            color: #555;
            font-size: 9pt;
        }
        
        /* Dress Code Box */
        .dress-code-box {
            background: #FFF8E1;
            border: 2px solid #FFC107;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .dress-code-box h4 {
            color: #E65100;
            margin-bottom: 8px;
            font-size: 11pt;
        }
        .dress-code-box p {
            color: #5D4037;
            font-size: 10pt;
        }
        
        /* Rules Box */
        .rules-box {
            background: #FFEBEE;
            border: 2px solid #EF5350;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .rules-box h4 {
            color: #C62828;
            margin-bottom: 10px;
            font-size: 11pt;
        }
        .rules-list {
            margin-left: 20px;
            color: #5D4037;
            font-size: 9pt;
        }
        .rules-list li {
            margin-bottom: 5px;
        }
        
        /* Center Info Box */
        .center-box {
            background: #E3F2FD;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .center-box h4 {
            color: #1565C0;
            margin-bottom: 10px;
            font-size: 12pt;
        }
        .center-detail {
            margin-bottom: 6px;
            font-size: 10pt;
            color: #1A237E;
        }
        .center-detail strong {
            color: #000;
        }
        
        /* Getting Started Steps */
        .step-item {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        .step-number {
            display: table-cell;
            width: 35px;
            vertical-align: top;
        }
        .step-circle {
            background: #FFD600;
            color: #000;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-weight: bold;
            font-size: 12pt;
        }
        .step-content {
            display: table-cell;
            vertical-align: top;
            padding-left: 10px;
        }
        .step-title {
            font-weight: bold;
            color: #000;
            font-size: 10pt;
        }
        .step-desc {
            color: #666;
            font-size: 9pt;
        }
        
        /* ===== FOOTER ===== */
        .footer {
            background: #FFD600;
            padding: 15px 30px;
            border-top: 3px solid #000;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }
        .footer-content {
            display: table;
            width: 100%;
        }
        .footer-social {
            display: table-cell;
            vertical-align: middle;
            font-size: 9pt;
            color: #000;
        }
        .social-item {
            display: inline-block;
            margin-right: 20px;
        }
        .footer-motto {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            font-style: italic;
            color: #C00;
            font-size: 9pt;
        }
        
        /* Utility */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-content">
            <div class="header-logo">
                <div style="background:#000;color:#FFD600;font-size:24pt;font-weight:bold;width:70px;height:70px;text-align:center;line-height:70px;border-radius:10px;">HQ</div>
            </div>
            <div class="header-text">
                <div class="company-name"><span class="hq-badge">HQ</span>HIGH-Q SOLID ACADEMY</div>
                <div class="motto">Motto: Always Ahead of Others</div>
                <div class="contact-info">Shop 18, World Star Complex, Ayetoro Maya, Ikorodu Lagos | {$escapedPhone}</div>
            </div>
        </div>
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
            <p style="margin-top:10px;">You're now enrolled in our <strong>{$escapedTitle}</strong> program. Our expert team is ready to help you succeed, and this welcome kit has everything you need to hit the ground running.</p>
            <p style="margin-top:10px;font-style:italic;color:#888;">Think of this document as your "get started in 5 minutes" guide. Keep it handy!</p>
        </div>
        
        <!-- Student Info Card -->
        <div class="student-card">
            <div class="student-card-title">Your Registration Details</div>
            <div class="student-info-row"><strong>Registration ID:</strong> {$registrationId}</div>
            <div class="student-info-row"><strong>Program:</strong> {$escapedTitle}</div>
            <div class="student-info-row"><strong>Email:</strong> {$escapedEmail}</div>
        </div>
        
        <!-- Syllabus Section -->
        <div class="section-header"><span class="section-icon">&#128218;</span> Program Syllabus & Learning Topics</div>
        <p style="margin-bottom:15px;color:#555;font-size:10pt;">Your program covers the following core topics:</p>
        {$syllabusHtml}
        
        <!-- Dress Code Section -->
        <div class="section-header"><span class="section-icon">&#128084;</span> Dress Code & Appearance</div>
        <div class="dress-code-box">
            <h4>Required Dress Code:</h4>
            <p>{$content['dressCode']}</p>
        </div>
        
        <!-- Rules Section -->
        <div class="rules-box">
            <h4>Important Center Rules</h4>
            <ul class="rules-list">
                <li>Arrive 10 minutes early to class</li>
                <li>Keep your mobile phone on silent mode</li>
                <li>No eating in class (water bottle allowed)</li>
                <li>Maintain professional behavior at all times</li>
                <li>Inform instructors in advance if you'll miss a class</li>
                <li>Respect all center facilities and equipment</li>
                <li>No photography without permission</li>
                <li>Participate actively in all lessons</li>
            </ul>
        </div>
        
        <!-- Center Info Section -->
        <div class="section-header"><span class="section-icon">&#128205;</span> Center Location & Hours</div>
        <div class="center-box">
            <h4>{$content['center']['name']}</h4>
            <div class="center-detail"><strong>Address:</strong> {$content['center']['address']}</div>
            <div class="center-detail"><strong>Phone:</strong> {$content['center']['phone']}</div>
            <div class="center-detail"><strong>Hours:</strong> {$content['center']['hours']}</div>
        </div>
        
        <!-- Getting Started Section -->
        <div class="section-header"><span class="section-icon">&#128640;</span> Getting Started - Your Next Steps</div>
        
        <div class="step-item">
            <div class="step-number"><div class="step-circle">1</div></div>
            <div class="step-content">
                <div class="step-title">Review this Welcome Kit</div>
                <div class="step-desc">Read through all sections to understand what to expect</div>
            </div>
        </div>
        
        <div class="step-item">
            <div class="step-number"><div class="step-circle">2</div></div>
            <div class="step-content">
                <div class="step-title">Prepare Your Materials</div>
                <div class="step-desc">Get notepads, pens, and any required textbooks</div>
            </div>
        </div>
        
        <div class="step-item">
            <div class="step-number"><div class="step-circle">3</div></div>
            <div class="step-content">
                <div class="step-title">Attend Your First Class</div>
                <div class="step-desc">Show up early, dress according to the code, and come ready to learn</div>
            </div>
        </div>
        
        <div class="step-item">
            <div class="step-number"><div class="step-circle">4</div></div>
            <div class="step-content">
                <div class="step-title">Stay Connected</div>
                <div class="step-desc">Check emails regularly for updates and announcements</div>
            </div>
        </div>
        
        <p style="text-align:center;margin-top:30px;font-size:10pt;color:#666;">Generated on: {$generatedDate}</p>
    </div>
    
    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-social">
                <span class="social-item">f Highqsolidacademy</span>
                <span class="social-item">@ highqsolidacademy</span>
                <span class="social-item">{$escapedContactEmail}</span>
            </div>
            <div class="footer-motto">"Always Ahead of Others"</div>
        </div>
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
 * Send welcome kit email to student
 * Uses company brand colors and professional styling
 */
function sendWelcomeKitEmail($studentEmail, $studentName, $programType, $registrationId, $pdfPath) {
    global $pdo;
    
    $siteSettings = getSiteSettings();
    $contactPhone = $siteSettings['contact_phone'] ?? '0807 208 8794';
    $contactEmail = $siteSettings['contact_email'] ?? 'highqsolidacademy@gmail.com';
    
    // Escape values
    $escapedName = htmlspecialchars($studentName);
    $escapedProgram = htmlspecialchars(ucfirst($programType));
    $escapedPhone = htmlspecialchars($contactPhone);
    $escapedEmail = htmlspecialchars($contactEmail);
    $currentYear = date('Y');

    // Professional subject line without emojis (emojis don't render properly in some email clients)
    $subject = "Your Welcome Kit - High-Q Registration #{$registrationId}";
    
    // Styled email body using company brand colors
    $message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f4f4;">
        <tr>
            <td align="center" style="padding:30px 10px;">
                <!-- Main Container -->
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
                    
                    <!-- Header with Yellow Background -->
                    <tr>
                        <td style="background:#FFD600;padding:30px;text-align:center;border-bottom:4px solid #000000;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center">
                                        <!-- HQ Logo Box -->
                                        <div style="background:#000000;color:#FFD600;font-size:28px;font-weight:bold;width:60px;height:60px;line-height:60px;border-radius:10px;display:inline-block;margin-bottom:10px;">HQ</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top:10px;">
                                        <h1 style="margin:0;color:#000000;font-size:24px;font-weight:bold;">Welcome to HIGH-Q!</h1>
                                        <p style="margin:5px 0 0;color:#333333;font-size:14px;">Your journey to success starts NOW</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Body Content -->
                    <tr>
                        <td style="padding:35px 30px;">
                            <p style="margin:0 0 20px;color:#222;font-size:16px;">Hey <strong>{$escapedName}</strong>,</p>
                            
                            <p style="margin:0 0 20px;color:#444;font-size:15px;line-height:1.6;">
                                Congratulations on taking this amazing step! You've just joined High-Q Solid Academy, and we're absolutely thrilled to have you as part of our learning family.
                            </p>
                            
                            <!-- Attachment Notice Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:25px 0;">
                                <tr>
                                    <td style="background:#FFFDE7;border-left:5px solid #FFD600;padding:20px;border-radius:0 8px 8px 0;">
                                        <p style="margin:0;color:#000;font-size:15px;font-weight:bold;">Attached: Your Personalized Welcome Kit (PDF)</p>
                                        <p style="margin:8px 0 0;color:#555;font-size:13px;">Everything you need to succeed in one document!</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin:0 0 10px;color:#222;font-size:15px;font-weight:bold;">Your Welcome Kit Includes:</p>
                            <ul style="margin:0 0 25px;padding-left:25px;color:#444;font-size:14px;line-height:1.8;">
                                <li><strong>Syllabus:</strong> Exactly what you'll learn and master</li>
                                <li><strong>Dress Code:</strong> How to dress for success</li>
                                <li><strong>Center Details:</strong> Location, hours, and getting there</li>
                                <li><strong>Center Rules:</strong> The simple guidelines for awesomeness</li>
                                <li><strong>Getting Started:</strong> 4 easy steps to rock your first day</li>
                            </ul>
                            
                            <!-- Pro Tip Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;">
                                <tr>
                                    <td style="background:#FFF8E1;border-left:5px solid #FFC107;padding:15px 20px;border-radius:0 8px 8px 0;">
                                        <p style="margin:0;color:#E65100;font-size:14px;"><strong>Pro Tip:</strong> Download and save the PDF. You'll want to reference it before your first class!</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Registration Info Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:25px 0;">
                                <tr>
                                    <td style="background:#f8f9fa;border:2px solid #e9ecef;padding:20px;border-radius:8px;">
                                        <p style="margin:0 0 5px;color:#666;font-size:11px;text-transform:uppercase;letter-spacing:1px;">Your Registration Confirmation</p>
                                        <p style="margin:8px 0;color:#222;font-size:14px;"><strong>Registration ID:</strong> {$registrationId}</p>
                                        <p style="margin:8px 0;color:#222;font-size:14px;"><strong>Program:</strong> {$escapedProgram} Program</p>
                                        <p style="margin:8px 0 0;color:#222;font-size:14px;"><strong>Email:</strong> {$studentEmail}</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin:25px 0 10px;color:#222;font-size:15px;font-weight:bold;">What's Next?</p>
                            <ol style="margin:0 0 25px;padding-left:25px;color:#444;font-size:14px;line-height:1.8;">
                                <li>Download and review your Welcome Kit (attached)</li>
                                <li>Prepare the right clothes based on dress code</li>
                                <li>Mark our center location on your map</li>
                                <li>Show up ready to learn and grow!</li>
                            </ol>
                            
                            <!-- Help Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;">
                                <tr>
                                    <td style="background:#E3F2FD;border-left:5px solid #2196F3;padding:15px 20px;border-radius:0 8px 8px 0;">
                                        <p style="margin:0;color:#1565C0;font-size:14px;"><strong>Questions?</strong> We're here to help!</p>
                                        <p style="margin:8px 0 0;color:#1A237E;font-size:13px;">Call us at <strong>{$escapedPhone}</strong> or reply to this email anytime.</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin:30px 0 0;text-align:center;color:#000;font-size:16px;font-weight:bold;">
                                You've got this! Let's make magic happen together.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer with Yellow Background -->
                    <tr>
                        <td style="background:#FFD600;padding:25px 30px;border-top:3px solid #000000;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center">
                                        <p style="margin:0 0 10px;color:#000;font-size:14px;font-weight:bold;">HIGH-Q SOLID ACADEMY</p>
                                        <p style="margin:0 0 8px;color:#333;font-size:12px;">f Highqsolidacademy | @ highqsolidacademy</p>
                                        <p style="margin:0 0 8px;color:#333;font-size:12px;">{$escapedPhone} | {$escapedEmail}</p>
                                        <p style="margin:15px 0 0;color:#C00;font-size:11px;font-style:italic;">"Always Ahead of Others"</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Copyright -->
                    <tr>
                        <td style="background:#000000;padding:15px;text-align:center;">
                            <p style="margin:0;color:#999;font-size:11px;">&copy; {$currentYear} High-Q Solid Academy. All rights reserved.</p>
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

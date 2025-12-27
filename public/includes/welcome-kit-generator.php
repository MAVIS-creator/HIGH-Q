<?php
/**
 * Welcome Kit Generator
 * Generates a PDF welcome kit with syllabus, dress code/rules, and center map
 * Triggered automatically after student payment confirmation
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generateWelcomeKitPDF($programType, $studentName, $studentEmail, $registrationId) {
    global $pdo;

    // Get site settings for contact info
    $siteSettings = getSiteSettings();
    
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
                'address' => 'Lagos, Nigeria',
                'phone' => $siteSettings['contact_phone'] ?? '0807 208 8794',
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
                'address' => 'Lagos, Nigeria',
                'phone' => $siteSettings['contact_phone'] ?? '0807 208 8794',
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
                'address' => 'Lagos, Nigeria',
                'phone' => $siteSettings['contact_phone'] ?? '0807 208 8794',
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
                'address' => 'Lagos, Nigeria',
                'phone' => $siteSettings['contact_phone'] ?? '0807 208 8794',
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
                'address' => 'Lagos, Nigeria',
                'phone' => $siteSettings['contact_phone'] ?? '0807 208 8794',
                'hours' => 'Monday - Friday: 3:00 PM - 6:00 PM, Saturday & Sunday: 10:00 AM - 3:00 PM'
            ]
        ]
    ];

    $content = $programContent[$programType] ?? $programContent['jamb'];

    // Generate HTML for PDF
    $html = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: Arial, sans-serif;
                color: #333;
                line-height: 1.6;
            }
            .container {
                max-width: 8.5in;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
                color: white;
                padding: 30px;
                border-radius: 10px;
                text-align: center;
                margin-bottom: 30px;
            }
            .header h1 {
                font-size: 28px;
                margin-bottom: 10px;
            }
            .header p {
                font-size: 14px;
                opacity: 0.95;
            }
            .welcome-section {
                background: #f0f9ff;
                border-left: 4px solid #06b6d4;
                padding: 20px;
                margin-bottom: 30px;
                border-radius: 5px;
            }
            .welcome-section h2 {
                color: #06b6d4;
                margin-bottom: 10px;
                font-size: 18px;
            }
            .student-info {
                background: white;
                border: 2px solid #e2e8f0;
                padding: 15px;
                margin-bottom: 30px;
                border-radius: 5px;
            }
            .student-info strong {
                display: block;
                margin-bottom: 5px;
            }
            h3 {
                color: #4f46e5;
                margin-top: 25px;
                margin-bottom: 15px;
                font-size: 16px;
                border-bottom: 2px solid #4f46e5;
                padding-bottom: 10px;
            }
            .syllabus-item {
                margin-bottom: 12px;
                padding: 10px;
                background: #f8fafc;
                border-radius: 5px;
            }
            .syllabus-item strong {
                color: #4f46e5;
                display: block;
                margin-bottom: 5px;
            }
            .dress-code {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 5px;
            }
            .center-info {
                background: #dbeafe;
                border-left: 4px solid #3b82f6;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 5px;
            }
            .center-info h4 {
                color: #1e40af;
                margin-bottom: 10px;
            }
            .center-info p {
                margin-bottom: 8px;
                color: #1e3a8a;
            }
            .important-rules {
                background: #fee2e2;
                border-left: 4px solid #dc2626;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 5px;
            }
            .important-rules h4 {
                color: #991b1b;
                margin-bottom: 10px;
            }
            .important-rules ul {
                margin-left: 20px;
            }
            .important-rules li {
                margin-bottom: 8px;
                color: #7f1d1d;
            }
            .footer {
                border-top: 2px solid #e2e8f0;
                padding-top: 20px;
                text-align: center;
                font-size: 12px;
                color: #64748b;
                margin-top: 30px;
            }
            .page-break {
                page-break-after: always;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1>🎓 Welcome to High-Q!</h1>
                <p>Your Success Is Our Priority</p>
            </div>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2>✨ Hey {$studentName}, We're So Excited!</h2>
                <p>You just took a BIG step toward your goals, and we're honored to be part of your journey! 🚀</p>
                <p style="margin-top: 12px;">You're now enrolled in our <strong>{$content['title']}</strong> program, and we promise you're in the right place. Our expert team is ready to help you succeed, and this welcome kit has everything you need to hit the ground running.</p>
                <p style="margin-top: 12px; font-size: 14px; color: #666;"><em>Think of this document as your "get started in 5 minutes" guide. Keep it handy!</em></p>
            </div>

            <!-- Student Info -->
            <div class="student-info">
                <strong>Registration ID: {$registrationId}</strong>
                <strong>Program: {$content['title']}</strong>
                <strong>Email: {$studentEmail}</strong>
            </div>

            <!-- Syllabus Section -->
            <h3>📚 Program Syllabus & Learning Topics</h3>
            <p>Your program covers the following core topics:</p>
HTML;

    foreach ($content['syllabus'] as $topic => $description) {
        $html .= <<<HTML
            <div class="syllabus-item">
                <strong>{$topic}</strong>
                {$description}
            </div>
HTML;
    }

    $html .= <<<HTML
            <!-- Dress Code Section -->
            <h3>👔 Dress Code & Center Rules</h3>
            <div class="dress-code">
                <strong>Required Dress Code:</strong><br>
                {$content['dressCode']}
            </div>

            <div class="important-rules">
                <h4>⚠️ Important Center Rules</h4>
                <ul>
                    <li>Arrive 10 minutes early to class</li>
                    <li>Keep your mobile phone on silent mode</li>
                    <li>No eating or drinking in class (water bottle allowed)</li>
                    <li>Maintain professional behavior at all times</li>
                    <li>Inform instructors in advance if you'll miss a class</li>
                    <li>Respect all center facilities and equipment</li>
                    <li>No photography without permission</li>
                    <li>Participate actively in all lessons</li>
                </ul>
            </div>

            <!-- Center Information -->
            <h3>📍 Center Location & Hours</h3>
            <div class="center-info">
                <h4>{$content['center']['name']}</h4>
                <p><strong>Address:</strong> {$content['center']['address']}</p>
                <p><strong>Phone:</strong> {$content['center']['phone']}</p>
                <p><strong>Hours:</strong> {$content['center']['hours']}</p>
            </div>

            <!-- Getting Started -->
            <h3>🚀 Getting Started</h3>
            <p>Here are your next steps:</p>
            <div class="syllabus-item">
                <strong>1. Review this Welcome Kit</strong>
                Read through all sections to understand what to expect
            </div>
            <div class="syllabus-item">
                <strong>2. Prepare Your Materials</strong>
                Get notepads, pens, and any required textbooks mentioned by instructors
            </div>
            <div class="syllabus-item">
                <strong>3. Attend Your First Class</strong>
                Show up early, dress according to the code, and come ready to learn
            </div>
            <div class="syllabus-item">
                <strong>4. Stay Connected</strong>
                Check emails regularly for updates, resources, and announcements
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>Questions? Contact us at {$content['center']['phone']} or visit our center.</p>
                <p style="margin-top: 10px;">Generated on: " . date('Y-m-d H:i:s') . "</p>
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
 */
function sendWelcomeKitEmail($studentEmail, $studentName, $programType, $registrationId, $pdfPath) {
    global $pdo;
    
    $siteSettings = getSiteSettings();
    $senderEmail = $siteSettings['contact_email'] ?? 'noreply@highq.com';
    $senderName = 'High-Q Learning Center';

    $subject = "🎓 Your Welcome Kit - High-Q Registration #{$registrationId}";
    
    $message = <<<HTML
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                color: #333;
                line-height: 1.6;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
            }
            .header {
                background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 5px;
            }
            .content {
                padding: 20px;
                background: #f8fafc;
            }
            .button {
                display: inline-block;
                background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 15px;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #64748b;
                margin-top: 20px;
                padding: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Welcome to High-Q! 🎓</h2>
            </div>
            
            <div class="content">
                <p>Hi <strong>{$studentName}</strong>,</p>
                
                <p>Thank you for registering with High-Q! We're excited to have you join our learning community.</p>
                
                <p>Attached to this email is your <strong>Welcome Kit PDF</strong> containing:</p>
                <ul>
                    <li>✓ Program syllabus and learning topics</li>
                    <li>✓ Dress code and center rules</li>
                    <li>✓ Center location and operating hours</li>
                    <li>✓ Getting started guide</li>
                </ul>
                
                <p><strong>Your Registration Details:</strong><br>
                Registration ID: {$registrationId}<br>
                Program: " . ucfirst($programType) . " Program<br>
                Confirmation Email: {$studentEmail}
                </p>
                
                <p>Please review the welcome kit carefully and familiarize yourself with the center rules and dress code before your first class.</p>
                
                <p style="margin-top: 20px; color: #64748b; font-size: 14px;">
                    If you have any questions, please don't hesitate to contact us at {$siteSettings['contact_phone']} or reply to this email.
                </p>
            </div>
            
            <div class="footer">
                <p>&copy; " . date('Y') . " High-Q Learning Center. All rights reserved.</p>
                <p>{$senderName}<br>{$siteSettings['contact_phone']}</p>
            </div>
        </div>
    </body>
    </html>
HTML;

    try {
        // Prepare email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$senderName} <{$senderEmail}>\r\n";
        
        // Send email with PDF attachment
        if (file_exists($pdfPath)) {
            // Read PDF file
            $file_content = chunk_split(base64_encode(file_get_contents($pdfPath)));
            $boundary = md5(time());
            
            // Build multipart message
            $headers = "From: {$senderName} <{$senderEmail}>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
            
            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $message . "\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: application/pdf; name=\"" . basename($pdfPath) . "\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"" . basename($pdfPath) . "\"\r\n\r\n";
            $body .= $file_content . "\r\n";
            $body .= "--{$boundary}--";
            
            return mail($studentEmail, $subject, $body, $headers);
        } else {
            // Send without attachment if file doesn't exist
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$senderName} <{$senderEmail}>\r\n";
            return mail($studentEmail, $subject, $message, $headers);
        }
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

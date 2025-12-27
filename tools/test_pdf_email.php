<?php
/**
 * Test Script for Admission Letter PDF & Welcome Kit Email
 * Run this file directly to test PDF generation and email sending
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load dependencies
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/config/db.php';
require_once __DIR__ . '/../public/config/functions.php';

echo "===========================================\n";
echo "  HIGH Q - PDF & Email Testing Tool\n";
echo "===========================================\n\n";

$testEmail = 'akintunde.dolapo1@gmail.com';
$companyEmail = 'highqsolidacademy@gmail.com';

// ============================================
// TEST 1: Check Dompdf
// ============================================
echo "1. Testing Dompdf availability...\n";
if (class_exists('Dompdf\Dompdf')) {
    echo "   ✓ Dompdf is available\n";
} else {
    echo "   ✗ Dompdf NOT found!\n";
    exit(1);
}

// ============================================
// TEST 2: Generate Test PDF
// ============================================
echo "\n2. Testing PDF Generation...\n";

try {
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new \Dompdf\Dompdf($options);
    
    $testHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body { font-family: Arial, sans-serif; }
        .header {
            background: #FFD600;
            padding: 15px 30px;
            border-bottom: 3px solid #000;
        }
        .company-name {
            font-size: 20pt;
            font-weight: bold;
        }
        .hq-box {
            background: #000;
            color: #FFD600;
            padding: 2px 8px;
            margin-right: 5px;
        }
        .content { padding: 40px; }
        .footer {
            background: #FFD600;
            padding: 12px 30px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name"><span class="hq-box">HQ</span> HIGH - Q SOLID ACADEMY</div>
        <div style="color: red; font-style: italic;">Motto: Always Ahead of Others</div>
    </div>
    <div class="content">
        <h2 style="text-align: center; text-decoration: underline;">TEST ADMISSION LETTER</h2>
        <p><strong>Date:</strong> ' . date('F j, Y') . '</p>
        <p>Dear <strong>Test Student</strong>,</p>
        <p>This is a test PDF to verify the letterhead design works correctly.</p>
        <p>The header should be yellow with black text, and the footer should also be yellow.</p>
        <br><br>
        <p>______________________________</p>
        <p><strong>Admissions Office</strong></p>
    </div>
    <div class="footer">
        <div>f Highqsolidacademy</div>
        <div>@ highqsolidacademy</div>
        <div>✉ highqsolidacademy@gmail.com</div>
    </div>
</body>
</html>';
    
    $dompdf->loadHtml($testHtml);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Save to file
    $outputPath = __DIR__ . '/../storage/test-admission-letter.pdf';
    file_put_contents($outputPath, $dompdf->output());
    
    echo "   ✓ PDF generated successfully!\n";
    echo "   ✓ Saved to: " . realpath($outputPath) . "\n";
    
} catch (Exception $e) {
    echo "   ✗ PDF Error: " . $e->getMessage() . "\n";
}

// ============================================
// TEST 3: Test Email Sending
// ============================================
echo "\n3. Testing Email Sending...\n";

if (function_exists('sendEmail')) {
    echo "   ✓ sendEmail() function exists\n";
    
    $subject = "HIGH Q Test - " . date('Y-m-d H:i:s');
    $body = '
    <html>
    <body style="font-family: Arial, sans-serif; padding: 20px;">
        <div style="background: #FFD600; padding: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0;">HIGH Q SOLID ACADEMY - Test Email</h2>
        </div>
        <p>This is a test email sent at: <strong>' . date('Y-m-d H:i:s') . '</strong></p>
        <p>If you receive this, the email system is working correctly!</p>
        <hr>
        <p style="color: #666; font-size: 12px;">Automated test from test_pdf_email.php</p>
    </body>
    </html>';
    
    echo "   → Sending test email to: $testEmail\n";
    
    try {
        $result = sendEmail($testEmail, $subject, $body);
        if ($result) {
            echo "   ✓ Email sent successfully!\n";
        } else {
            echo "   ✗ sendEmail() returned false\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Email Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ sendEmail() function NOT found!\n";
}

// ============================================
// TEST 4: Test Welcome Kit Generator
// ============================================
echo "\n4. Testing Welcome Kit Generator...\n";

$welcomeKitPath = __DIR__ . '/../public/includes/welcome-kit-generator.php';
if (file_exists($welcomeKitPath)) {
    echo "   ✓ Welcome kit file exists\n";
    
    // Include it
    require_once $welcomeKitPath;
    
    if (function_exists('generateWelcomeKitPDF')) {
        echo "   ✓ generateWelcomeKitPDF() function exists\n";
        
        echo "   → Generating welcome kit for 'jamb' program...\n";
        
        try {
            $result = generateWelcomeKitPDF('jamb', 'Test Student', $testEmail, 999);
            
            if ($result) {
                echo "   ✓ Welcome kit generated!\n";
                if (is_array($result) && isset($result['filepath'])) {
                    echo "   ✓ PDF Path: " . $result['filepath'] . "\n";
                    
                    // Now test sending the welcome kit email
                    if (function_exists('sendWelcomeKitEmail')) {
                        echo "\n   → Sending welcome kit email with PDF attachment...\n";
                        $emailResult = sendWelcomeKitEmail($testEmail, 'Test Student', 'jamb', 999, $result['filepath']);
                        if ($emailResult) {
                            echo "   ✓ Welcome kit email SENT with PDF attachment!\n";
                        } else {
                            echo "   ✗ Welcome kit email failed to send\n";
                        }
                    }
                }
            } else {
                echo "   ✗ generateWelcomeKitPDF returned false/null\n";
            }
        } catch (Exception $e) {
            echo "   ✗ Welcome Kit Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✗ generateWelcomeKitPDF() function NOT found\n";
    }
    
    if (function_exists('sendWelcomeKitEmail')) {
        echo "   ✓ sendWelcomeKitEmail() function exists\n";
    } else {
        echo "   ℹ sendWelcomeKitEmail() may be integrated in main function\n";
    }
} else {
    echo "   ✗ Welcome kit file NOT found at: $welcomeKitPath\n";
}

// ============================================
// TEST 5: Database Check
// ============================================
echo "\n5. Checking Database for test data...\n";

try {
    // Check for registrations
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM student_registrations");
    $regCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "   ✓ Found $regCount student registration(s)\n";
    
    if ($regCount > 0) {
        $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM student_registrations ORDER BY id DESC LIMIT 1");
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✓ Latest: #{$reg['id']} - {$reg['first_name']} {$reg['last_name']} ({$reg['email']})\n";
        echo "\n   → Test admission letter URL:\n";
        echo "     http://localhost/HIGH-Q/public/admission_letter.php?rid={$reg['id']}&format=pdf\n";
    }
    
    // Check for payments
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM payments");
    $payCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "   ✓ Found $payCount payment(s)\n";
    
} catch (Exception $e) {
    echo "   ✗ Database Error: " . $e->getMessage() . "\n";
}

echo "\n===========================================\n";
echo "  Testing Complete!\n";
echo "===========================================\n";
echo "\nCheck:\n";
echo "1. storage/test-admission-letter.pdf for the generated PDF\n";
echo "2. Your email inbox at $testEmail for test emails\n";
echo "3. Browser at http://localhost/HIGH-Q/public/contact.php for FAQ test\n";
?>

<?php
/**
 * Test Script for Admission Letter & Welcome Kit Generators
 * Tests both PDF generation and email sending functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../public/config/db.php';
require_once __DIR__ . '/../public/config/functions.php';

echo "<h1>Testing Admission Letter & Welcome Kit Generators</h1>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
.test-section { margin: 20px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; }
.success { color: #16a34a; background: #dcfce7; padding: 10px; border-radius: 4px; }
.error { color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 4px; }
.info { color: #2563eb; background: #dbeafe; padding: 10px; border-radius: 4px; }
h2 { margin-top: 30px; color: #333; }
pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; }
</style>";

// Test email to use
$testEmail = 'akintunde.dolapo1@gmail.com';

// ============================================
// PART 1: Test Admission Letter Generator
// ============================================
echo "<h2>1. Testing Admission Letter Generator</h2>";

echo "<div class='test-section'>";
echo "<h3>1.1 Finding a test registration...</h3>";

try {
    // Find a registration to test with
    $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM student_registrations ORDER BY id DESC LIMIT 1");
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reg) {
        echo "<p class='success'>✓ Found registration: ID #{$reg['id']} - {$reg['first_name']} {$reg['last_name']} ({$reg['email']})</p>";
        
        $testRid = $reg['id'];
        
        // Test HTML rendering
        echo "<h3>1.2 Testing HTML rendering...</h3>";
        $admissionUrl = "http://localhost/HIGH-Q/public/admission_letter.php?rid={$testRid}";
        echo "<p class='info'>Admission Letter URL: <a href='{$admissionUrl}' target='_blank'>{$admissionUrl}</a></p>";
        
        // Test PDF rendering
        echo "<h3>1.3 Testing PDF download...</h3>";
        $pdfUrl = "http://localhost/HIGH-Q/public/admission_letter.php?rid={$testRid}&format=pdf";
        echo "<p class='info'>PDF URL: <a href='{$pdfUrl}' target='_blank'>{$pdfUrl}</a></p>";
        
        // Check if Dompdf is available
        echo "<h3>1.4 Checking Dompdf availability...</h3>";
        if (class_exists('Dompdf\Dompdf')) {
            echo "<p class='success'>✓ Dompdf is available</p>";
        } else {
            // Try to load it
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
                if (class_exists('Dompdf\Dompdf')) {
                    echo "<p class='success'>✓ Dompdf loaded from vendor/autoload.php</p>";
                } else {
                    echo "<p class='error'>✗ Dompdf not found even after loading autoload.php</p>";
                }
            } else {
                echo "<p class='error'>✗ vendor/autoload.php not found</p>";
            }
        }
        
    } else {
        echo "<p class='error'>✗ No registrations found in database. Please create a test registration first.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ============================================
// PART 2: Test Welcome Kit Generator
// ============================================
echo "<h2>2. Testing Welcome Kit Generator</h2>";

echo "<div class='test-section'>";

echo "<h3>2.1 Loading Welcome Kit Generator...</h3>";
$welcomeKitPath = __DIR__ . '/../public/includes/welcome-kit-generator.php';
if (file_exists($welcomeKitPath)) {
    echo "<p class='success'>✓ Welcome kit generator file exists</p>";
    
    // Include it
    require_once $welcomeKitPath;
    
    // Check if function exists
    if (function_exists('generateWelcomeKitPDF')) {
        echo "<p class='success'>✓ generateWelcomeKitPDF() function is available</p>";
        
        echo "<h3>2.2 Testing PDF Generation...</h3>";
        
        // Test with different program types
        $programTypes = ['jamb', 'waec', 'postutme', 'digital'];
        $testProgramType = 'jamb'; // Default for testing
        
        echo "<p class='info'>Testing with program type: <strong>{$testProgramType}</strong></p>";
        echo "<p class='info'>Test email: <strong>{$testEmail}</strong></p>";
        
        // Generate PDF
        try {
            $result = generateWelcomeKitPDF(
                $testProgramType,
                'Test Student',
                $testEmail,
                $reg['id'] ?? 1
            );
            
            if ($result) {
                echo "<p class='success'>✓ Welcome kit generated and email sent successfully!</p>";
                echo "<p class='info'>📧 Check your email at <strong>{$testEmail}</strong> for the welcome kit PDF.</p>";
            } else {
                echo "<p class='error'>✗ Failed to generate/send welcome kit</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error generating welcome kit: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else {
        echo "<p class='error'>✗ generateWelcomeKitPDF() function not found</p>";
    }
    
    // Check if sendWelcomeKitEmail function exists
    if (function_exists('sendWelcomeKitEmail')) {
        echo "<p class='success'>✓ sendWelcomeKitEmail() function is available</p>";
    } else {
        echo "<p class='info'>ℹ sendWelcomeKitEmail() function not found (may be integrated into main function)</p>";
    }
    
} else {
    echo "<p class='error'>✗ Welcome kit generator file not found at: {$welcomeKitPath}</p>";
}
echo "</div>";

// ============================================
// PART 3: Test Direct Email Sending
// ============================================
echo "<h2>3. Testing Direct Email Functionality</h2>";

echo "<div class='test-section'>";
echo "<h3>3.1 Testing sendEmail() function...</h3>";

if (function_exists('sendEmail')) {
    echo "<p class='success'>✓ sendEmail() function is available</p>";
    
    // Send a test email
    $testSubject = "HIGH Q Test Email - " . date('Y-m-d H:i:s');
    $testBody = "
    <html>
    <body style='font-family: Arial, sans-serif; padding: 20px;'>
        <h2 style='color: #4f46e5;'>🎓 HIGH Q SOLID ACADEMY - Test Email</h2>
        <p>This is a test email to verify the email system is working correctly.</p>
        <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Purpose:</strong> Testing admission letter and welcome kit email delivery</p>
        <hr>
        <p style='color: #666; font-size: 12px;'>This is an automated test. No action required.</p>
    </body>
    </html>";
    
    echo "<p class='info'>Sending test email to: {$testEmail}</p>";
    
    try {
        $sent = sendEmail($testEmail, $testSubject, $testBody);
        if ($sent) {
            echo "<p class='success'>✓ Test email sent successfully! Check your inbox.</p>";
        } else {
            echo "<p class='error'>✗ Failed to send test email</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Email error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error'>✗ sendEmail() function not found</p>";
}
echo "</div>";

// ============================================
// PART 4: Quick Links for Manual Testing
// ============================================
echo "<h2>4. Quick Test Links</h2>";

echo "<div class='test-section'>";
echo "<p><strong>Manual Test URLs:</strong></p>";
echo "<ul>";

if (isset($testRid)) {
    echo "<li><a href='http://localhost/HIGH-Q/public/admission_letter.php?rid={$testRid}' target='_blank'>View Admission Letter (HTML)</a></li>";
    echo "<li><a href='http://localhost/HIGH-Q/public/admission_letter.php?rid={$testRid}&format=pdf' target='_blank'>Download Admission Letter (PDF)</a></li>";
}

echo "<li><a href='http://localhost/HIGH-Q/public/receipt.php' target='_blank'>View Receipt Page (needs payment ref)</a></li>";
echo "<li><a href='http://localhost/HIGH-Q/public/contact.php' target='_blank'>Test FAQ Read More (Contact Page)</a></li>";
echo "</ul>";
echo "</div>";

echo "<h2>✅ Testing Complete</h2>";
echo "<p>Please check:</p>";
echo "<ol>";
echo "<li>Your email inbox at <strong>{$testEmail}</strong> for test emails</li>";
echo "<li>The PDF download links above work correctly</li>";
echo "<li>The FAQ read more on contact.php expands properly on mobile</li>";
echo "</ol>";
?>

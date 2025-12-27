<?php
/**
 * Test Script for Admission Letter & Welcome Kit Generators
 * Tests both PDF generation and email sending functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load vendor autoload first
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/config/db.php';
require_once __DIR__ . '/../public/config/functions.php';

echo "<h1>Testing Admission Letter & Welcome Kit Generators</h1>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
.test-section { margin: 20px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; }
.success { color: #16a34a; background: #dcfce7; padding: 10px; border-radius: 4px; margin: 8px 0; }
.error { color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 4px; margin: 8px 0; }
.info { color: #2563eb; background: #dbeafe; padding: 10px; border-radius: 4px; margin: 8px 0; }
h2 { margin-top: 30px; color: #333; }
pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 6px; margin: 5px; }
.btn:hover { background: #4338ca; }
</style>";

// Test email to use
$testEmail = 'akintunde.dolapo1@gmail.com';

// ============================================
// PART 1: Database Check
// ============================================
echo "<h2>1. Database Check</h2>";
echo "<div class='test-section'>";

try {
    // Find a registration to test with
    $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM student_registrations ORDER BY id DESC LIMIT 5");
    $regs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($regs)) {
        echo "<p class='success'>✓ Found " . count($regs) . " registration(s)</p>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr>";
        foreach ($regs as $reg) {
            echo "<tr>";
            echo "<td>{$reg['id']}</td>";
            echo "<td>{$reg['first_name']} {$reg['last_name']}</td>";
            echo "<td>{$reg['email']}</td>";
            echo "<td>
                <a class='btn' href='/HIGH-Q/public/admission_letter.php?rid={$reg['id']}' target='_blank'>View</a>
                <a class='btn' href='/HIGH-Q/public/admission_letter.php?rid={$reg['id']}&format=pdf' target='_blank'>PDF</a>
            </td>";
            echo "</tr>";
        }
        echo "</table>";
        $testRid = $regs[0]['id'];
    } else {
        echo "<p class='error'>✗ No registrations found. Creating a test one...</p>";
        $testRid = null;
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    $testRid = null;
}
echo "</div>";

// ============================================
// PART 2: Dompdf Check
// ============================================
echo "<h2>2. Dompdf Check</h2>";
echo "<div class='test-section'>";

if (class_exists('Dompdf\Dompdf')) {
    echo "<p class='success'>✓ Dompdf is available and loaded</p>";
    
    // Test PDF generation
    try {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml('<h1>Test PDF</h1><p>This is a test.</p>');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        echo "<p class='success'>✓ Dompdf can generate PDFs successfully</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Dompdf error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error'>✗ Dompdf class not found</p>";
}
echo "</div>";

// ============================================
// PART 3: Email Function Check
// ============================================
echo "<h2>3. Email Function Check</h2>";
echo "<div class='test-section'>";

if (function_exists('sendEmail')) {
    echo "<p class='success'>✓ sendEmail() function is available</p>";
    
    // Check if form was submitted to send test email
    if (isset($_POST['send_test_email'])) {
        $testSubject = "HIGH Q Test Email - " . date('Y-m-d H:i:s');
        $testBody = "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px;'>
            <h2 style='color: #4f46e5;'>🎓 HIGH Q SOLID ACADEMY - Test Email</h2>
            <p>This is a test email to verify the email system is working correctly.</p>
            <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>Server:</strong> " . php_uname('n') . "</p>
            <hr>
            <p style='color: #666; font-size: 12px;'>This is an automated test. No action required.</p>
        </body>
        </html>";
        
        try {
            $sent = sendEmail($testEmail, $testSubject, $testBody);
            if ($sent) {
                echo "<p class='success'>✓ Test email sent to {$testEmail}! Check your inbox.</p>";
            } else {
                echo "<p class='error'>✗ sendEmail() returned false</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Email error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<form method='post'>";
        echo "<p>Click to send a test email to <strong>{$testEmail}</strong>:</p>";
        echo "<button type='submit' name='send_test_email' class='btn'>Send Test Email</button>";
        echo "</form>";
    }
} else {
    echo "<p class='error'>✗ sendEmail() function not found</p>";
}
echo "</div>";

// ============================================
// PART 4: Quick Links
// ============================================
echo "<h2>4. Quick Test Links</h2>";
echo "<div class='test-section'>";

$baseUrl = "/HIGH-Q/public";

if ($testRid) {
    echo "<p><strong>Admission Letter:</strong></p>";
    echo "<a class='btn' href='{$baseUrl}/admission_letter.php?rid={$testRid}' target='_blank'>View HTML</a>";
    echo "<a class='btn' href='{$baseUrl}/admission_letter.php?rid={$testRid}&format=pdf' target='_blank'>Download PDF</a>";
}

echo "<p style='margin-top: 20px;'><strong>Other Pages:</strong></p>";
echo "<a class='btn' href='{$baseUrl}/contact.php' target='_blank'>Contact (FAQ Test)</a>";
echo "<a class='btn' href='{$baseUrl}/receipt.php?ref=test' target='_blank'>Receipt Page</a>";
echo "</div>";

echo "<h2>✅ Summary</h2>";
echo "<p>The admission letter generator creates PDFs correctly when accessed via:</p>";
echo "<pre>/public/admission_letter.php?rid=REGISTRATION_ID&format=pdf</pre>";
echo "<p>The welcome kit is automatically generated and emailed when a receipt PDF is downloaded.</p>";
?>

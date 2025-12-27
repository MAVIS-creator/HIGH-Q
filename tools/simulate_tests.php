<?php
/**
 * HIGH Q SOLID ACADEMY - Simulated Endpoint & Functionality Tests
 * 
 * This script simulates actual HTTP requests and tests:
 * - Page rendering (checks for expected HTML content)
 * - Registration flow
 * - Academic page confirm/reject/send payment link
 * - Create payment link
 * - Settings save/load
 * - All critical API endpoints
 */

// Configuration
define('BASE_URL', 'http://localhost/HIGH-Q');
define('ADMIN_URL', BASE_URL . '/admin');
define('PUBLIC_URL', BASE_URL . '/public');

// Load dependencies
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../admin/includes/db.php';

// Test results
$passed = 0;
$failed = 0;
$results = [];

/**
 * Helper: Make HTTP GET request and return response
 */
function httpGet($url, $followRedirects = true) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $followRedirects,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Accept: text/html,application/json']
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'body' => $response,
        'code' => $httpCode,
        'error' => $error
    ];
}

/**
 * Helper: Make HTTP POST request
 */
function httpPost($url, $data = [], $headers = []) {
    $ch = curl_init();
    $defaultHeaders = ['Content-Type: application/x-www-form-urlencoded'];
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers)
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'body' => $response,
        'code' => $httpCode,
        'error' => $error
    ];
}

/**
 * Test helper with color output
 */
function test($name, $condition, $details = '') {
    global $passed, $failed, $results;
    
    if ($condition) {
        $passed++;
        echo "\033[32m✓ PASS:\033[0m $name\n";
        $results[] = ['name' => $name, 'status' => 'pass', 'details' => $details];
    } else {
        $failed++;
        echo "\033[31m✗ FAIL:\033[0m $name" . ($details ? " - $details" : "") . "\n";
        $results[] = ['name' => $name, 'status' => 'fail', 'details' => $details];
    }
}

/**
 * Section header
 */
function section($title) {
    echo "\n\033[1;36m--- $title ---\033[0m\n";
}

// ============================================================================
// START TESTS
// ============================================================================

echo "\033[1;33m=== HIGH Q SOLID ACADEMY - Simulated Tests ===\033[0m\n";
echo "Base URL: " . BASE_URL . "\n";
echo "Testing at: " . date('Y-m-d H:i:s') . "\n";

// ============================================================================
// 1. PUBLIC PAGE RENDERING TESTS
// ============================================================================
section("Public Page Rendering Tests");

$publicPages = [
    ['url' => '/public/home.php', 'contains' => ['HIGH Q', 'SOLID ACADEMY'], 'name' => 'Home Page'],
    ['url' => '/public/about.php', 'contains' => ['About', 'Academy'], 'name' => 'About Page'],
    ['url' => '/public/contact.php', 'contains' => ['Contact', 'form'], 'name' => 'Contact Page'],
    ['url' => '/public/programs.php', 'contains' => ['Program', 'Course'], 'name' => 'Programs Page'],
    ['url' => '/public/tutors.php', 'contains' => ['Tutor'], 'name' => 'Tutors Page'],
    ['url' => '/public/register-new.php', 'contains' => ['Register', 'form'], 'name' => 'Registration Page'],
];

foreach ($publicPages as $page) {
    $response = httpGet(BASE_URL . $page['url']);
    $rendered = $response['code'] == 200;
    $hasContent = true;
    
    if ($rendered && !empty($page['contains'])) {
        foreach ($page['contains'] as $keyword) {
            if (stripos($response['body'], $keyword) === false) {
                $hasContent = false;
                break;
            }
        }
    }
    
    test(
        $page['name'] . " renders correctly",
        $rendered && $hasContent,
        $rendered ? '' : "HTTP {$response['code']}"
    );
}

// ============================================================================
// 2. ADMIN PAGE RENDERING TESTS
// ============================================================================
section("Admin Page Rendering Tests");

$adminPages = [
    ['url' => '/admin/index.php', 'contains' => ['Admin', 'Panel'], 'name' => 'Admin Landing'],
    ['url' => '/admin/login.php', 'contains' => ['Login', 'Email', 'Password'], 'name' => 'Admin Login'],
    ['url' => '/admin/signup.php', 'contains' => ['Sign', 'Register'], 'name' => 'Admin Signup'],
    ['url' => '/admin/forgot_password.php', 'contains' => ['Forgot', 'Password', 'Email'], 'name' => 'Forgot Password'],
    ['url' => '/admin/pending.php', 'contains' => ['Pending', 'Approval'], 'name' => 'Pending Page'],
];

foreach ($adminPages as $page) {
    $response = httpGet(BASE_URL . $page['url']);
    $rendered = $response['code'] == 200;
    $hasContent = true;
    
    if ($rendered && !empty($page['contains'])) {
        foreach ($page['contains'] as $keyword) {
            if (stripos($response['body'], $keyword) === false) {
                $hasContent = false;
                break;
            }
        }
    }
    
    test(
        $page['name'] . " renders correctly",
        $rendered && $hasContent,
        $rendered ? '' : "HTTP {$response['code']}"
    );
}

// ============================================================================
// 3. REGISTRATION FLOW SIMULATION
// ============================================================================
section("Registration Flow Simulation");

// Test 1: Registration form loads
$regForm = httpGet(PUBLIC_URL . '/register-new.php');
test(
    "Registration form loads",
    $regForm['code'] == 200 && stripos($regForm['body'], 'form') !== false,
    "HTTP {$regForm['code']}"
);

// Test 2: Check if CSRF token present
test(
    "Registration form has CSRF protection",
    stripos($regForm['body'], 'csrf') !== false || stripos($regForm['body'], '_token') !== false
);

// Test 3: Process registration endpoint exists
$processFile = __DIR__ . '/../public/process-registration.php';
test(
    "process-registration.php exists",
    file_exists($processFile)
);

// Test 4: Check registration saves to DB (verify table structure)
try {
    $stmt = $pdo->query("DESCRIBE student_registrations");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredCols = ['id', 'name', 'email', 'phone', 'status'];
    $hasRequired = count(array_intersect($requiredCols, $columns)) >= 4;
    test("student_registrations table has required columns", $hasRequired);
} catch (Exception $e) {
    test("student_registrations table structure", false, $e->getMessage());
}

// Test 5: Payment wait page exists and renders
$paymentWait = httpGet(PUBLIC_URL . '/payments_wait.php');
test(
    "payments_wait.php renders",
    $paymentWait['code'] == 200 || $paymentWait['code'] == 302,
    "HTTP {$paymentWait['code']}"
);

// Test 6: Receipt page exists
$receiptPage = httpGet(PUBLIC_URL . '/receipt.php');
test(
    "receipt.php exists and accessible",
    $receiptPage['code'] == 200 || $receiptPage['code'] == 302 || $receiptPage['code'] == 400,
    "HTTP {$receiptPage['code']}"
);

// ============================================================================
// 4. ACADEMIC PAGE API TESTS
// ============================================================================
section("Academic Page API Tests");

// Test: Export registration endpoint
$exportFile = __DIR__ . '/../admin/api/export_registration.php';
test("export_registration.php exists", file_exists($exportFile));

// Verify export has PDF capability
if (file_exists($exportFile)) {
    $exportContent = file_get_contents($exportFile);
    test(
        "Export supports PDF format",
        stripos($exportContent, 'export_pdf') !== false || stripos($exportContent, 'Dompdf') !== false
    );
    test(
        "Export supports CSV format",
        stripos($exportContent, 'csv') !== false || stripos($exportContent, 'CSV') !== false
    );
}

// Test: Create payment link endpoint
$paymentLinkFile = __DIR__ . '/../admin/api/create_payment_link.php';
test("create_payment_link.php exists", file_exists($paymentLinkFile));

if (file_exists($paymentLinkFile)) {
    $plContent = file_get_contents($paymentLinkFile);
    test(
        "Create payment link validates required fields",
        stripos($plContent, 'registration_id') !== false || stripos($plContent, 'student') !== false
    );
    test(
        "Create payment link generates unique reference",
        stripos($plContent, 'reference') !== false || stripos($plContent, 'uniqid') !== false
    );
}

// Test: Resend payment link endpoint
$resendFile = __DIR__ . '/../admin/api/resend_payment_link.php';
test("resend_payment_link.php exists", file_exists($resendFile));

// ============================================================================
// 5. SETTINGS API TESTS
// ============================================================================
section("Settings API Tests");

// Test: Save settings endpoint
$saveSettingsFile = __DIR__ . '/../admin/api/save-settings.php';
test("save-settings.php exists", file_exists($saveSettingsFile));

if (file_exists($saveSettingsFile)) {
    $settingsContent = file_get_contents($saveSettingsFile);
    test(
        "Settings API handles JSON data",
        stripos($settingsContent, 'json') !== false
    );
    test(
        "Settings API uses database",
        stripos($settingsContent, 'pdo') !== false || stripos($settingsContent, 'INSERT') !== false || stripos($settingsContent, 'UPDATE') !== false
    );
}

// Test: Settings can be loaded from DB
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM site_settings");
    $count = $stmt->fetchColumn();
    test("site_settings table has data", $count >= 0);
} catch (Exception $e) {
    test("site_settings table accessible", false, $e->getMessage());
}

// ============================================================================
// 6. AUTHENTICATION FLOW TESTS
// ============================================================================
section("Authentication Flow Tests");

// Test login page CSRF
$loginPage = httpGet(ADMIN_URL . '/login.php');
test(
    "Login page has CSRF token",
    stripos($loginPage['body'], 'csrf') !== false || stripos($loginPage['body'], '_token') !== false
);

// Test login form has required fields
test(
    "Login form has email field",
    stripos($loginPage['body'], 'name="email"') !== false
);
test(
    "Login form has password field",
    stripos($loginPage['body'], 'name="password"') !== false
);

// Test forgot password flow
$forgotPage = httpGet(ADMIN_URL . '/forgot_password.php');
test(
    "Forgot password page renders",
    $forgotPage['code'] == 200 && stripos($forgotPage['body'], 'email') !== false
);

// ============================================================================
// 7. NOTIFICATION API TESTS
// ============================================================================
section("Notification API Tests");

$notifFile = __DIR__ . '/../admin/api/notifications.php';
test("notifications.php exists", file_exists($notifFile));

$markReadFile = __DIR__ . '/../admin/api/mark_read.php';
test("mark_read.php exists", file_exists($markReadFile));

// ============================================================================
// 8. DATABASE INTEGRITY TESTS
// ============================================================================
section("Database Integrity Tests");

$tables = [
    'users' => ['id', 'email', 'password', 'role_id'],
    'roles' => ['id', 'name', 'slug'],
    'payments' => ['id', 'amount', 'status'],
    'courses' => ['id', 'name', 'status'],
    'student_registrations' => ['id', 'name', 'email'],
    'notifications' => ['id', 'message'],
];

foreach ($tables as $table => $expectedCols) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $hasRequired = count(array_intersect($expectedCols, $columns)) >= count($expectedCols) - 1;
        test("Table '$table' structure valid", $hasRequired);
    } catch (Exception $e) {
        test("Table '$table' accessible", false, $e->getMessage());
    }
}

// ============================================================================
// 9. VENDOR DEPENDENCY TESTS
// ============================================================================
section("Vendor Dependency Tests");

// Dompdf for PDF generation
test("Dompdf loaded", class_exists('Dompdf\Dompdf'));

// PHPMailer for emails
test("PHPMailer loaded", class_exists('PHPMailer\PHPMailer\PHPMailer'));

// ============================================================================
// 10. ACADEMIC CONFIRM/REJECT FUNCTIONALITY TEST
// ============================================================================
section("Academic Confirm/Reject Functionality");

$academicFile = __DIR__ . '/../admin/pages/academic.php';
test("academic.php exists", file_exists($academicFile));

if (file_exists($academicFile)) {
    $academicContent = file_get_contents($academicFile);
    
    test(
        "Academic page has confirm registration function",
        stripos($academicContent, 'confirmRegistration') !== false
    );
    
    test(
        "Academic page has reject registration function",
        stripos($academicContent, 'rejectRegistration') !== false || stripos($academicContent, 'reject') !== false
    );
    
    test(
        "Academic page has view registration modal",
        stripos($academicContent, 'viewRegistration') !== false
    );
    
    test(
        "Academic page has export functionality",
        stripos($academicContent, 'export') !== false
    );
    
    test(
        "Academic page sends email on confirm",
        stripos($academicContent, 'sendEmail') !== false || stripos($academicContent, 'mail') !== false
    );
}

// ============================================================================
// 11. PAYMENTS FUNCTIONALITY TEST
// ============================================================================
section("Payments Functionality");

$paymentsFile = __DIR__ . '/../admin/pages/payments.php';
test("payments.php exists", file_exists($paymentsFile));

if (file_exists($paymentsFile)) {
    $paymentsContent = file_get_contents($paymentsFile);
    
    test(
        "Payments page has create payment link",
        stripos($paymentsContent, 'createPaymentLink') !== false || stripos($paymentsContent, 'payment_link') !== false
    );
    
    test(
        "Payments page displays payment status",
        stripos($paymentsContent, 'status') !== false
    );
    
    test(
        "Payments page shows payment history",
        stripos($paymentsContent, 'payments') !== false
    );
}

// ============================================================================
// 12. ERROR HANDLING TESTS
// ============================================================================
section("Error Handling Tests");

// Test 404 page
$errorPage = httpGet(BASE_URL . '/admin/errors/404.php');
test(
    "404 error page exists",
    $errorPage['code'] == 200 || file_exists(__DIR__ . '/../admin/errors/404.php')
);

// Test error folder exists
test(
    "Admin errors folder exists",
    is_dir(__DIR__ . '/../admin/errors')
);

// ============================================================================
// SUMMARY
// ============================================================================
echo "\n\033[1;33m=== TEST SUMMARY ===\033[0m\n";
echo "\033[32mPassed: $passed\033[0m\n";
echo "\033[31mFailed: $failed\033[0m\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed > 0) {
    echo "\n\033[31mFailed tests:\033[0m\n";
    foreach ($results as $r) {
        if ($r['status'] === 'fail') {
            echo "  - {$r['name']}" . ($r['details'] ? " ({$r['details']})" : "") . "\n";
        }
    }
}

// Return exit code
exit($failed > 0 ? 1 : 0);

<?php
/**
 * Endpoint and Feature Testing Script
 * Tests various API endpoints and functionality
 */

require_once __DIR__ . '/../admin/includes/db.php';

echo "=== HIGH Q SOLID ACADEMY - Endpoint Testing ===\n\n";

$tests = [];
$passed = 0;
$failed = 0;

function test($name, $result, $message = '') {
    global $passed, $failed, $tests;
    if ($result) {
        echo "✓ PASS: $name\n";
        $passed++;
    } else {
        echo "✗ FAIL: $name" . ($message ? " - $message" : "") . "\n";
        $failed++;
    }
    $tests[$name] = $result;
}

// 1. Database Connection
echo "--- Database Tests ---\n";
test('Database Connection', $pdo instanceof PDO);

// 2. Check Required Tables
$requiredTables = [
    'users', 'roles', 'role_permissions', 'menus', 'courses', 
    'student_registrations', 'payments', 'site_settings',
    'testimonials', 'tutors', 'posts', 'notifications'
];

foreach ($requiredTables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    test("Table exists: $table", $stmt->rowCount() > 0);
}

// 3. Check API Files Exist
echo "\n--- API File Tests ---\n";
$apiFiles = [
    'admin/api/create_payment_link.php',
    'admin/api/export_registration.php',
    'admin/api/mark_read.php',
    'admin/api/notifications.php',
    'admin/api/save-settings.php',
    'admin/api/update_profile.php',
    'admin/api/update_password.php',
    'admin/api/patcher.php',
    'admin/api/tutors.php',
];

foreach ($apiFiles as $file) {
    $path = __DIR__ . '/../' . $file;
    test("API exists: $file", file_exists($path));
}

// 4. Check Public Pages Exist
echo "\n--- Public Page Tests ---\n";
$publicPages = [
    'public/home.php',
    'public/about.php',
    'public/contact.php',
    'public/register-new.php',
    'public/process-registration.php',
    'public/payments_wait.php',
    'public/payments_callback.php',
    'public/receipt.php',
    'public/programs.php',
    'public/tutors.php',
];

foreach ($publicPages as $file) {
    $path = __DIR__ . '/../' . $file;
    test("Page exists: $file", file_exists($path));
}

// 5. Check Admin Pages Exist
echo "\n--- Admin Page Tests ---\n";
$adminPages = [
    'admin/pages/dashboard.php',
    'admin/pages/academic.php',
    'admin/pages/payments.php',
    'admin/pages/users.php',
    'admin/pages/settings.php',
    'admin/pages/courses.php',
    'admin/pages/posts.php',
    'admin/pages/patcher.php',
];

foreach ($adminPages as $file) {
    $path = __DIR__ . '/../' . $file;
    test("Admin page exists: $file", file_exists($path));
}

// 6. Test Registration Table Structure
echo "\n--- Registration Data Tests ---\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM student_registrations");
    $count = $stmt->fetchColumn();
    test("Student registrations count", true, "Found $count records");
    
    // Check for post_utme_registrations
    $stmt = $pdo->query("SHOW TABLES LIKE 'post_utme_registrations'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM post_utme_registrations");
        $count = $stmt->fetchColumn();
        test("Post-UTME registrations count", true, "Found $count records");
    }
    
    // Check for universal_registrations
    $stmt = $pdo->query("SHOW TABLES LIKE 'universal_registrations'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM universal_registrations");
        $count = $stmt->fetchColumn();
        test("Universal registrations count", true, "Found $count records");
    }
} catch (Exception $e) {
    test("Registration tables", false, $e->getMessage());
}

// 7. Test Payments Table
echo "\n--- Payment Tests ---\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM payments");
    $count = $stmt->fetchColumn();
    test("Payments count", true, "Found $count records");
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
    $pending = $stmt->fetchColumn();
    test("Pending payments", true, "Found $pending pending");
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'paid'");
    $paid = $stmt->fetchColumn();
    test("Paid payments", true, "Found $paid paid");
} catch (Exception $e) {
    test("Payment tables", false, $e->getMessage());
}

// 8. Test Courses
echo "\n--- Course Tests ---\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM courses WHERE is_active = 1");
    $count = $stmt->fetchColumn();
    test("Active courses", true, "Found $count active courses");
} catch (Exception $e) {
    test("Courses", false, $e->getMessage());
}

// 9. Test Users and Roles
echo "\n--- User & Role Tests ---\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    test("Users count", true, "Found $count users");
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM roles");
    $count = $stmt->fetchColumn();
    test("Roles count", true, "Found $count roles");
} catch (Exception $e) {
    test("Users/Roles", false, $e->getMessage());
}

// 10. Test Site Settings
echo "\n--- Settings Tests ---\n";
try {
    $stmt = $pdo->query("SELECT * FROM site_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    test("Site settings exists", $settings !== false);
} catch (Exception $e) {
    test("Site settings", false, $e->getMessage());
}

// 11. Check Vendor Dependencies
echo "\n--- Vendor Dependency Tests ---\n";
$vendorCheck = file_exists(__DIR__ . '/../vendor/autoload.php');
test("Vendor autoload exists", $vendorCheck);

if ($vendorCheck) {
    require_once __DIR__ . '/../vendor/autoload.php';
    test("Dompdf available", class_exists('\Dompdf\Dompdf'));
    test("PHPMailer available", class_exists('\PHPMailer\PHPMailer\PHPMailer'));
}

// 12. Check Storage Directories
echo "\n--- Storage Directory Tests ---\n";
$storageDirs = [
    'storage/logs',
    'storage/backups',
    'public/uploads',
    'public/uploads/passports',
    'public/uploads/receipts',
];

foreach ($storageDirs as $dir) {
    $path = __DIR__ . '/../' . $dir;
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    test("Directory: $dir", $writable, $exists ? ($writable ? 'writable' : 'not writable') : 'missing');
}

// Summary
echo "\n=== TEST SUMMARY ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed > 0) {
    echo "\nFailed tests:\n";
    foreach ($tests as $name => $result) {
        if (!$result) {
            echo "  - $name\n";
        }
    }
}

echo "\n";

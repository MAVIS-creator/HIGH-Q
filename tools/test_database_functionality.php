<?php
/**
 * HIGH-Q Database Functionality Test Suite
 * Tests all major database operations and schema integrity
 */

require_once __DIR__ . '/../admin/includes/db.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    HIGH-Q DATABASE FUNCTIONALITY TEST SUITE                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n\n";

$testsPassed = 0;
$testsFailed = 0;

function runTest($name, $closure) {
    global $testsPassed, $testsFailed;
    try {
        $result = $closure();
        if ($result) {
            echo "  ✓ $name\n";
            $testsPassed++;
            return true;
        } else {
            echo "  ✗ $name - Test returned false\n";
            $testsFailed++;
            return false;
        }
    } catch (Exception $e) {
        echo "  ✗ $name - " . substr($e->getMessage(), 0, 80) . "\n";
        $testsFailed++;
        return false;
    }
}

// Test 1: Connection
echo "1️⃣  DATABASE CONNECTION TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("PDO Connection Active", function() {
    global $pdo;
    return $pdo instanceof PDO;
});
runTest("Can Query Database", function() {
    global $pdo;
    return $pdo->query("SELECT 1") !== false;
});

// Test 2: Users Table
echo "\n2️⃣  USERS & AUTHENTICATION TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("Users Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    return $stmt->rowCount() > 0;
});
runTest("Users Table Has Required Columns", function() {
    global $pdo;
    $columns = ['id', 'email', 'password', 'role_id', 'created_at'];
    $stmt = $pdo->query("DESCRIBE users");
    $existing = array_column($stmt->fetchAll(), 'Field');
    return count(array_intersect($columns, $existing)) === count($columns);
});
runTest("Can Select Users", function() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    return $stmt->fetch()['cnt'] >= 0;
});

// Test 3: Courses
echo "\n3️⃣  COURSES & EDUCATION TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("Courses Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'courses'");
    return $stmt->rowCount() > 0;
});
runTest("Can Query Courses", function() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM courses");
    return $stmt->fetch()['cnt'] >= 0;
});

// Test 4: Student Registrations
echo "\n4️⃣  STUDENT REGISTRATION TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("Student Registrations Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'student_registrations'");
    return $stmt->rowCount() > 0;
});
runTest("Can Query Student Registrations", function() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM student_registrations");
    return $stmt->fetch()['cnt'] >= 0;
});
runTest("Email Column Exists in Registrations", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW COLUMNS FROM student_registrations LIKE 'email'");
    return $stmt->rowCount() > 0;
});

// Test 5: Post-UTME
echo "\n5️⃣  POST-UTME EXAMINATION TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("Post-UTME Registrations Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'post_utme_registrations'");
    return $stmt->rowCount() > 0;
});
runTest("Can Query Post-UTME Registrations", function() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM post_utme_registrations");
    return $stmt->fetch()['cnt'] >= 0;
});

// Test 6: Payments
echo "\n6️⃣  PAYMENT PROCESSING TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("Payments Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    return $stmt->rowCount() > 0;
});
runTest("Can Query Payments", function() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM payments");
    return $stmt->fetch()['cnt'] >= 0;
});
runTest("Payment Status Column Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'status'");
    return $stmt->rowCount() > 0;
});

// Test 7: Forum & Community
echo "\n7️⃣  FORUM & COMMUNITY TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("Forum Questions Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'forum_questions'");
    return $stmt->rowCount() > 0;
});
runTest("Forum Replies Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'forum_replies'");
    return $stmt->rowCount() > 0;
});
runTest("Can Query Forum Data", function() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM forum_questions");
    return $stmt->fetch()['cnt'] >= 0;
});

// Test 8: Notifications & Messaging
echo "\n8️⃣  NOTIFICATIONS & MESSAGING TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("Notifications Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    return $stmt->rowCount() > 0;
});
runTest("Chat Messages Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'chat_messages'");
    return $stmt->rowCount() > 0;
});

// Test 9: Migration Tracking
echo "\n9️⃣  MIGRATION TRACKING TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("Migrations Table Exists", function() {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'migrations'");
    return $stmt->rowCount() > 0;
});
runTest("Migrations Are Tracked", function() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM migrations");
    $count = $stmt->fetch()['cnt'];
    return $count > 0;
});
runTest("Can Read Migration Status", function() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM migrations WHERE status = 'success'");
    return $stmt->fetch()['cnt'] > 0;
});

// Test 10: Data Operations
echo "\n🔟 DATA OPERATION TESTS\n";
echo "─────────────────────────────────────────────────────────────────────────────────\n";
runTest("Can Insert Test Data", function() {
    global $pdo;
    try {
        $pdo->prepare("INSERT IGNORE INTO audit_logs (user_id, action, created_at) VALUES (?, ?, NOW())")
            ->execute([1, 'test_action']);
        return true;
    } catch (Exception $e) {
        return false;
    }
});
runTest("Can Select Test Data", function() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM audit_logs WHERE action = 'test_action'");
    return $stmt->fetch()['cnt'] >= 0;
});
runTest("Can Use Prepared Statements", function() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE id = ?");
    $stmt->execute([1]);
    return $stmt->fetch()['cnt'] >= 0;
});

// Summary
echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
$totalTests = $testsPassed + $testsFailed;
$successRate = round(($testsPassed / $totalTests) * 100, 1);
echo "║                            TEST RESULTS SUMMARY                                ║\n";
echo "╠════════════════════════════════════════════════════════════════════════════════╣\n";
printf("║  Total Tests: %-73d║\n", $totalTests);
printf("║  Passed: %-77d║\n", $testsPassed);
printf("║  Failed: %-77d║\n", $testsFailed);
printf("║  Success Rate: %-70s║\n", $successRate . "%");
echo "╠════════════════════════════════════════════════════════════════════════════════╣\n";

if ($testsFailed === 0) {
    echo "║                          ✅ ALL TESTS PASSED!                                  ║\n";
    echo "║                    Database is fully operational and ready.                  ║\n";
} else {
    echo "║                      ⚠️  SOME TESTS FAILED - REVIEW ABOVE                       ║\n";
}

echo "║                                                                                ║\n";
echo "║  Generated: " . date('Y-m-d H:i:s') . "                                              ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";

exit($testsFailed > 0 ? 1 : 0);
?>

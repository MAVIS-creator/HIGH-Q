<?php
/**
 * Admin Notification System Test Script
 * Verifies that notifications are sent to all admin emails for:
 * - New registrations
 * - Payment confirmations/rejections
 * - Chat messages/updates
 * 
 * Access: admin/api/test-notifications.php?action=test_all
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is authenticated and admin
if (empty($_SESSION['user'])) {
    http_response_code(401);
    die('Unauthorized: Please log in first');
}

try {
    requirePermission('settings');
} catch (Exception $e) {
    http_response_code(403);
    die('Forbidden: You do not have permission to run tests');
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'info';
$result = [
    'status' => 'error',
    'message' => 'Unknown action',
    'details' => [],
];

try {
    switch ($action) {
        case 'info':
            // Get system info
            $result = [
                'status' => 'ok',
                'message' => 'Notification System Information',
                'details' => [
                    'notifications_enabled' => hqAdminEmailNotificationsEnabled($pdo),
                    'admin_recipients' => hqAdminNotificationRecipients($pdo),
                    'total_admins' => count(hqAdminNotificationRecipients($pdo)),
                    'current_user' => [
                        'id' => $_SESSION['user']['id'] ?? null,
                        'name' => $_SESSION['user']['name'] ?? null,
                        'email' => $_SESSION['user']['email'] ?? null,
                    ],
                    'test_endpoints' => [
                        'test_all' => 'Run all notification tests',
                        'test_admin_list' => 'Test admin email collection',
                        'test_registration' => 'Test registration notification',
                        'test_payment' => 'Test payment notification',
                        'test_chat' => 'Test chat notification',
                        'test_public_chat' => 'Test public chat message notification',
                        'test_public_payment' => 'Test public payment confirmation notification',
                        'test_settings' => 'Test notification settings',
                    ]
                ]
            ];
            break;

        case 'test_admin_list':
            // Test admin email collection
            $admins = hqAdminNotificationRecipients($pdo);
            $result = [
                'status' => 'ok',
                'message' => count($admins) . ' admin email(s) found',
                'details' => [
                    'admin_count' => count($admins),
                    'admin_emails' => $admins,
                    'notifications_enabled' => hqAdminEmailNotificationsEnabled($pdo),
                ]
            ];
            break;

        case 'test_registration':
            // Test registration notification
            $testData = [
                'Registration ID' => 'TEST-' . uniqid(),
                'Program Type' => 'Post-UTME',
                'Student Name' => 'Test Student',
                'Email' => 'test@example.com',
                'Phone' => '+2348012345678',
                'Amount' => '₦10,000.00',
                'Payment Reference' => 'REG-TEST-' . date('YmdHis'),
                'Status' => 'Test Notification'
            ];
            
            $sent = sendAdminChangeNotification($pdo, 'TEST: New Registration Submitted', $testData, (int)($_SESSION['user']['id'] ?? 0));
            $result = [
                'status' => $sent ? 'ok' : 'warning',
                'message' => $sent ? 'Registration test notification sent' : 'No admin recipients found or notifications disabled',
                'details' => [
                    'test_data' => $testData,
                    'recipients_count' => count(hqAdminNotificationRecipients($pdo)),
                    'recipients' => hqAdminNotificationRecipients($pdo),
                    'notifications_enabled' => hqAdminEmailNotificationsEnabled($pdo),
                ]
            ];
            break;

        case 'test_payment':
            // Test payment notification
            $testData = [
                'Payment ID' => 'TEST-' . uniqid(),
                'Reference' => 'PAY-TEST-' . date('YmdHis'),
                'Amount' => '₦10,000.00',
                'Method' => 'Bank Transfer',
                'Status' => 'Test Confirmed'
            ];
            
            $sent = sendAdminChangeNotification($pdo, 'TEST: Payment Confirmed', $testData, (int)($_SESSION['user']['id'] ?? 0));
            $result = [
                'status' => $sent ? 'ok' : 'warning',
                'message' => $sent ? 'Payment test notification sent' : 'No admin recipients found or notifications disabled',
                'details' => [
                    'test_data' => $testData,
                    'recipients_count' => count(hqAdminNotificationRecipients($pdo)),
                    'recipients' => hqAdminNotificationRecipients($pdo),
                ]
            ];
            break;

        case 'test_chat':
            // Test chat notification
            $testData = [
                'Thread ID' => 'TEST-' . uniqid(),
                'Visitor Name' => 'Test Visitor',
                'Action' => 'New Message',
                'Message Preview' => 'This is a test chat message from system testing'
            ];
            
            $sent = sendAdminChangeNotification($pdo, 'TEST: Chat Message', $testData, (int)($_SESSION['user']['id'] ?? 0));
            $result = [
                'status' => $sent ? 'ok' : 'warning',
                'message' => $sent ? 'Chat test notification sent' : 'No admin recipients found or notifications disabled',
                'details' => [
                    'test_data' => $testData,
                    'recipients_count' => count(hqAdminNotificationRecipients($pdo)),
                    'recipients' => hqAdminNotificationRecipients($pdo),
                ]
            ];
            break;

        case 'test_public_chat':
            // Test public visitor chat notification
            $testData = [
                'Thread ID' => 'PUBLIC-TEST-' . uniqid(),
                'Visitor Name' => 'Test Visitor',
                'Visitor Email' => 'visitor@example.com',
                'Message Preview' => 'This is a test public chat message from system testing',
                'Has Attachments' => 'No',
                'Status' => 'Awaiting Admin Response'
            ];
            
            $sent = sendAdminChangeNotification($pdo, 'New Chat Message from Visitor', $testData, (int)($_SESSION['user']['id'] ?? 0));
            $result = [
                'status' => $sent ? 'ok' : 'warning',
                'message' => $sent ? 'Public chat test notification sent (simulating visitor message)' : 'No admin recipients found or notifications disabled',
                'details' => [
                    'test_data' => $testData,
                    'trigger_location' => 'public/chatbox.php (when visitor sends message)',
                    'recipients_count' => count(hqAdminNotificationRecipients($pdo)),
                    'recipients' => hqAdminNotificationRecipients($pdo),
                ]
            ];
            break;

        case 'test_public_payment':
            // Test bank transfer payment confirmation notification (admin confirms payment)
            $testData = [
                'Payment ID' => 'TEST-PAY-' . uniqid(),
                'Reference' => 'BANK-' . rand(100000, 999999),
                'Amount' => '₦50,000.00',
                'Gateway' => 'Bank Transfer',
                'Status' => 'Successfully Confirmed'
            ];
            
            $sent = sendAdminChangeNotification($pdo, 'Payment Confirmed by Admin', $testData, (int)($_SESSION['user']['id'] ?? 0));
            $result = [
                'status' => $sent ? 'ok' : 'warning',
                'message' => $sent ? 'Bank transfer payment test notification sent (admin confirmation)' : 'No admin recipients found or notifications disabled',
                'details' => [
                    'test_data' => $testData,
                    'trigger_location' => 'admin/pages/payments.php (when admin confirms payment)',
                    'payment_system' => 'Bank Transfer (Not Paystack)',
                    'recipients_count' => count(hqAdminNotificationRecipients($pdo)),
                    'recipients' => hqAdminNotificationRecipients($pdo),
                ]
            ];
            break;

        case 'test_settings':
            // Test notification settings
            $settings = [];
            try {
                $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
                $stmt->execute(['system_settings']);
                $raw = $stmt->fetchColumn();
                if ($raw) {
                    $settings = json_decode($raw, true) ?: [];
                }
            } catch (Throwable $e) {
                $settings = [];
            }
            
            $result = [
                'status' => 'ok',
                'message' => 'Notification settings information',
                'details' => [
                    'notifications_enabled' => hqAdminEmailNotificationsEnabled($pdo),
                    'system_settings' => $settings,
                    'mail_configured' => !empty($_ENV['MAIL_HOST']),
                    'mail_host' => $_ENV['MAIL_HOST'] ?? 'NOT SET',
                    'mail_from' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'NOT SET',
                ]
            ];
            break;

        case 'test_all':
            // Run all tests
            $allTests = [
                'admin_list' => [],
                'registration' => [],
                'payment' => [],
                'chat' => [],
                'public_chat' => [],
                'public_payment' => [],
                'settings' => [],
            ];
            
            // Admin list test
            $admins = hqAdminNotificationRecipients($pdo);
            $allTests['admin_list'] = [
                'status' => count($admins) > 0 ? 'pass' : 'fail',
                'message' => count($admins) . ' admin(s) found',
                'admins' => $admins
            ];
            
            // Registration notification test
            $regData = [
                'Registration ID' => 'TEST-' . uniqid(),
                'Program Type' => 'Post-UTME',
                'Student Name' => 'Test Student',
                'Email' => 'test@example.com',
            ];
            $regSent = sendAdminChangeNotification($pdo, 'TEST: New Registration', $regData, (int)($_SESSION['user']['id'] ?? 0));
            $allTests['registration'] = [
                'status' => $regSent ? 'pass' : (count($admins) > 0 ? 'warning' : 'fail'),
                'message' => $regSent ? 'Notification sent' : 'Failed to send',
            ];
            
            // Payment notification test
            $payData = [
                'Payment ID' => 'TEST-' . uniqid(),
                'Reference' => 'PAY-TEST-' . date('YmdHis'),
                'Amount' => '₦10,000.00',
            ];
            $paySent = sendAdminChangeNotification($pdo, 'TEST: Payment Confirmed', $payData, (int)($_SESSION['user']['id'] ?? 0));
            $allTests['payment'] = [
                'status' => $paySent ? 'pass' : (count($admins) > 0 ? 'warning' : 'fail'),
                'message' => $paySent ? 'Notification sent' : 'Failed to send',
            ];
            
            // Chat notification test
            $chatData = [
                'Thread ID' => 'TEST-' . uniqid(),
                'Visitor Name' => 'Test Visitor',
                'Message Preview' => 'Test message',
            ];
            $chatSent = sendAdminChangeNotification($pdo, 'TEST: Chat Message', $chatData, (int)($_SESSION['user']['id'] ?? 0));
            $allTests['chat'] = [
                'status' => $chatSent ? 'pass' : (count($admins) > 0 ? 'warning' : 'fail'),
                'message' => $chatSent ? 'Notification sent' : 'Failed to send',
            ];
            
            // Public chat notification test
            $pubChatData = [
                'Thread ID' => 'PUBLIC-TEST-' . uniqid(),
                'Visitor Name' => 'Test Visitor',
                'Visitor Email' => 'visitor@example.com',
                'Message Preview' => 'Test public chat message',
                'Has Attachments' => 'No',
                'Status' => 'Awaiting Admin Response'
            ];
            $pubChatSent = sendAdminChangeNotification($pdo, 'New Chat Message from Visitor', $pubChatData, (int)($_SESSION['user']['id'] ?? 0));
            $allTests['public_chat'] = [
                'status' => $pubChatSent ? 'pass' : (count($admins) > 0 ? 'warning' : 'fail'),
                'message' => $pubChatSent ? 'Public chat notification sent' : 'Failed to send',
                'trigger' => 'public/chatbox.php'
            ];
            
            // Public payment notification test
            $pubPayData = [
                'Payment ID' => 'TEST-PAY-' . uniqid(),
                'Reference' => 'BANK-' . rand(100000, 999999),
                'Amount' => '₦50,000.00',
                'Gateway' => 'Bank Transfer',
                'Status' => 'Successfully Confirmed'
            ];
            $pubPaySent = sendAdminChangeNotification($pdo, 'Payment Confirmed by Admin', $pubPayData, (int)($_SESSION['user']['id'] ?? 0));
            $allTests['public_payment'] = [
                'status' => $pubPaySent ? 'pass' : (count($admins) > 0 ? 'warning' : 'fail'),
                'message' => $pubPaySent ? 'Bank transfer payment notification sent' : 'Failed to send',
                'trigger' => 'admin/pages/payments.php'
            ];
            
            // Settings test
            $allTests['settings'] = [
                'status' => hqAdminEmailNotificationsEnabled($pdo) ? 'pass' : 'warning',
                'message' => hqAdminEmailNotificationsEnabled($pdo) ? 'Notifications enabled' : 'Notifications may be disabled',
            ];
            
            $result = [
                'status' => 'ok',
                'message' => 'All notification tests completed',
                'details' => $allTests,
                'summary' => [
                    'total_tests' => count($allTests),
                    'passed' => count(array_filter($allTests, function($t) { return $t['status'] === 'pass'; })),
                    'warnings' => count(array_filter($allTests, function($t) { return $t['status'] === 'warning'; })),
                ]
            ];
            break;

        default:
            $result['message'] = 'Unknown action. Use ?action=test_all, test_admin_list, test_registration, test_payment, test_chat, test_public_chat, test_public_payment, test_settings, or info';
    }
} catch (Throwable $e) {
    $result = [
        'status' => 'error',
        'message' => 'Test error: ' . $e->getMessage(),
        'details' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

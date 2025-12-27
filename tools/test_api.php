<?php
/**
 * Test API for UI Testing Dashboard
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/config/db.php';
require_once __DIR__ . '/../public/config/functions.php';
require_once __DIR__ . '/../public/includes/welcome-kit-generator.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'sendEmail':
        $email = $_GET['email'] ?? '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address']);
            exit;
        }
        
        $subject = 'HIGH Q Test Email - ' . date('Y-m-d H:i:s');
        $body = '
        <html>
        <body style="font-family: Arial, sans-serif; padding: 20px;">
            <div style="background: #FFD600; padding: 15px; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #000;">HIGH Q SOLID ACADEMY - Test Email</h2>
            </div>
            <p>This is a test email sent at: <strong>' . date('Y-m-d H:i:s') . '</strong></p>
            <p>If you receive this, the email system is working correctly!</p>
            <hr>
            <p style="color: #666; font-size: 12px;">Automated test from UI Test Dashboard</p>
        </body>
        </html>';
        
        try {
            $result = sendEmail($email, $subject, $body);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'generateWelcomeKit':
        try {
            $result = generateWelcomeKitPDF('jamb', 'Test Student', 'test@example.com', 999);
            if ($result && isset($result['filepath'])) {
                echo json_encode([
                    'success' => true, 
                    'filepath' => $result['filepath'],
                    'downloadUrl' => str_replace(__DIR__ . '/../', '/HIGH-Q/', $result['filepath'])
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Generation returned empty result']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'sendWelcomeKitEmail':
        $email = $_GET['email'] ?? 'test@example.com';
        
        try {
            // First generate the PDF
            $result = generateWelcomeKitPDF('jamb', 'Test Student', $email, 999);
            
            if ($result && isset($result['filepath'])) {
                // Then send the email
                $emailResult = sendWelcomeKitEmail($email, 'Test Student', 'jamb', 999, $result['filepath']);
                echo json_encode(['success' => $emailResult]);
            } else {
                echo json_encode(['success' => false, 'error' => 'PDF generation failed']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

<?php
/**
 * admin/api/send-report.php
 * 
 * API endpoint for generating and sending security scan reports
 * via email. Can be called:
 * - After scan completion (scheduler)
 * - Manually via admin UI
 * - Programmatically from other systems
 * 
 * Parameters:
 * - scan_data: JSON scan result from scan-engine.php
 * - recipient_email: Email to send report to (optional, defaults to company email)
 * - send_email: Whether to send email (default: true)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/report-generator.php';

header('Content-Type: application/json');

try {
    // Check permission
    requirePermission('sentinel');
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Validate scan data
    if (empty($input['scan_data'])) {
        throw new Exception('scan_data parameter is required');
    }
    
    $scanData = is_array($input['scan_data']) ? $input['scan_data'] : json_decode($input['scan_data'], true);
    if (!is_array($scanData) || !isset($scanData['report'])) {
        throw new Exception('Invalid scan data format');
    }
    
    // Generate report
    $generator = new ReportGenerator($scanData);
    $htmlReport = $generator->generateHtmlEmail();
    $plainReport = $generator->generatePlainText();
    
    // Determine recipients
    $recipientEmail = $input['recipient_email'] ?? ($_ENV['MAIL_FROM_ADDRESS'] ?? 'akintunde.dolapo1@gmail.com');
    $sendEmail = $input['send_email'] !== false && $input['send_email'] !== 'false';
    
    $response = [
        'status' => 'success',
        'message' => 'Report generated successfully',
        'report_html' => $htmlReport,
        'report_text' => $plainReport,
        'recipient' => $recipientEmail,
        'sent' => false
    ];
    
    // Send email if requested
    if ($sendEmail && $recipientEmail) {
        $scanType = $scanData['report']['scan_type'] ?? 'Security';
        $subject = "[HIGH Q] Security Scan Report - " . ucfirst($scanType) . " Scan";
        
        $sent = sendEmail(
            $recipientEmail,
            $subject,
            $htmlReport,
            []
        );
        
        if ($sent) {
            $response['sent'] = true;
            $response['message'] = 'Report generated and sent successfully';
            
            // Log the action
            logAction($pdo, $_SESSION['user_id'] ?? 0, 'security_report_sent', [
                'scan_type' => $scanType,
                'recipient' => $recipientEmail,
                'findings' => [
                    'critical' => count($scanData['report']['critical'] ?? []),
                    'warnings' => count($scanData['report']['warnings'] ?? []),
                ]
            ]);
        } else {
            $response['sent'] = false;
            $response['message'] = 'Report generated but email could not be sent';
        }
    }
    
    // Store report for historical records
    $reportDir = __DIR__ . '/../../storage/scan_reports';
    if (!is_dir($reportDir)) @mkdir($reportDir, 0755, true);
    
    $timestamp = date('Y-m-d_H-i-s');
    $reportFile = $reportDir . DIRECTORY_SEPARATOR . "report_{$scanType}_{$timestamp}.json";
    @file_put_contents($reportFile, json_encode([
        'timestamp' => $timestamp,
        'scan_data' => $scanData,
        'recipient' => $recipientEmail,
        'sent' => $response['sent']
    ], JSON_PRETTY_PRINT));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

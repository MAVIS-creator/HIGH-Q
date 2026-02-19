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
 * - generate_pdf: Whether to generate PDF (default: true)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/report-generator.php';
require_once __DIR__ . '/../includes/pdf-report-generator.php';

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
    
    // Generate reports
    $generator = new ReportGenerator($scanData);
    $htmlReport = $generator->generateHtmlEmail();
    $plainReport = $generator->generatePlainText();
    
    // Generate PDF report
    $pdfGenerator = new PdfReportGenerator($scanData);
    $pdfPath = null;
    $pdfFilename = null;
    $briefSummary = $pdfGenerator->getBriefSummary();
    
    $generatePdf = ($input['generate_pdf'] ?? true) !== false && ($input['generate_pdf'] ?? true) !== 'false';
    
    if ($generatePdf) {
        try {
            $pdfPath = $pdfGenerator->generate();
            $pdfFilename = basename($pdfPath);
        } catch (Exception $e) {
            // PDF generation failed, continue without PDF
            error_log("PDF generation failed: " . $e->getMessage());
        }
    }
    
    // Determine recipients
    $recipientEmail = $input['recipient_email'] ?? ($_ENV['MAIL_FROM_ADDRESS'] ?? 'akintunde.dolapo1@gmail.com');
    $sendEmail = $input['send_email'] !== false && $input['send_email'] !== 'false';
    
    $response = [
        'status' => 'success',
        'message' => 'Report generated successfully',
        'report_html' => $htmlReport,
        'report_text' => $plainReport,
        'brief_summary' => $briefSummary,
        'pdf_path' => $pdfPath,
        'pdf_filename' => $pdfFilename,
        'recipient' => $recipientEmail,
        'sent' => false
    ];
    
    // Send email if requested
    if ($sendEmail && $recipientEmail) {
        $scanType = $scanData['report']['scan_type'] ?? 'Security';
        $subject = "[HIGH Q] Security Scan Report - " . ucfirst($scanType) . " Scan";
        
        // Use brief summary in email body, attach PDF for full details
        $emailBody = "<h2>Security Scan Summary</h2><pre style='font-family: monospace; background: #f4f4f4; padding: 15px; border-radius: 8px;'>{$briefSummary}</pre>";
        $emailBody .= "<hr><p>📎 Full detailed report is attached as PDF.</p>";
        $emailBody .= $htmlReport;
        
        // Prepare attachment
        $attachments = [];
        if ($pdfPath && file_exists($pdfPath)) {
            $attachments[] = $pdfPath;
        }
        
        $sent = sendEmail(
            $recipientEmail,
            $subject,
            $emailBody,
            $attachments
        );
        
        if ($sent) {
            $response['sent'] = true;
            $response['message'] = 'Report generated and sent successfully';
            
            // Log the action
            logAction($pdo, $_SESSION['user_id'] ?? 0, 'security_report_sent', [
                'scan_type' => $scanType,
                'recipient' => $recipientEmail,
                'pdf_attached' => !empty($pdfPath),
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
    
    $scanType = $scanData['report']['scan_type'] ?? 'security';
    $timestamp = date('Y-m-d_H-i-s');
    $reportFile = $reportDir . DIRECTORY_SEPARATOR . "report_{$scanType}_{$timestamp}.json";
    @file_put_contents($reportFile, json_encode([
        'timestamp' => $timestamp,
        'scan_data' => $scanData,
        'recipient' => $recipientEmail,
        'sent' => $response['sent'],
        'pdf_path' => $pdfPath
    ], JSON_PRETTY_PRINT));
    
    echo json_encode($response);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

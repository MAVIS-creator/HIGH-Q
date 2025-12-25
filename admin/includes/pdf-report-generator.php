<?php
/**
 * admin/includes/pdf-report-generator.php
 * 
 * Generates professional PDF security scan reports using DomPDF
 * 
 * Features:
 * - Executive summary with risk assessment
 * - Detailed findings with file locations and line numbers
 * - List of all scanned files
 * - Remediation recommendations
 * - Professional styling with HQ branding
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfReportGenerator {
    private $companyName = 'HIGH Q SOLID ACADEMY';
    private $report;
    private $scanType;
    private $timestamp;
    
    public function __construct($scanData) {
        $this->report = $scanData['report'] ?? $scanData;
        $this->scanType = $this->report['scan_type'] ?? 'security';
        $this->timestamp = date('Y-m-d H:i:s');
    }
    
    /**
     * Generate PDF and return file path
     */
    public function generate(): string {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($options);
        $html = $this->buildHtml();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Save to storage
        $storageDir = __DIR__ . '/../../storage/reports';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
        
        $filename = 'security_report_' . date('Y-m-d_H-i-s') . '.pdf';
        $filepath = $storageDir . '/' . $filename;
        
        file_put_contents($filepath, $dompdf->output());
        
        return $filepath;
    }
    
    /**
     * Return PDF as string (for attachment)
     */
    public function getContent(): string {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($options);
        $html = $this->buildHtml();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    }
    
    /**
     * Get brief text summary for email body
     */
    public function getBriefSummary(): string {
        $critical = count($this->report['critical'] ?? []);
        $warnings = count($this->report['warnings'] ?? []);
        $info = count($this->report['info'] ?? []);
        $filesScanned = $this->report['totals']['files_scanned'] ?? 0;
        
        $riskLevel = $this->getRiskLevel($critical, $warnings);
        
        $summary = "=== HIGH Q SECURITY SCAN SUMMARY ===\n\n";
        $summary .= "Scan Type: " . ucfirst($this->scanType) . "\n";
        $summary .= "Date: {$this->timestamp}\n";
        $summary .= "Files Scanned: {$filesScanned}\n";
        $summary .= "Risk Level: {$riskLevel}\n\n";
        
        $summary .= "FINDINGS:\n";
        $summary .= "• Critical Issues: {$critical}\n";
        $summary .= "• Warnings: {$warnings}\n";
        $summary .= "• Info Messages: {$info}\n\n";
        
        if ($critical > 0) {
            $summary .= "TOP CRITICAL ISSUES:\n";
            $criticalItems = array_slice($this->report['critical'] ?? [], 0, 3);
            foreach ($criticalItems as $item) {
                $msg = $item['message'] ?? $item['type'] ?? 'Security issue';
                $file = $item['file'] ?? '';
                $line = $item['line'] ?? '';
                $summary .= "• {$msg}";
                if ($file) $summary .= " [{$file}" . ($line ? ":{$line}" : "") . "]";
                $summary .= "\n";
            }
            $summary .= "\n";
        }
        
        $summary .= "Full detailed report is attached as PDF.\n";
        
        return $summary;
    }
    
    private function buildHtml(): string {
        $critical = $this->report['critical'] ?? [];
        $warnings = $this->report['warnings'] ?? [];
        $info = $this->report['info'] ?? [];
        $filesScanned = $this->report['totals']['files_scanned'] ?? 0;
        $scannedFiles = $this->report['scanned_files'] ?? [];
        
        $riskLevel = $this->getRiskLevel(count($critical), count($warnings));
        $riskColor = $this->getRiskColor($riskLevel);
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Security Scan Report - {$this->companyName}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 11px; 
            line-height: 1.5; 
            color: #333;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 30px;
            margin: -20px -20px 20px -20px;
            text-align: center;
        }
        
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { font-size: 12px; opacity: 0.9; }
        
        .meta-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .meta-row { display: table-row; }
        .meta-cell { 
            display: table-cell; 
            padding: 10px 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        .meta-label { 
            background: #f8f9fa; 
            font-weight: bold; 
            width: 150px;
        }
        
        .risk-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 12px;
            color: white;
            background: {$riskColor};
        }
        
        .summary-grid {
            display: table;
            width: 100%;
            margin: 20px 0;
        }
        
        .summary-card {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        
        .summary-card.critical { border-top: 4px solid #dc2626; }
        .summary-card.warning { border-top: 4px solid #f59e0b; }
        .summary-card.info { border-top: 4px solid #3b82f6; }
        
        .summary-value { font-size: 28px; font-weight: bold; }
        .summary-card.critical .summary-value { color: #dc2626; }
        .summary-card.warning .summary-value { color: #f59e0b; }
        .summary-card.info .summary-value { color: #3b82f6; }
        
        .summary-label { font-size: 10px; color: #666; text-transform: uppercase; }
        
        .section { margin: 25px 0; }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        .finding {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #6b7280;
            padding: 12px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        
        .finding.critical { border-left-color: #dc2626; background: #fef2f2; }
        .finding.warning { border-left-color: #f59e0b; background: #fffbeb; }
        .finding.info { border-left-color: #3b82f6; background: #eff6ff; }
        
        .finding-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        
        .finding.critical .finding-type { background: #dc2626; color: white; }
        .finding.warning .finding-type { background: #f59e0b; color: white; }
        .finding.info .finding-type { background: #3b82f6; color: white; }
        
        .finding-message { font-weight: 600; margin-bottom: 4px; }
        .finding-file { font-family: monospace; font-size: 10px; color: #666; }
        .finding-line { color: #dc2626; font-weight: bold; }
        
        .files-list {
            font-family: monospace;
            font-size: 9px;
            columns: 2;
            column-gap: 20px;
        }
        
        .files-list div {
            padding: 3px 0;
            border-bottom: 1px dotted #e5e7eb;
            page-break-inside: avoid;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛡️ Security Scan Report</h1>
        <p>{$this->companyName}</p>
    </div>
    
    <div class="meta-info">
        <div class="meta-row">
            <div class="meta-cell meta-label">Scan Type</div>
            <div class="meta-cell">HTML</div>
        </div>
        <div class="meta-row">
            <div class="meta-cell meta-label">Generated</div>
            <div class="meta-cell">{$this->timestamp}</div>
        </div>
        <div class="meta-row">
            <div class="meta-cell meta-label">Files Scanned</div>
            <div class="meta-cell">{$filesScanned}</div>
        </div>
        <div class="meta-row">
            <div class="meta-cell meta-label">Risk Level</div>
            <div class="meta-cell"><span class="risk-badge">{$riskLevel}</span></div>
        </div>
    </div>
    
    <div class="summary-grid">
        <div class="summary-card critical">
            <div class="summary-value">HTML</div>
            <div class="summary-label">Critical Issues</div>
        </div>
        <div class="summary-card warning">
            <div class="summary-value">HTML</div>
            <div class="summary-label">Warnings</div>
        </div>
        <div class="summary-card info">
            <div class="summary-value">HTML</div>
            <div class="summary-label">Info Messages</div>
        </div>
    </div>
HTML;

        // Replace placeholders
        $scanTypeUpper = ucfirst($this->scanType);
        $html = str_replace(
            ['HTML</div>
        </div>
        <div class="meta-row">', 
             '<div class="summary-value">HTML</div>
            <div class="summary-label">Critical Issues</div>',
             '<div class="summary-value">HTML</div>
            <div class="summary-label">Warnings</div>',
             '<div class="summary-value">HTML</div>
            <div class="summary-label">Info Messages</div>'],
            ["{$scanTypeUpper}</div>
        </div>
        <div class=\"meta-row\">",
             "<div class=\"summary-value\">" . count($critical) . "</div>
            <div class=\"summary-label\">Critical Issues</div>",
             "<div class=\"summary-value\">" . count($warnings) . "</div>
            <div class=\"summary-label\">Warnings</div>",
             "<div class=\"summary-value\">" . count($info) . "</div>
            <div class=\"summary-label\">Info Messages</div>"],
            $html
        );
        
        // Critical findings section
        if (count($critical) > 0) {
            $html .= '<div class="section"><div class="section-title">🚨 Critical Issues (' . count($critical) . ')</div>';
            foreach ($critical as $item) {
                $html .= $this->renderFinding($item, 'critical');
            }
            $html .= '</div>';
        }
        
        // Warning findings section
        if (count($warnings) > 0) {
            $html .= '<div class="section"><div class="section-title">⚠️ Warnings (' . count($warnings) . ')</div>';
            foreach ($warnings as $item) {
                $html .= $this->renderFinding($item, 'warning');
            }
            $html .= '</div>';
        }
        
        // Info section
        if (count($info) > 0) {
            $html .= '<div class="section"><div class="section-title">ℹ️ Informational (' . count($info) . ')</div>';
            foreach (array_slice($info, 0, 20) as $item) {
                $html .= $this->renderFinding($item, 'info');
            }
            if (count($info) > 20) {
                $html .= '<p style="color: #666; font-style: italic;">... and ' . (count($info) - 20) . ' more info messages</p>';
            }
            $html .= '</div>';
        }
        
        // Scanned files section
        if (count($scannedFiles) > 0) {
            $html .= '<div class="page-break"></div>';
            $html .= '<div class="section"><div class="section-title">📁 Scanned Files (' . count($scannedFiles) . ')</div>';
            $html .= '<div class="files-list">';
            foreach ($scannedFiles as $file) {
                $html .= '<div>' . htmlspecialchars($file) . '</div>';
            }
            $html .= '</div></div>';
        }
        
        // Footer
        $html .= <<<HTML
    <div class="footer">
        <p>Generated by {$this->companyName} Security Scanner</p>
        <p>Report ID: HTML | Confidential</p>
    </div>
</body>
</html>
HTML;
        
        $reportId = substr(md5($this->timestamp . json_encode($this->report)), 0, 12);
        $html = str_replace('Report ID: HTML', "Report ID: {$reportId}", $html);
        
        return $html;
    }
    
    private function renderFinding(array $item, string $type): string {
        $message = htmlspecialchars($item['message'] ?? $item['type'] ?? 'Issue detected');
        $file = htmlspecialchars($item['file'] ?? '');
        $line = $item['line'] ?? '';
        $typeLabel = strtoupper($item['type'] ?? $type);
        
        $html = "<div class=\"finding {$type}\">";
        $html .= "<span class=\"finding-type\">{$typeLabel}</span>";
        $html .= "<div class=\"finding-message\">{$message}</div>";
        
        if ($file) {
            $html .= "<div class=\"finding-file\">📄 {$file}";
            if ($line) {
                $html .= " <span class=\"finding-line\">Line {$line}</span>";
            }
            $html .= "</div>";
        }
        
        $html .= "</div>";
        
        return $html;
    }
    
    private function getRiskLevel(int $critical, int $warnings): string {
        if ($critical > 0) return 'CRITICAL';
        if ($warnings > 3) return 'HIGH';
        if ($warnings > 0) return 'MEDIUM';
        return 'LOW';
    }
    
    private function getRiskColor(string $level): string {
        return match($level) {
            'CRITICAL' => '#dc2626',
            'HIGH' => '#ea580c',
            'MEDIUM' => '#f59e0b',
            default => '#22c55e'
        };
    }
}

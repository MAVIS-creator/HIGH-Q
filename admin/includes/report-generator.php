<?php
/**
 * admin/includes/report-generator.php
 * 
 * Generates professional, HTML-styled security scan reports
 * for email distribution.
 * 
 * Supports:
 * - Custom branding and colors
 * - Threat summary with color coding
 * - Detailed findings list
 * - Remediation recommendations
 * - Responsive email design
 */

class ReportGenerator {
    private $companyName = 'HIGH Q SOLID ACADEMY';
    private $companyEmail = 'highqsolidacademy@gmail.com';
    private $report;
    private $scanType;
    
    public function __construct($scanData) {
        $this->report = $scanData;
        $this->scanType = $scanData['report']['scan_type'] ?? 'unknown';
    }
    
    /**
     * Generate HTML email body
     */
    public function generateHtmlEmail() {
        $critical = count($this->report['report']['critical'] ?? []);
        $warnings = count($this->report['report']['warnings'] ?? []);
        $info = count($this->report['report']['info'] ?? []);
        $filesScanned = $this->report['report']['totals']['files_scanned'] ?? 0;
        
        $riskLevel = $this->getRiskLevel($critical, $warnings);
        $riskColor = $this->getRiskColor($riskLevel);
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Scan Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Risk Badge */
        .risk-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .risk-critical { background-color: #fee; color: #c33; }
        .risk-warning { background-color: #ffeaa7; color: #d63031; }
        .risk-safe { background-color: #d4edda; color: #155724; }
        
        /* Content */
        .content {
            padding: 30px 20px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        /* Summary Grid */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .summary-card.critical {
            border-left-color: #dc3545;
        }
        
        .summary-card.warning {
            border-left-color: #ffc107;
        }
        
        .summary-card.info {
            border-left-color: #17a2b8;
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .summary-card.critical .summary-value {
            color: #dc3545;
        }
        
        .summary-card.warning .summary-value {
            color: #ff9800;
        }
        
        /* Findings */
        .finding {
            background: #f9f9f9;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        
        .finding.critical {
            background: #ffebee;
            border-left-color: #dc3545;
        }
        
        .finding.warning {
            background: #fff3e0;
            border-left-color: #ff9800;
        }
        
        .finding.info {
            background: #e0f7ff;
            border-left-color: #17a2b8;
        }
        
        .finding-message {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .finding-file {
            font-size: 12px;
            color: #666;
            font-family: 'Courier New', monospace;
        }
        
        .finding-type {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            background: rgba(0,0,0,0.1);
            border-radius: 3px;
            margin-right: 8px;
            margin-top: 4px;
        }
        
        /* Recommendations */
        .recommendation {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #2196f3;
            margin-bottom: 10px;
        }
        
        .recommendation-title {
            font-weight: 600;
            color: #1565c0;
            margin-bottom: 5px;
        }
        
        .recommendation-text {
            font-size: 14px;
            color: #333;
        }
        
        /* Footer */
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #666;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .timestamp {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 20px 15px;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .content {
                padding: 15px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Security Scan Report</h1>
            <p>{$this->companyName}</p>
        </div>
        
        <div class="content">
            <!-- Risk Summary -->
            <div class="section">
                <div style="text-align: center;">
                    <div class="risk-badge risk-{$riskLevel}">
                        Risk Level: {$this->formatRiskLevel($riskLevel)}
                    </div>
                </div>
            </div>
            
            <!-- Summary Stats -->
            <div class="section">
                <div class="section-title">📊 Scan Summary</div>
                <div class="summary-grid">
                    <div class="summary-card critical">
                        <div class="summary-label">Critical Issues</div>
                        <div class="summary-value">{$critical}</div>
                    </div>
                    <div class="summary-card warning">
                        <div class="summary-label">Warnings</div>
                        <div class="summary-value">{$warnings}</div>
                    </div>
                    <div class="summary-card info">
                        <div class="summary-label">Files Scanned</div>
                        <div class="summary-value">{$filesScanned}</div>
                    </div>
                    <div class="summary-card info">
                        <div class="summary-label">Scan Type</div>
                        <div class="summary-value" style="text-transform: capitalize; font-size: 16px;">{$this->scanType}</div>
                    </div>
                </div>
            </div>
            
HTML;

        // Critical Findings
        if ($critical > 0) {
            $html .= $this->renderFindings('Critical Issues', 'critical');
        }
        
        // Warnings
        if ($warnings > 0) {
            $html .= $this->renderFindings('Warnings', 'warning');
        }
        
        // Recommendations
        $html .= $this->renderRecommendations($riskLevel);
        
        // Info
        if ($info > 0) {
            $html .= $this->renderFindings('Additional Information', 'info');
        }
        
        // Footer
        $timestamp = date('Y-m-d H:i:s');
        $html .= <<<HTML
            
        </div>
        
        <div class="footer">
            <p>This security scan was performed on <strong>{$timestamp}</strong></p>
            <p>For questions or concerns, contact: <a href="mailto:{$this->companyEmail}">{$this->companyEmail}</a></p>
            <p class="timestamp">Report generated by Security Scan Engine v1.0</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
    
    /**
     * Generate plain text version
     */
    public function generatePlainText() {
        $critical = count($this->report['report']['critical'] ?? []);
        $warnings = count($this->report['report']['warnings'] ?? []);
        $info = count($this->report['report']['info'] ?? []);
        $filesScanned = $this->report['report']['totals']['files_scanned'] ?? 0;
        $riskLevel = $this->getRiskLevel($critical, $warnings);
        
        $text = <<<TEXT
SECURITY SCAN REPORT
{$this->companyName}

RISK LEVEL: {$this->formatRiskLevel($riskLevel)}

SCAN SUMMARY
─────────────────────────────────
Scan Type: {$this->scanType}
Files Scanned: {$filesScanned}
Critical Issues: {$critical}
Warnings: {$warnings}
Info Messages: {$info}

CRITICAL ISSUES
─────────────────────────────────
TEXT;
        
        if ($critical > 0) {
            foreach (array_slice($this->report['report']['critical'] ?? [], 0, 10) as $issue) {
                $text .= "\n• " . $issue['message'];
                if (isset($issue['file'])) $text .= " (" . $issue['file'] . ")";
            }
        } else {
            $text .= "\nNo critical issues found.";
        }
        
        $text .= "\n\nWARNINGS\n─────────────────────────────────\n";
        if ($warnings > 0) {
            foreach (array_slice($this->report['report']['warnings'] ?? [], 0, 10) as $warning) {
                $text .= "\n• " . $warning['message'];
                if (isset($warning['file'])) $text .= " (" . $warning['file'] . ")";
            }
        } else {
            $text .= "No warnings found.";
        }
        
        $text .= "\n\nFor detailed information, please access the admin panel.\n";
        $text .= "Report generated: " . date('Y-m-d H:i:s') . "\n";
        
        return $text;
    }
    
    /**
     * Render findings section
     */
    private function renderFindings($title, $type) {
        $findings = $this->report['report'][$type === 'Additional Information' ? 'info' : $type] ?? [];
        if (empty($findings)) return '';
        
        $html = "<div class='section'>\n<div class='section-title'>";
        
        if ($type === 'critical') $html .= "🚨 ";
        elseif ($type === 'warning') $html .= "⚠️ ";
        else $html .= "ℹ️ ";
        
        $html .= "{$title}</div>\n";
        
        foreach (array_slice($findings, 0, 10) as $finding) {
            $html .= "<div class='finding {$type}'>\n";
            $html .= "<div class='finding-message'>" . htmlspecialchars($finding['message']) . "</div>\n";
            
            if (isset($finding['file'])) {
                $html .= "<div class='finding-file'>📄 " . htmlspecialchars($finding['file']) . "</div>\n";
            }
            
            if (isset($finding['type'])) {
                $html .= "<span class='finding-type'>" . htmlspecialchars($finding['type']) . "</span>\n";
            }
            
            $html .= "</div>\n";
        }
        
        if (count($findings) > 10) {
            $remaining = count($findings) - 10;
            $html .= "<p style='color: #999; font-size: 12px; margin-top: 10px;'>... and {$remaining} more</p>\n";
        }
        
        $html .= "</div>\n";
        
        return $html;
    }
    
    /**
     * Render recommendations
     */
    private function renderRecommendations($riskLevel) {
        $recommendations = [];
        
        if ($riskLevel === 'critical') {
            $recommendations[] = [
                'title' => 'Critical Risk Detected',
                'text' => 'Your system has critical security issues that require immediate attention. Please review the findings above and take corrective action as soon as possible.'
            ];
            $recommendations[] = [
                'title' => 'Incident Investigation',
                'text' => 'We recommend immediately investigating the source of these critical issues and checking logs for any unauthorized access.'
            ];
        }
        
        if ($riskLevel === 'critical' || $riskLevel === 'warning') {
            $recommendations[] = [
                'title' => 'Review and Patch',
                'text' => 'Install security patches for any known vulnerabilities, update dependencies, and review code for vulnerabilities.'
            ];
        }
        
        $recommendations[] = [
            'title' => 'Regular Monitoring',
            'text' => 'Schedule regular security scans to continuously monitor your system for threats and vulnerabilities.'
        ];
        
        $recommendations[] = [
            'title' => 'Backup & Recovery',
            'text' => 'Maintain regular backups of your system and ensure you have a disaster recovery plan in place.'
        ];
        
        $html = "<div class='section'>\n<div class='section-title'>💡 Recommendations</div>\n";
        
        foreach ($recommendations as $rec) {
            $html .= "<div class='recommendation'>\n";
            $html .= "<div class='recommendation-title'>" . htmlspecialchars($rec['title']) . "</div>\n";
            $html .= "<div class='recommendation-text'>" . htmlspecialchars($rec['text']) . "</div>\n";
            $html .= "</div>\n";
        }
        
        $html .= "</div>\n";
        
        return $html;
    }
    
    /**
     * Determine risk level
     */
    private function getRiskLevel($critical, $warnings) {
        if ($critical > 0) return 'critical';
        if ($warnings > 0) return 'warning';
        return 'safe';
    }
    
    /**
     * Format risk level for display
     */
    private function formatRiskLevel($level) {
        return ucfirst($level);
    }
    
    /**
     * Get risk color
     */
    private function getRiskColor($level) {
        return [
            'critical' => '#dc3545',
            'warning' => '#ff9800',
            'safe' => '#28a745'
        ][$level] ?? '#666';
    }
}

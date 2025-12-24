<?php
// Admin Security Scan page: Sentinel (Enhanced)
$pageTitle = 'Security Scan';
$pageSubtitle = 'Monitor security threats and run comprehensive scans';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requirePermission('sentinel');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Get latest scan reports
$latestScans = [];
try {
    $stmt = $pdo->query("
        SELECT id, scan_type, status, threat_count, scan_date, duration
        FROM security_scans 
        ORDER BY scan_date DESC 
        LIMIT 5
    ");
    $latestScans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
    $latestScans = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .sentinel-wrapper {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .sentinel-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.35);
            position: relative;
            overflow: hidden;
        }
        
        .sentinel-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: drift 20s linear infinite;
        }
        
        @keyframes drift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .sentinel-header-content {
            position: relative;
            z-index: 1;
        }
        
        .sentinel-header h1 {
            margin: 0 0 12px 0;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .sentinel-header p {
            margin: 0;
            opacity: 0.96;
            font-size: 15px;
            font-weight: 300;
        }
        
        .sentinel-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1200px) {
            .sentinel-container {
                grid-template-columns: 1fr;
            }
        }
        
        .scan-controls {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .scan-controls:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .scan-controls h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #1f2937;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .scan-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .scan-option {
            padding: 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafbfc;
        }
        
        .scan-option:hover {
            background: #f3f4f6;
            border-color: #6366f1;
        }
        
        .scan-option input[type="radio"] {
            margin-right: 10px;
            cursor: pointer;
        }
        
        .scan-option strong {
            font-size: 14px;
            color: #1f2937;
        }
        
        .scan-option small {
            display: block;
            color: #6b7280;
            margin-left: 24px;
            margin-top: 4px;
            font-size: 12px;
        }
        
        .email-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .email-label {
            font-size: 13px;
            color: #374151;
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        #reportEmail {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        #reportEmail:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .email-helper {
            color: #6b7280;
            display: block;
            margin-top: 6px;
            font-size: 12px;
        }
        
        .scan-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-scan {
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .btn-scan-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .btn-scan-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }
        
        .btn-scan-secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-scan-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }
        
        .btn-scan:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .scan-progress {
            display: none;
            background: linear-gradient(135deg, #f0fdf4 0%, #dbeafe 100%);
            border: 2px solid #86efac;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .scan-progress.active {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 12px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #6366f1 100%);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 12px;
            color: #059669;
            margin-top: 8px;
            font-weight: 600;
        }
        
        .threat-summary {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .threat-summary:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .threat-summary h3 {
            margin-top: 0;
            color: #1f2937;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .threat-boxes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        
        .threat-box {
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .threat-box:hover {
            transform: translateY(-2px);
        }
        
        .threat-box.critical {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #fca5a5;
            color: #991b1b;
        }
        
        .threat-box.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fef08a 100%);
            border-color: #fde68a;
            color: #92400e;
        }
        
        .threat-box.info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #93c5fd;
            color: #1e40af;
        }
        
        .threat-box strong {
            font-size: 32px;
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
        }
        
        .threat-box span {
            font-size: 13px;
            font-weight: 600;
        }
        
        .scan-reports {
            margin-top: 40px;
        }
        
        .scan-reports h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #1f2937;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .report-table th {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        
        .report-table tr:last-child td {
            border-bottom: none;
        }
        
        .report-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-badge.success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
        }
        
        .status-badge.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }
        
        .status-badge.danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            background: #fafbfc;
            border-radius: 12px;
            border: 2px dashed #e5e7eb;
        }
        
        .empty-state i {
            font-size: 52px;
            opacity: 0.3;
            margin-bottom: 16px;
            display: block;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="admin-main sentinel-wrapper">
    <div class="sentinel-header">
        <div class="sentinel-header-content">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <p><?= htmlspecialchars($pageSubtitle) ?></p>
        </div>
    </div>

    <div class="sentinel-container">
        <!-- Scan Controls -->
        <div class="scan-controls">
            <h3><i class='bx bx-play-circle' style="font-size: 20px;"></i> Start Security Scan</h3>
            
            <div class="scan-options">
                <label class="scan-option">
                    <input type="radio" name="scan_type" value="quick" checked>
                    <strong>⚡ Quick Scan</strong>
                    <small>Check common vulnerabilities (2-5 min)</small>
                </label>
                <label class="scan-option">
                    <input type="radio" name="scan_type" value="full">
                    <strong>🔍 Full Scan</strong>
                    <small>Complete system audit (10-15 min)</small>
                </label>
                <label class="scan-option">
                    <input type="radio" name="scan_type" value="malware">
                    <strong>🦠 Malware Scan</strong>
                    <small>Detect suspicious files (5-10 min)</small>
                </label>
            </div>

            <div class="email-section">
                <label class="email-label">📧 Email Report To:</label>
                <input type="email" id="reportEmail" placeholder="Enter email address" />
                <small class="email-helper">Leave blank to use default company email</small>
            </div>

            <div class="scan-buttons">
                <button class="btn-scan btn-scan-primary" id="scanBtn" onclick="startScan()">
                    <i class='bx bx-play'></i> Start Scan
                </button>
                <button class="btn-scan btn-scan-secondary" onclick="emailReport()">
                    <i class='bx bx-envelope'></i> Email Report
                </button>
            </div>

            <!-- Scanning Progress -->
            <div class="scan-progress" id="scanProgress">
                <p id="scanStatus" style="margin: 0 0 12px 0; font-weight: 600; color: #059669;">Initializing scan...</p>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <p class="progress-text" id="progressText">0%</p>
            </div>
        </div>

        <!-- Threat Summary -->
        <div class="threat-summary">
            <h3><i class='bx bx-bar-chart-alt' style="font-size: 20px;"></i> Latest Scan Summary</h3>
            <div class="threat-boxes">
                <div class="threat-box critical" id="criticalCount">
                    <strong>0</strong>
                    <span>Critical</span>
                </div>
                <div class="threat-box warning" id="warningCount">
                    <strong>0</strong>
                    <span>Warnings</span>
                </div>
                <div class="threat-box info" id="infoCount">
                    <strong>0</strong>
                    <span>Info</span>
                </div>
            </div>
            <p style="margin-top: 20px; color: #6b7280; font-size: 13px; font-weight: 500;" id="lastScanTime">
                No scans performed yet
            </p>
        </div>
    </div>

    <!-- Scan Reports -->
    <div class="scan-reports">
        <h3><i class='bx bx-history' style="font-size: 20px;"></i> Scan Reports</h3>
        <?php if (count($latestScans) > 0): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Scan Type</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Threats Found</th>
                        <th>Duration</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestScans as $scan): ?>
                        <tr>
                            <td>
                                <?php if ($scan['scan_type'] === 'quick'): ?>
                                    <i class='bx bx-lightning-charge'></i> Quick Scan
                                <?php elseif ($scan['scan_type'] === 'full'): ?>
                                    <i class='bx bx-circle'></i> Full Scan
                                <?php elseif ($scan['scan_type'] === 'malware'): ?>
                                    <i class='bx bx-virus'></i> Malware Scan
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y H:i', strtotime($scan['scan_date'])) ?></td>
                            <td>
                                <?php if ($scan['status'] === 'completed'): ?>
                                    <span class="status-badge success">✓ Completed</span>
                                <?php elseif ($scan['status'] === 'running'): ?>
                                    <span class="status-badge warning">⟳ Running</span>
                                <?php else: ?>
                                    <span class="status-badge danger">✗ Error</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$scan['threat_count'] ?> threat<?= $scan['threat_count'] != 1 ? 's' : '' ?></td>
                            <td><?= htmlspecialchars($scan['duration']) ?>s</td>
                            <td><a href="#" onclick="viewReport(<?= $scan['id'] ?>); return false;" style="color: #6366f1; text-decoration: none; font-weight: 600;">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class='bx bxs-shield-alt'></i>
                <p>No scans performed yet. Click "Start Scan" to run your first security check.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function startScan() {
    const scanType = document.querySelector('input[name="scan_type"]:checked').value;
    const progressDiv = document.getElementById('scanProgress');
    const scanButton = document.getElementById('scanBtn');
    
    progressDiv.classList.add('active');
    scanButton.disabled = true;
    
    let progress = 0;
    const statusElement = document.getElementById('scanStatus');
    const fillElement = document.getElementById('progressFill');
    const textElement = document.getElementById('progressText');
    
    const phases = [
        'Initializing security scanner...',
        'Scanning files for threats...',
        'Checking configuration...',
        'Analyzing code patterns...',
        'Running dependency audit...',
        'Verifying integrity...',
        'Compiling report...',
        'Finalizing results...'
    ];
    
    let phaseIndex = 0;
    
    // Show progress while we wait for the actual scan
    const progressInterval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 12;
            if (progress > 90) progress = 90;
        }
        
        fillElement.style.width = Math.floor(progress) + '%';
        textElement.textContent = Math.floor(progress) + '%';
        
        if (phaseIndex < phases.length && progress > (phaseIndex * 90 / phases.length)) {
            statusElement.textContent = phases[phaseIndex];
            phaseIndex++;
        }
    }, 300);
    
    // Call the actual backend API
    fetch('../api/scan-engine.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'scan_type=' + encodeURIComponent(scanType)
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(progressInterval);
        progress = 100;
        
        fillElement.style.width = '100%';
        textElement.textContent = '100%';
        statusElement.textContent = 'Scan completed!';
        scanButton.disabled = false;
        
        if (data.status === 'ok' && data.report) {
            // Display real results
            updateScanSummary(data.report);
        } else {
            console.error('Scan error:', data.message);
            statusElement.textContent = 'Error: ' + (data.message || 'Unknown error');
        }
    })
    .catch(error => {
        clearInterval(progressInterval);
        console.error('Scan failed:', error);
        statusElement.textContent = 'Error: ' + error.message;
        scanButton.disabled = false;
    });
}

function updateScanSummary(report) {
    // Display real threat counts from backend
    const critical = report.totals.critical_issues || report.critical.length || 0;
    const warnings = report.totals.warnings || report.warnings.length || 0;
    const info = report.totals.info_messages || report.info.length || 0;
    
    document.getElementById('criticalCount').innerHTML = `<strong>${critical}</strong><span>Critical</span>`;
    document.getElementById('warningCount').innerHTML = `<strong>${warnings}</strong><span>Warnings</span>`;
    document.getElementById('infoCount').innerHTML = `<strong>${info}</strong><span>Info</span>`;
    
    // Store report data for view functionality
    window.lastScanReport = report;
    
    const now = new Date();
    document.getElementById('lastScanTime').textContent = `Last scan: ${now.toLocaleDateString()} at ${now.toLocaleTimeString()}`;
}

function viewReport(id) {
    if (window.lastScanReport) {
        console.log('Scan Report:', window.lastScanReport);
        const critical = window.lastScanReport.critical || [];
        const warnings = window.lastScanReport.warnings || [];
        const info = window.lastScanReport.info || [];
        
        let msg = 'Scan Report\n\n';
        msg += 'CRITICAL (' + critical.length + '):\n';
        critical.slice(0, 5).forEach(item => {
            msg += '  • ' + (item.message || item.type) + '\n';
        });
        
        msg += '\nWARNINGS (' + warnings.length + '):\n';
        warnings.slice(0, 5).forEach(item => {
            msg += '  • ' + (item.message || item.type) + '\n';
        });
        
        alert(msg);
    } else {
        alert('No scan data available. Run a scan first.');
    }
}

function emailReport() {
    if (!window.lastScanReport) {
        alert('No scan data available. Run a scan first.');
        return;
    }
    
    const recipient = document.getElementById('reportEmail').value || '';
    const button = event.target;
    button.disabled = true;
    button.textContent = '⟳ Sending...';
    
    // Prepare scan data for send-report API
    const reportData = {
        status: 'completed',
        report: window.lastScanReport
    };
    
    fetch('../api/send-report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            scan_data: reportData,
            recipient_email: recipient,
            send_email: true
        })
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;
        button.textContent = '✉️ Email Report';
        
        if (data.status === 'success' && data.sent) {
            alert('✅ Report sent successfully to ' + (recipient || 'company email') + '!');
        } else if (data.status === 'success') {
            alert('Report generated but email could not be sent. Check email configuration.');
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        button.disabled = false;
        button.textContent = '✉️ Email Report';
        alert('Error sending report: ' + error.message);
    });
}
</script>

</body>
</html>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

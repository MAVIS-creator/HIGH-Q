<?php
// Admin Security Scan page: Sentinel
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
        .sentinel-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1200px) {
            .sentinel-container {
                grid-template-columns: 1fr;
            }
        }
        
        .scan-controls {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .scan-controls h3 {
            margin-top: 0;
            margin-bottom: 16px;
            color: #1f2937;
        }
        
        .scan-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .scan-option {
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .scan-option:hover {
            background: #f3f4f6;
            border-color: #6366f1;
        }
        
        .scan-option input[type="radio"] {
            margin-right: 8px;
        }
        
        .scan-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-scan {
            flex: 1;
            padding: 10px 16px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-scan:hover {
            background: #4f46e5;
        }
        
        .btn-scan:disabled {
            background: #d1d5db;
            cursor: not-allowed;
        }
        
        .scan-progress {
            display: none;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .scan-progress.active {
            display: block;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 12px;
        }
        
        .progress-fill {
            height: 100%;
            background: #10b981;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 12px;
            color: #059669;
            margin-top: 8px;
        }
        
        .threat-summary {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .threat-summary h3 {
            margin-top: 0;
            color: #1f2937;
        }
        
        .threat-boxes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .threat-box {
            padding: 16px;
            border-radius: 6px;
            text-align: center;
        }
        
        .threat-box.critical {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        
        .threat-box.warning {
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #92400e;
        }
        
        .threat-box.info {
            background: #dbeafe;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }
        
        .threat-box strong {
            font-size: 24px;
            display: block;
            margin-bottom: 4px;
        }
        
        .threat-box span {
            font-size: 12px;
        }
        
        .scan-reports {
            margin-top: 30px;
        }
        
        .scan-reports h3 {
            margin-top: 0;
            margin-bottom: 16px;
            color: #1f2937;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .report-table th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        
        .report-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        
        .report-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-badge.success {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
<div class="admin-main">
    <div class="page-header">
        <h1><i class='bx bxs-shield-alt' style="vertical-align: middle; margin-right: 8px;"></i><?= htmlspecialchars($pageTitle) ?></h1>
        <p><?= htmlspecialchars($pageSubtitle) ?></p>
    </div>

    <div class="sentinel-container">
        <!-- Scan Controls -->
        <div class="scan-controls">
            <h3><i class='bx bx-play-circle'></i> Start Security Scan</h3>
            
            <div class="scan-options">
                <label class="scan-option">
                    <input type="radio" name="scan_type" value="quick" checked>
                    <strong>Quick Scan</strong>
                    <small style="display: block; color: #6b7280; margin-left: 24px; margin-top: 4px;">Check common vulnerabilities (2-5 min)</small>
                </label>
                <label class="scan-option">
                    <input type="radio" name="scan_type" value="full">
                    <strong>Full Scan</strong>
                    <small style="display: block; color: #6b7280; margin-left: 24px; margin-top: 4px;">Complete system audit (10-15 min)</small>
                </label>
                <label class="scan-option">
                    <input type="radio" name="scan_type" value="malware">
                    <strong>Malware Scan</strong>
                    <small style="display: block; color: #6b7280; margin-left: 24px; margin-top: 4px;">Detect suspicious files (5-10 min)</small>
                </label>
            </div>

            <div class="scan-buttons">
                <button class="btn-scan" onclick="startScan()">
                    <i class='bx bx-play'></i> Start Scan
                </button>
            </div>

            <!-- Scanning Progress -->
            <div class="scan-progress" id="scanProgress">
                <p id="scanStatus">Initializing scan...</p>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <p class="progress-text" id="progressText">0%</p>
            </div>
        </div>

        <!-- Threat Summary -->
        <div class="threat-summary">
            <h3><i class='bx bx-bar-chart'></i> Latest Scan Summary</h3>
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
            <p style="margin-top: 16px; color: #6b7280; font-size: 13px;" id="lastScanTime">
                No scans performed yet
            </p>
        </div>
    </div>

    <!-- Scan Reports -->
    <div class="scan-reports">
        <h3><i class='bx bx-history'></i> Scan Reports</h3>
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
                            <td><a href="#" onclick="viewReport(<?= $scan['id'] ?>)" style="color: #6366f1; text-decoration: none;">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class='bx bx-shield-quarter'></i>
                <p>No scans performed yet. Click "Start Scan" to run your first security check.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function startScan() {
    const scanType = document.querySelector('input[name="scan_type"]:checked').value;
    const progressDiv = document.getElementById('scanProgress');
    const scanButton = document.querySelector('.btn-scan');
    
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
</script>
</script>

</body>
</html>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
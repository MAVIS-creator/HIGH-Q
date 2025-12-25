<?php
// Admin Security Scan page: Sentinel - Full-page layout
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
    $latestScans = [];
}
?>
<div class="sentinel-fullscreen">
    <!-- Hero Header -->
    <div class="sentinel-hero">
        <div class="sentinel-hero-content">
            <div class="sentinel-hero-text">
                <span class="sentinel-hero-badge"><i class='bx bx-shield-quarter'></i> Security Automation</span>
                <h1 class="sentinel-hero-title"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="sentinel-hero-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
            </div>
            <div class="sentinel-hero-actions">
                <span class="hero-status-pill hero-status-pill--success">
                    <span class="status-dot status-dot--success"></span>
                    <span>Engine Ready</span>
                </span>
                <span class="hero-status-pill">
                    <i class='bx bx-shield'></i>
                    <span>Real-time defenses</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="sentinel-main">
        <div class="sentinel-grid">
            <!-- Scan Controls -->
            <div class="sentinel-card sentinel-card--wide">
                <div class="sentinel-card-header">
                    <div class="sentinel-card-title-group">
                        <p class="sentinel-card-label">Run a scan</p>
                        <h2 class="sentinel-card-title">Scan orchestrator</h2>
                    </div>
                    <span class="sentinel-status-badge sentinel-status-badge--success">
                        <span class="status-dot status-dot--success"></span>
                        Engine idle
                    </span>
                </div>

                <div class="sentinel-card-body">
                    <div class="scan-type-grid">
                        <label class="scan-type-option">
                            <input type="radio" name="scan_type" value="quick" checked>
                            <div class="scan-type-content">
                                <div class="scan-type-icon scan-type-icon--amber">
                                    <i class='bx bx-bolt-circle'></i>
                                </div>
                                <div class="scan-type-info">
                                    <p class="scan-type-name">Quick Scan</p>
                                    <p class="scan-type-desc">Fast health check (2-5 min)</p>
                                </div>
                            </div>
                            <div class="scan-type-ring"></div>
                        </label>

                        <label class="scan-type-option">
                            <input type="radio" name="scan_type" value="full">
                            <div class="scan-type-content">
                                <div class="scan-type-icon scan-type-icon--amber">
                                    <i class='bx bx-target-lock'></i>
                                </div>
                                <div class="scan-type-info">
                                    <p class="scan-type-name">Full Scan</p>
                                    <p class="scan-type-desc">Complete audit (10-15 min)</p>
                                </div>
                            </div>
                            <div class="scan-type-ring"></div>
                        </label>

                        <label class="scan-type-option">
                            <input type="radio" name="scan_type" value="malware">
                            <div class="scan-type-content">
                                <div class="scan-type-icon scan-type-icon--rose">
                                    <i class='bx bx-bug-alt'></i>
                                </div>
                                <div class="scan-type-info">
                                    <p class="scan-type-name">Malware Scan</p>
                                    <p class="scan-type-desc">Threat sweep (5-10 min)</p>
                                </div>
                            </div>
                            <div class="scan-type-ring"></div>
                        </label>
                    </div>

                    <div class="scan-actions-row">
                        <div class="scan-email-field">
                            <label for="reportEmail">Email report to</label>
                            <input id="reportEmail" type="email" placeholder="Leave blank to use company email" />
                            <p class="scan-email-hint">We will send the latest scan report when you click Email Report.</p>
                        </div>
                        <div class="scan-buttons">
                            <button id="scanBtn" onclick="startScan()" class="btn-scan btn-scan--primary">
                                <i class='bx bx-play'></i>
                                Start Scan
                            </button>
                            <button onclick="emailReport()" class="btn-scan btn-scan--secondary">
                                <i class='bx bx-envelope'></i>
                                Email Report
                            </button>
                        </div>
                    </div>

                    <div id="scanProgress" class="scan-progress hidden">
                        <div class="scan-progress-header">
                            <span id="scanStatus">Initializing scan...</span>
                            <span id="progressText">0%</span>
                        </div>
                        <div class="scan-progress-bar">
                            <div id="progressFill" class="scan-progress-fill"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Threat Dashboard -->
            <div class="sentinel-card">
                <div class="sentinel-card-header">
                    <div class="sentinel-card-title-group">
                        <p class="sentinel-card-label">Latest results</p>
                        <h2 class="sentinel-card-title">Threat dashboard</h2>
                    </div>
                </div>
                <div class="sentinel-card-body">
                    <div class="threat-stats-grid">
                        <div class="threat-stat threat-stat--critical">
                            <p class="threat-stat-label">Critical</p>
                            <p id="criticalCount" class="threat-stat-value">0</p>
                        </div>
                        <div class="threat-stat threat-stat--warning">
                            <p class="threat-stat-label">Warnings</p>
                            <p id="warningCount" class="threat-stat-value">0</p>
                        </div>
                        <div class="threat-stat threat-stat--info">
                            <p class="threat-stat-label">Info</p>
                            <p id="infoCount" class="threat-stat-value">0</p>
                        </div>
                    </div>
                    <p id="lastScanTime" class="last-scan-time">No scans performed yet</p>
                </div>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="sentinel-card sentinel-card--table">
            <div class="sentinel-card-header">
                <div class="sentinel-card-title-group">
                    <p class="sentinel-card-label">History</p>
                    <h2 class="sentinel-card-title">Recent scan reports</h2>
                </div>
            </div>
            <?php if (count($latestScans) > 0): ?>
            <div class="sentinel-table-wrapper">
                <table class="sentinel-table">
                    <thead>
                        <tr>
                            <th>Scan Type</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Threats</th>
                            <th>Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latestScans as $scan): ?>
                        <tr>
                            <td>
                                <?php if ($scan['scan_type'] === 'quick'): ?>
                                    <span class="scan-type-badge scan-type-badge--quick"><i class='bx bx-bolt-circle'></i>Quick</span>
                                <?php elseif ($scan['scan_type'] === 'full'): ?>
                                    <span class="scan-type-badge scan-type-badge--full"><i class='bx bx-target-lock'></i>Full</span>
                                <?php else: ?>
                                    <span class="scan-type-badge scan-type-badge--malware"><i class='bx bx-bug-alt'></i>Malware</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y H:i', strtotime($scan['scan_date'])) ?></td>
                            <td>
                                <?php if ($scan['status'] === 'completed'): ?>
                                    <span class="status-pill status-pill--success"><i class='bx bx-check-circle'></i> Completed</span>
                                <?php elseif ($scan['status'] === 'running'): ?>
                                    <span class="status-pill status-pill--warning"><i class='bx bx-loader-alt bx-spin'></i> Running</span>
                                <?php else: ?>
                                    <span class="status-pill status-pill--danger"><i class='bx bx-x-circle'></i> Error</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$scan['threat_count'] ?> threat<?= $scan['threat_count'] != 1 ? 's' : '' ?></td>
                            <td><?= htmlspecialchars($scan['duration']) ?>s</td>
                            <td>
                                <a href="#" onclick="viewReport(<?= $scan['id'] ?>); return false;" class="view-report-link">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="sentinel-empty-state">
                <div class="sentinel-empty-icon">
                    <i class='bx bxs-shield-alt-2'></i>
                </div>
                <p class="sentinel-empty-title">No scans performed yet</p>
                <p class="sentinel-empty-desc">Run your first scan to see reports here.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function startScan() {
    const scanType = document.querySelector('input[name="scan_type"]:checked').value;
    const progressDiv = document.getElementById('scanProgress');
    const scanButton = document.getElementById('scanBtn');

    progressDiv.classList.remove('hidden');
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
            updateScanSummary(data.report);
        } else {
            statusElement.textContent = 'Error: ' + (data.message || 'Unknown error');
        }
    })
    .catch(error => {
        clearInterval(progressInterval);
        statusElement.textContent = 'Error: ' + error.message;
        scanButton.disabled = false;
    });
}

function updateScanSummary(report) {
    const critical = report.totals?.critical_issues ?? (report.critical?.length || 0);
    const warnings = report.totals?.warnings ?? (report.warnings?.length || 0);
    const info = report.totals?.info_messages ?? (report.info?.length || 0);

    document.getElementById('criticalCount').textContent = critical;
    document.getElementById('warningCount').textContent = warnings;
    document.getElementById('infoCount').textContent = info;

    window.lastScanReport = report;
    const now = new Date();
    document.getElementById('lastScanTime').textContent = `Last scan: ${now.toLocaleDateString()} at ${now.toLocaleTimeString()}`;
    
    // Auto-show report modal after scan
    showReportModal();
}

function viewReport(id) {
    if (!window.lastScanReport) {
        Swal.fire({
            icon: 'info',
            title: 'No Scan Data',
            text: 'Run a scan first to view reports.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    showReportModal();
}

function showReportModal() {
    const report = window.lastScanReport;
    if (!report) return;
    
    const critical = report.critical || [];
    const warnings = report.warnings || [];
    const info = report.info || [];
    const filesScanned = report.totals?.files_scanned || report.scanned_files?.length || 0;
    const scannedFiles = report.scanned_files || [];
    
    // Risk level
    const riskLevel = critical.length > 0 ? 'CRITICAL' : (warnings.length > 3 ? 'HIGH' : (warnings.length > 0 ? 'MEDIUM' : 'LOW'));
    const riskColors = { CRITICAL: '#dc2626', HIGH: '#ea580c', MEDIUM: '#f59e0b', LOW: '#22c55e' };
    
    let html = `
        <div style="text-align: left; max-height: 60vh; overflow-y: auto;">
            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;">
                <div style="background: linear-gradient(135deg, #fef2f2, #fee2e2); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #fecaca;">
                    <div style="font-size: 28px; font-weight: bold; color: #dc2626;">${critical.length}</div>
                    <div style="font-size: 11px; color: #991b1b; text-transform: uppercase; font-weight: 600;">Critical</div>
                </div>
                <div style="background: linear-gradient(135deg, #fffbeb, #fef3c7); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #fde68a;">
                    <div style="font-size: 28px; font-weight: bold; color: #d97706;">${warnings.length}</div>
                    <div style="font-size: 11px; color: #92400e; text-transform: uppercase; font-weight: 600;">Warnings</div>
                </div>
                <div style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #bfdbfe;">
                    <div style="font-size: 28px; font-weight: bold; color: #2563eb;">${info.length}</div>
                    <div style="font-size: 11px; color: #1e40af; text-transform: uppercase; font-weight: 600;">Info</div>
                </div>
                <div style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #bbf7d0;">
                    <div style="font-size: 28px; font-weight: bold; color: #16a34a;">${filesScanned}</div>
                    <div style="font-size: 11px; color: #166534; text-transform: uppercase; font-weight: 600;">Files</div>
                </div>
            </div>
            
            <!-- Risk Badge -->
            <div style="text-align: center; margin-bottom: 20px;">
                <span style="display: inline-block; padding: 8px 20px; border-radius: 20px; background: ${riskColors[riskLevel]}; color: white; font-weight: bold; font-size: 12px;">
                    ${riskLevel === 'CRITICAL' ? '🚨' : riskLevel === 'HIGH' ? '⚠️' : riskLevel === 'MEDIUM' ? '⚡' : '✅'} Risk Level: ${riskLevel}
                </span>
            </div>
    `;
    
    // Critical Issues
    if (critical.length > 0) {
        html += `<div style="margin-bottom: 16px;"><h4 style="font-size: 14px; font-weight: bold; color: #dc2626; margin-bottom: 8px; border-bottom: 2px solid #dc2626; padding-bottom: 4px;">🚨 Critical Issues</h4>`;
        critical.slice(0, 5).forEach(item => {
            html += renderFinding(item, 'critical');
        });
        if (critical.length > 5) html += `<p style="font-size: 11px; color: #666; font-style: italic;">... and ${critical.length - 5} more</p>`;
        html += `</div>`;
    }
    
    // Warnings
    if (warnings.length > 0) {
        html += `<div style="margin-bottom: 16px;"><h4 style="font-size: 14px; font-weight: bold; color: #d97706; margin-bottom: 8px; border-bottom: 2px solid #d97706; padding-bottom: 4px;">⚠️ Warnings</h4>`;
        warnings.slice(0, 5).forEach(item => {
            html += renderFinding(item, 'warning');
        });
        if (warnings.length > 5) html += `<p style="font-size: 11px; color: #666; font-style: italic;">... and ${warnings.length - 5} more</p>`;
        html += `</div>`;
    }
    
    // Info
    if (info.length > 0) {
        html += `<div style="margin-bottom: 16px;"><h4 style="font-size: 14px; font-weight: bold; color: #2563eb; margin-bottom: 8px; border-bottom: 2px solid #2563eb; padding-bottom: 4px;">ℹ️ Information</h4>`;
        info.slice(0, 3).forEach(item => {
            html += renderFinding(item, 'info');
        });
        if (info.length > 3) html += `<p style="font-size: 11px; color: #666; font-style: italic;">... and ${info.length - 3} more</p>`;
        html += `</div>`;
    }
    
    html += `</div>`;
    
    Swal.fire({
        title: '📊 Security Scan Report',
        html: html,
        width: '800px',
        showCancelButton: true,
        confirmButtonText: '<i class="bx bxs-file-pdf"></i> Download PDF',
        confirmButtonColor: '#dc2626',
        cancelButtonText: 'Close',
        cancelButtonColor: '#64748b',
        showDenyButton: true,
        denyButtonText: '<i class="bx bx-envelope"></i> Email Report',
        denyButtonColor: '#f59e0b',
        customClass: {
            popup: 'swal-wide'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            generatePdfReport();
        } else if (result.isDenied) {
            emailReport();
        }
    });
}

function renderFinding(item, type) {
    const colors = { critical: '#fef2f2', warning: '#fffbeb', info: '#eff6ff' };
    const borders = { critical: '#dc2626', warning: '#d97706', info: '#2563eb' };
    
    let html = `<div style="background: ${colors[type]}; border-left: 4px solid ${borders[type]}; padding: 10px 12px; margin-bottom: 8px; border-radius: 0 6px 6px 0;">`;
    html += `<div style="font-weight: 600; font-size: 12px; color: #1f2937;">${item.message || item.type || 'Issue detected'}</div>`;
    
    if (item.file) {
        html += `<div style="font-size: 11px; color: #6b7280; font-family: monospace; margin-top: 4px;">📄 ${item.file}`;
        if (item.line) html += ` <span style="color: ${borders[type]}; font-weight: bold;">Line ${item.line}</span>`;
        html += `</div>`;
    }
    
    if (item.snippet) {
        html += `<div style="font-size: 10px; color: #9ca3af; font-family: monospace; margin-top: 4px; background: #f3f4f6; padding: 4px 8px; border-radius: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(item.snippet)}</div>`;
    }
    
    html += `</div>`;
    return html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function generatePdfReport() {
    if (!window.lastScanReport) {
        Swal.fire('Error', 'No scan data available', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Generating PDF...',
        html: 'Creating your detailed report...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    const reportData = { status: 'completed', report: window.lastScanReport };
    
    fetch('../api/send-report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            scan_data: reportData,
            send_email: false,
            generate_pdf: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.pdf_path) {
            Swal.fire({
                icon: 'success',
                title: 'PDF Generated!',
                html: `<p>Report saved: <code>${data.pdf_filename}</code></p>
                       <a href="../storage/reports/${data.pdf_filename}" download class="swal2-confirm swal2-styled" style="display: inline-block; margin-top: 10px;">
                           <i class='bx bx-download'></i> Download PDF
                       </a>`,
                confirmButtonColor: '#22c55e'
            });
        } else {
            Swal.fire('Error', data.message || 'Failed to generate PDF', 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
}

function emailReport() {
    if (!window.lastScanReport) {
        Swal.fire({
            icon: 'info',
            title: 'No Scan Data',
            text: 'Run a scan first to email reports.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    const recipient = document.getElementById('reportEmail').value || '';
    
    Swal.fire({
        title: 'Sending Report...',
        html: 'Generating PDF and sending email...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    const reportData = { status: 'completed', report: window.lastScanReport };

    fetch('../api/send-report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            scan_data: reportData,
            recipient_email: recipient,
            send_email: true,
            generate_pdf: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.sent) {
            Swal.fire({
                icon: 'success',
                title: 'Report Sent!',
                html: `<p>Email sent to: <strong>${data.recipient}</strong></p>
                       ${data.pdf_filename ? '<p>PDF report attached ✓</p>' : ''}`,
                confirmButtonColor: '#22c55e'
            });
        } else if (data.status === 'success') {
            Swal.fire({
                icon: 'warning',
                title: 'Partial Success',
                text: 'Report generated but email could not be sent. Check email configuration.',
                confirmButtonColor: '#f59e0b'
            });
        } else {
            Swal.fire('Error', data.message || 'Unknown error', 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
}
</script>

</body>
</html>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

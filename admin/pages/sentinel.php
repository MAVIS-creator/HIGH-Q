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
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_scans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scan_type VARCHAR(20) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'completed',
        threat_count INT NOT NULL DEFAULT 0,
        report_file VARCHAR(255) NULL,
        scan_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        duration INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_scan_date (scan_date),
        INDEX idx_scan_type (scan_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try {
        $pdo->exec("ALTER TABLE security_scans ADD COLUMN report_file VARCHAR(255) NULL AFTER threat_count");
    } catch (Throwable $e) {
        // already exists
    }

    $stmt = $pdo->query("
        SELECT id, scan_type, status, threat_count, report_file, scan_date, duration
        FROM security_scans 
        ORDER BY scan_date DESC 
        LIMIT 5
    ");
    $latestScans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $latestScans = [];
}

// Fallback: load recent report files if DB history is empty
if (count($latestScans) === 0) {
    try {
        $reportDir = realpath(__DIR__ . '/../../storage/scan_reports');
        if ($reportDir && is_dir($reportDir)) {
            $files = glob($reportDir . DIRECTORY_SEPARATOR . '*.json') ?: [];
            rsort($files);
            foreach (array_slice($files, 0, 5) as $index => $file) {
                $raw = @file_get_contents($file);
                $json = $raw ? json_decode($raw, true) : null;
                $scan = $json['scan_data']['report'] ?? null;
                if (!is_array($scan)) continue;

                $critical = count($scan['critical'] ?? []);
                $warnings = count($scan['warnings'] ?? []);
                $started = !empty($scan['started_at']) ? strtotime($scan['started_at']) : time();
                $finished = !empty($scan['finished_at']) ? strtotime($scan['finished_at']) : $started;

                $latestScans[] = [
                    'id' => 0 - ($index + 1),
                    'scan_type' => $scan['scan_type'] ?? 'quick',
                    'status' => ($scan['status'] ?? 'completed') === 'completed' ? 'completed' : 'error',
                    'threat_count' => $critical + $warnings,
                    'report_file' => basename($file),
                    'scan_date' => date('Y-m-d H:i:s', $finished),
                    'duration' => max(0, (int)($finished - $started)),
                ];
            }
        }
    } catch (Throwable $e) {
        // keep empty state when fallback parsing fails
    }
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
                    <div id="issueAlertBanner" class="issue-alert-banner issue-alert-banner--hidden" role="alert" aria-live="polite">
                        <div class="issue-alert-banner-icon"><i class='bx bx-shield-quarter'></i></div>
                        <div class="issue-alert-banner-content">
                            <p id="issueAlertTitle" class="issue-alert-banner-title">No active issues</p>
                            <p id="issueAlertMessage" class="issue-alert-banner-message">Run a scan to monitor security posture.</p>
                        </div>
                    </div>
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
                                <a href="#" onclick="viewReport(<?= (int)$scan['id'] ?>, <?= json_encode($scan['report_file'] ?? '') ?>); return false;" class="view-report-link">View</a>
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

<style>
/* Sentinel Full-Page Layout Styles */
.sentinel-fullscreen {
    margin: -24px -32px -32px -32px;
    min-height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
    background: #f8fafc;
}

/* Hero Section */
.sentinel-hero {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    padding: 1.75rem 2.5rem;
}

.sentinel-hero-content {
    max-width: 1800px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.sentinel-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: rgba(0,0,0,0.5);
    margin-bottom: 0.5rem;
}

.sentinel-hero-badge i { font-size: 1rem; }

.sentinel-hero-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.sentinel-hero-subtitle {
    font-size: 0.95rem;
    color: rgba(0,0,0,0.65);
    margin: 0.35rem 0 0 0;
}

.sentinel-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.hero-status-pill {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 2rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: #1e293b;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #94a3b8;
}

.status-dot--success { background: #22c55e; }
.status-dot--warning { background: #f59e0b; }
.status-dot--danger { background: #ef4444; }

/* Main Content Area */
.sentinel-main {
    flex: 1;
    padding: 1.5rem 2.5rem 2.5rem;
    max-width: 1800px;
    margin: 0 auto;
    width: 100%;
    box-sizing: border-box;
}

.sentinel-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

/* Cards */
.sentinel-card {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 4px 12px rgba(0,0,0,0.03);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.sentinel-card--wide {
    grid-column: 1;
}

.sentinel-card--table {
    width: 100%;
}

.sentinel-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: rgba(248,250,252,0.6);
    border-bottom: 1px solid #f1f5f9;
}

.sentinel-card-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin: 0;
}

.sentinel-card-title {
    font-size: 1.05rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0.15rem 0 0 0;
}

.sentinel-status-badge {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    color: #64748b;
}

.sentinel-card-body {
    padding: 1.5rem;
}

/* Scan Type Options */
.scan-type-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.scan-type-option {
    position: relative;
    cursor: pointer;
}

.scan-type-option input { display: none; }

.scan-type-content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.scan-type-option:hover .scan-type-content {
    border-color: #fbbf24;
    box-shadow: 0 2px 8px rgba(251,191,36,0.15);
}

.scan-type-option input:checked + .scan-type-content {
    border-color: #fbbf24;
    background: rgba(251,191,36,0.05);
}

.scan-type-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.scan-type-icon i { font-size: 1.25rem; }

.scan-type-icon--amber {
    background: #fef3c7;
    color: #d97706;
}

.scan-type-icon--rose {
    background: #fee2e2;
    color: #dc2626;
}

.scan-type-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.scan-type-desc {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0.2rem 0 0 0;
}

.scan-type-ring {
    position: absolute;
    inset: -2px;
    border-radius: 0.85rem;
    border: 2px solid transparent;
    pointer-events: none;
    transition: all 0.2s ease;
}

.scan-type-option input:checked ~ .scan-type-ring {
    border-color: #fbbf24;
}

/* Scan Actions */
.scan-actions-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1.5rem;
    align-items: end;
}

.scan-email-field label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.5rem;
}

.scan-email-field input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.scan-email-field input:focus {
    outline: none;
    border-color: #fbbf24;
    box-shadow: 0 0 0 3px rgba(251,191,36,0.15);
}

.scan-email-hint {
    font-size: 0.7rem;
    color: #64748b;
    margin: 0.4rem 0 0 0;
}

.scan-buttons {
    display: flex;
    gap: 0.75rem;
}

.btn-scan {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-scan--primary {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #1e293b;
    box-shadow: 0 2px 6px rgba(251,191,36,0.3);
}

.btn-scan--primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(251,191,36,0.4);
}

.btn-scan--primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-scan--secondary {
    background: linear-gradient(135deg, #475569, #334155);
    color: #fff;
    box-shadow: 0 2px 6px rgba(51,65,85,0.2);
}

.btn-scan--secondary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(51,65,85,0.3);
}

/* Progress */
.scan-progress {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.75rem;
}

.scan-progress.hidden { display: none; }

.scan-progress-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
    font-weight: 600;
    color: #92400e;
    margin-bottom: 0.75rem;
}

.scan-progress-bar {
    height: 8px;
    background: #fef3c7;
    border-radius: 4px;
    overflow: hidden;
}

.scan-progress-fill {
    height: 100%;
    width: 0;
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
    border-radius: 4px;
    transition: width 0.3s ease;
}

/* Threat Stats */
.threat-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.threat-stat {
    border-radius: 0.75rem;
    padding: 1rem;
    text-align: center;
    overflow: hidden;
}

.threat-stat--critical {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    border: 1px solid #fecaca;
}

.threat-stat--warning {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a;
}

.threat-stat--info {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    border: 1px solid #c7d2fe;
}

.threat-stat-label {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.threat-stat--critical .threat-stat-label { color: #be123c; }
.threat-stat--warning .threat-stat-label { color: #b45309; }
.threat-stat--info .threat-stat-label { color: #4338ca; }

.threat-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0.5rem 0 0 0;
    line-height: 1;
}

.threat-stat--critical .threat-stat-value { color: #dc2626; }
.threat-stat--warning .threat-stat-value { color: #d97706; }
.threat-stat--info .threat-stat-value { color: #6366f1; }

.last-scan-time {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    margin: 0;
}

.issue-alert-banner {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-top: 0.9rem;
    padding: 0.85rem 0.9rem;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
}

.issue-alert-banner--hidden {
    display: none;
}

.issue-alert-banner--safe {
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.issue-alert-banner--warning {
    border-color: #fde68a;
    background: #fffbeb;
}

.issue-alert-banner--critical {
    border-color: #fecaca;
    background: #fef2f2;
}

.issue-alert-banner-icon {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    background: rgba(15, 23, 42, 0.06);
    color: #334155;
    flex-shrink: 0;
}

.issue-alert-banner--safe .issue-alert-banner-icon {
    color: #166534;
    background: rgba(22, 163, 74, 0.12);
}

.issue-alert-banner--warning .issue-alert-banner-icon {
    color: #b45309;
    background: rgba(245, 158, 11, 0.18);
}

.issue-alert-banner--critical .issue-alert-banner-icon {
    color: #b91c1c;
    background: rgba(220, 38, 38, 0.16);
}

.issue-alert-banner-title {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 700;
    color: #1e293b;
}

.issue-alert-banner-message {
    margin: 0.2rem 0 0;
    font-size: 0.75rem;
    color: #475569;
    line-height: 1.35;
}

.swal-report-popup .swal2-title {
    font-size: 2rem;
    font-weight: 700;
}

.swal-report-popup .swal2-actions {
    gap: 0.6rem;
    margin-top: 0.75rem;
}

.swal-report-btn {
    border: none;
    border-radius: 999px;
    padding: 0.6rem 1rem;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: none;
}

.swal-report-btn.swal-report-btn--pdf {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: #fff;
}

.swal-report-btn.swal-report-btn--email {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
}

.swal-report-btn.swal-report-btn--close {
    background: #e2e8f0;
    color: #1e293b;
}

/* Table */
.sentinel-table-wrapper {
    overflow-x: auto;
}

.sentinel-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.sentinel-table thead {
    background: #f8fafc;
}

.sentinel-table th {
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
}

.sentinel-table td {
    padding: 0.875rem 1rem;
    color: #475569;
    border-bottom: 1px solid #f1f5f9;
}

.sentinel-table tbody tr:hover {
    background: #f8fafc;
}

.scan-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-weight: 600;
}

.scan-type-badge--quick { color: #6366f1; }
.scan-type-badge--full { color: #475569; }
.scan-type-badge--malware { color: #dc2626; }

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-pill--success {
    background: #dcfce7;
    color: #166534;
}

.status-pill--warning {
    background: #fef3c7;
    color: #92400e;
}

.status-pill--danger {
    background: #fee2e2;
    color: #991b1b;
}

.view-report-link {
    color: #6366f1;
    font-weight: 600;
    text-decoration: none;
}

.view-report-link:hover {
    color: #4338ca;
}

/* Empty State */
.sentinel-empty-state {
    padding: 3rem 2rem;
    text-align: center;
}

.sentinel-empty-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 0.75rem;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
}

.sentinel-empty-icon i { font-size: 1.5rem; }

.sentinel-empty-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #475569;
    margin: 0;
}

.sentinel-empty-desc {
    font-size: 0.8rem;
    color: #94a3b8;
    margin: 0.25rem 0 0 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .sentinel-grid {
        grid-template-columns: 1fr;
    }
    
    .scan-actions-row {
        grid-template-columns: 1fr;
    }
    
    .scan-buttons {
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .sentinel-fullscreen {
        margin: -24px -16px -16px -16px;
    }
    
    .sentinel-hero {
        padding: 1.25rem 1rem;
    }
    
    .sentinel-hero-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .sentinel-hero-title { font-size: 1.5rem; }
    
    .sentinel-main {
        padding: 1rem;
    }
    
    .scan-type-grid {
        grid-template-columns: 1fr;
    }
    
    .scan-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-scan {
        width: 100%;
    }
}
</style>

<script>
const ADMIN_BASE = (window.HQ_ADMIN_BASE || '').replace(/\/$/, '');
const APP_BASE = ADMIN_BASE.replace(/\/admin$/, '');

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

    fetch(ADMIN_BASE + '/api/scan-engine.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'scan_type=' + encodeURIComponent(scanType)
    })
    .then(async response => {
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            throw new Error('Invalid JSON response from scan API.');
        }
        if (!response.ok) {
            throw new Error(data.message || 'Scan request failed');
        }
        return data;
    })
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

    updateIssueAlertBanner(critical, warnings, info);
    notifyIfIssuesDetected(critical, warnings);
    
    // Auto-show report modal after scan
    showReportModal();
}

function updateIssueAlertBanner(critical, warnings, info) {
    const banner = document.getElementById('issueAlertBanner');
    const title = document.getElementById('issueAlertTitle');
    const message = document.getElementById('issueAlertMessage');
    if (!banner || !title || !message) return;

    banner.classList.remove('issue-alert-banner--hidden', 'issue-alert-banner--safe', 'issue-alert-banner--warning', 'issue-alert-banner--critical');

    if (critical > 0) {
        banner.classList.add('issue-alert-banner--critical');
        title.textContent = `Critical issues detected: ${critical}`;
        message.textContent = `Warnings: ${warnings}. Action is required now to reduce risk.`;
        return;
    }

    if (warnings > 0) {
        banner.classList.add('issue-alert-banner--warning');
        title.textContent = `Warnings detected: ${warnings}`;
        message.textContent = `No critical issues found. Review warnings and harden sensitive files/configuration.`;
        return;
    }

    banner.classList.add('issue-alert-banner--safe');
    title.textContent = 'No critical findings';
    message.textContent = `Security posture looks clean. Informational findings: ${info}.`;
}

function notifyIfIssuesDetected(critical, warnings) {
    if (critical <= 0 && warnings <= 0) {
        Swal.fire({
            icon: 'success',
            title: 'Scan clean',
            text: 'No critical or warning issues detected.',
            timer: 2200,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        return;
    }

    const icon = critical > 0 ? 'error' : 'warning';
    const title = critical > 0 ? 'Security alert: critical findings' : 'Security alert: warnings found';
    const text = `Critical: ${critical}, Warnings: ${warnings}. Open the report and address them.`;

    Swal.fire({
        icon,
        title,
        text,
        toast: true,
        position: 'top-end',
        timer: critical > 0 ? 5000 : 3800,
        showConfirmButton: false
    });
}

async function viewReport(id, reportFile = '') {
    if (reportFile || (id && id > 0)) {
        try {
            const url = new URL(ADMIN_BASE + '/api/scan-report.php', window.location.origin);
            if (reportFile) {
                url.searchParams.set('file', reportFile);
            } else {
                url.searchParams.set('id', String(id));
            }

            const res = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            const raw = await res.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                throw new Error('Invalid JSON while loading scan report.');
            }

            if (!res.ok || data.status !== 'ok' || !data.report) {
                throw new Error(data.message || 'Report data unavailable for this scan.');
            }

            window.lastScanReport = data.report;
            showReportModal();
            return;
        } catch (error) {
            console.warn('Failed to load historical scan report:', error);
        }
    }

    if (window.lastScanReport) {
        showReportModal();
        return;
    }

    Swal.fire({
        icon: 'info',
        title: 'No Scan Data',
        text: 'Report file for this scan was not found. Run a new scan to generate one.',
        confirmButtonColor: '#f59e0b'
    });
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
    const riskIcons = { CRITICAL: 'bx-error-circle', HIGH: 'bx-error', MEDIUM: 'bx-info-circle', LOW: 'bx-check-circle' };
    
    let html = `
        <div style="text-align: left; max-height: 60vh; overflow-y: auto;">
            <div style="margin-bottom: 14px; padding: 10px 12px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 12px; color: #334155;">
                <strong style="display:block; margin-bottom: 4px;">How to read this report</strong>
                Findings are rule-based security checks from the scan engine (not AI-generated guesses). Each warning is matched by deterministic patterns and system checks.
            </div>
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
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 20px; border-radius: 20px; background: ${riskColors[riskLevel]}; color: white; font-weight: bold; font-size: 12px;">
                    <i class='bx ${riskIcons[riskLevel]}'></i> Risk Level: ${riskLevel}
                </span>
            </div>
    `;
    
    // Critical Issues
    if (critical.length > 0) {
        html += `<div style="margin-bottom: 16px;"><h4 style="display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: bold; color: #dc2626; margin-bottom: 8px; border-bottom: 2px solid #dc2626; padding-bottom: 4px;"><i class='bx bx-error-circle'></i> Critical Issues</h4>`;
        critical.slice(0, 5).forEach(item => {
            html += renderFinding(item, 'critical');
        });
        if (critical.length > 5) html += `<p style="font-size: 11px; color: #666; font-style: italic;">... and ${critical.length - 5} more</p>`;
        html += `</div>`;
    }
    
    // Warnings
    if (warnings.length > 0) {
        html += `<div style="margin-bottom: 16px;"><h4 style="display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: bold; color: #d97706; margin-bottom: 8px; border-bottom: 2px solid #d97706; padding-bottom: 4px;"><i class='bx bx-error'></i> Warnings</h4>`;
        warnings.slice(0, 5).forEach(item => {
            html += renderFinding(item, 'warning');
        });
        if (warnings.length > 5) html += `<p style="font-size: 11px; color: #666; font-style: italic;">... and ${warnings.length - 5} more</p>`;
        html += `</div>`;
    }
    
    // Info
    if (info.length > 0) {
        html += `<div style="margin-bottom: 16px;"><h4 style="display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: bold; color: #2563eb; margin-bottom: 8px; border-bottom: 2px solid #2563eb; padding-bottom: 4px;"><i class='bx bx-info-circle'></i> Information</h4>`;
        info.slice(0, 3).forEach(item => {
            html += renderFinding(item, 'info');
        });
        if (info.length > 3) html += `<p style="font-size: 11px; color: #666; font-style: italic;">... and ${info.length - 3} more</p>`;
        html += `</div>`;
    }
    
    html += `</div>`;
    
    Swal.fire({
        title: '<i class="bx bx-bar-chart-alt-2" style="margin-right: 8px;"></i> Security Scan Report',
        html: html,
        width: '800px',
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: '<i class="bx bxs-file-pdf"></i> Download PDF',
        confirmButtonColor: '#dc2626',
        cancelButtonText: 'Close',
        cancelButtonColor: '#64748b',
        showDenyButton: true,
        denyButtonText: '<i class="bx bx-envelope"></i> Email Report',
        denyButtonColor: '#f59e0b',
        customClass: {
            popup: 'swal-wide swal-report-popup',
            confirmButton: 'swal-report-btn swal-report-btn--pdf',
            denyButton: 'swal-report-btn swal-report-btn--email',
            cancelButton: 'swal-report-btn swal-report-btn--close'
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
    const explanation = getFindingExplanation(item);
    
    let html = `<div style="background: ${colors[type]}; border-left: 4px solid ${borders[type]}; padding: 10px 12px; margin-bottom: 8px; border-radius: 0 6px 6px 0;">`;
    html += `<div style="font-weight: 600; font-size: 12px; color: #1f2937;">${item.message || item.type || 'Issue detected'}</div>`;
    if (explanation) {
        html += `<div style="font-size: 11px; color: #475569; margin-top: 4px;">${escapeHtml(explanation)}</div>`;
    }
    
    if (item.file) {
        html += `<div style="display: flex; align-items: center; gap: 4px; font-size: 11px; color: #6b7280; font-family: monospace; margin-top: 4px;"><i class='bx bx-file'></i> ${item.file}`;
        if (item.line) html += ` <span style="color: ${borders[type]}; font-weight: bold;">Line ${item.line}</span>`;
        html += `</div>`;
    }
    
    if (item.snippet) {
        html += `<div style="font-size: 10px; color: #9ca3af; font-family: monospace; margin-top: 4px; background: #f3f4f6; padding: 4px 8px; border-radius: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(item.snippet)}</div>`;
    }
    
    html += `</div>`;
    return html;
}

function getFindingExplanation(item) {
    const type = (item?.type || '').toLowerCase();
    const msg = (item?.message || '').toLowerCase();

    if (type === 'scan_limit' || msg.includes('limited to')) {
        return 'Quick scan checks up to a fixed file limit for speed. Run Full Scan for deeper coverage.';
    }

    if (type === 'sensitive_file_exposed' || msg.includes('sensitive file') || msg.includes('world-readable')) {
        return 'A sensitive file can be read by broad users/processes. Restrict file permissions and prevent public web access.';
    }

    if (type === 'syntax_error' || msg.includes('syntax')) {
        return 'PHP syntax issue detected during lint check; this can break pages or APIs.';
    }

    if (type === 'webshell' || type === 'exec_shell' || type === 'file_inclusion' || msg.includes('suspicious pattern')) {
        return 'Potentially dangerous code pattern detected by signature matching. Review the file content immediately.';
    }

    return 'Detected by deterministic scan rules. Review this finding and remediate if it is valid for your environment.';
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
    
    fetch(ADMIN_BASE + '/api/send-report.php', {
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
    .then(async response => {
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            throw new Error('Invalid JSON response from report API.');
        }
        if (!response.ok) {
            throw new Error(data.message || 'Failed to generate report');
        }
        return data;
    })
    .then(data => {
        if (data.status === 'success' && data.pdf_path) {
            Swal.fire({
                icon: 'success',
                title: 'PDF Generated!',
                html: `<p>Report saved: <code>${data.pdf_filename}</code></p>
                       <a href="${APP_BASE}/storage/reports/${data.pdf_filename}" download class="swal2-confirm swal2-styled" style="display: inline-block; margin-top: 10px;">
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

    fetch(ADMIN_BASE + '/api/send-report.php', {
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
    .then(async response => {
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            throw new Error('Invalid JSON response from report API.');
        }
        if (!response.ok) {
            throw new Error(data.message || 'Failed to send report');
        }
        return data;
    })
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

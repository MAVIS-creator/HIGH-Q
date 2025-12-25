<?php
// Admin Security Scan page: Sentinel (Tailwind redesign)
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body class="bg-slate-50">
<div class="min-h-screen max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 p-8 shadow-xl text-slate-900">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(0,0,0,0.05),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(0,0,0,0.05),transparent_25%),radial-gradient(circle_at_50%_80%,rgba(0,0,0,0.04),transparent_30%)]"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-amber-900/70">Security Automation</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-bold leading-tight"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="mt-2 text-slate-800/80 max-w-2xl"><?= htmlspecialchars($pageSubtitle) ?></p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs sm:text-sm">
                <span class="inline-flex items-center gap-2 bg-slate-900/10 backdrop-blur-md border border-slate-900/20 rounded-full px-4 py-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span class="text-slate-900">Engine Ready</span>
                </span>
                <span class="inline-flex items-center gap-2 bg-slate-900/10 backdrop-blur-md border border-slate-900/20 rounded-full px-4 py-2">
                    <i class='bx bx-shield'></i>
                    <span class="text-slate-900">Real-time defenses</span>
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Scan Controls -->
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-slate-500">Run a scan</p>
                    <h2 class="text-lg font-semibold text-slate-800">Scan orchestrator</h2>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span>Engine idle</span>
                </div>
            </div>

            <div class="p-5 sm:p-6 space-y-6">
                <div class="grid md:grid-cols-3 gap-4">
                    <label class="group relative border border-slate-200 rounded-xl p-4 hover:border-amber-400 hover:shadow-md transition cursor-pointer">
                        <input type="radio" name="scan_type" value="quick" class="peer sr-only" checked>
                        <div class="flex items-start gap-3">
                            <div class="h-10 w-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"><i class='bx bx-bolt-circle text-xl'></i></div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Quick Scan</p>
                                <p class="text-xs text-slate-500">Fast health check (2-5 min)</p>
                            </div>
                        </div>
                        <div class="absolute inset-0 rounded-xl ring-2 ring-amber-400 ring-offset-2 opacity-0 peer-checked:opacity-100 transition"></div>
                    </label>

                    <label class="group relative border border-slate-200 rounded-xl p-4 hover:border-amber-400 hover:shadow-md transition cursor-pointer">
                        <input type="radio" name="scan_type" value="full" class="peer sr-only">
                        <div class="flex items-start gap-3">
                            <div class="h-10 w-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"><i class='bx bx-target-lock text-xl'></i></div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Full Scan</p>
                                <p class="text-xs text-slate-500">Complete audit (10-15 min)</p>
                            </div>
                        </div>
                        <div class="absolute inset-0 rounded-xl ring-2 ring-amber-400 ring-offset-2 opacity-0 peer-checked:opacity-100 transition"></div>
                    </label>

                    <label class="group relative border border-slate-200 rounded-xl p-4 hover:border-amber-400 hover:shadow-md transition cursor-pointer">
                        <input type="radio" name="scan_type" value="malware" class="peer sr-only">
                        <div class="flex items-start gap-3">
                            <div class="h-10 w-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center"><i class='bx bx-bug-alt text-xl'></i></div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Malware Scan</p>
                                <p class="text-xs text-slate-500">Threat sweep (5-10 min)</p>
                            </div>
                        </div>
                        <div class="absolute inset-0 rounded-xl ring-2 ring-amber-400 ring-offset-2 opacity-0 peer-checked:opacity-100 transition"></div>
                    </label>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="space-y-2">
                        <label for="reportEmail" class="text-sm font-semibold text-slate-700">Email report to</label>
                        <input id="reportEmail" type="email" placeholder="Leave blank to use company email" class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm shadow-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-100" />
                        <p class="text-xs text-slate-500">We will send the latest scan report when you click Email Report.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 items-end">
                        <button id="scanBtn" onclick="startScan()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-amber-400 to-amber-500 px-4 py-3 text-sm font-semibold text-slate-900 shadow-md hover:shadow-lg transition disabled:opacity-50">
                            <i class='bx bx-play'></i>
                            Start Scan
                        </button>
                        <button onclick="emailReport()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-slate-700 to-slate-800 px-4 py-3 text-sm font-semibold text-white shadow-md hover:shadow-lg transition">
                            <i class='bx bx-envelope'></i>
                            Email Report
                        </button>
                    </div>
                </div>

                <div id="scanProgress" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center justify-between text-sm text-amber-700">
                        <span id="scanStatus" class="font-semibold">Initializing scan...</span>
                        <span id="progressText" class="font-semibold">0%</span>
                    </div>
                    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-amber-100">
                        <div id="progressFill" class="h-2 w-0 rounded-full bg-gradient-to-r from-amber-400 to-amber-500 transition-all duration-300"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-slate-500">Latest results</p>
                    <h2 class="text-lg font-semibold text-slate-800">Threat dashboard</h2>
                </div>
            </div>
            <div class="p-5 sm:p-6 space-y-5">
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-gradient-to-br from-rose-50 to-rose-100 border border-rose-200/80 p-4 text-center">
                        <p class="text-xs font-semibold text-rose-700">Critical</p>
                        <p id="criticalCount" class="mt-2 text-3xl font-bold text-rose-700">0</p>
                    </div>
                    <div class="rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200/80 p-4 text-center">
                        <p class="text-xs font-semibold text-amber-700">Warnings</p>
                        <p id="warningCount" class="mt-2 text-3xl font-bold text-amber-700">0</p>
                    </div>
                    <div class="rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200/80 p-4 text-center">
                        <p class="text-xs font-semibold text-indigo-700">Info</p>
                        <p id="infoCount" class="mt-2 text-3xl font-bold text-indigo-700">0</p>
                    </div>
                </div>
                <p id="lastScanTime" class="text-xs font-semibold text-slate-500">No scans performed yet</p>
            </div>
        </div>
    </div>

    <!-- Reports -->
    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
        <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-100 bg-slate-50/60">
            <div>
                <p class="text-xs font-semibold tracking-wide text-slate-500">History</p>
                <h2 class="text-lg font-semibold text-slate-800">Recent scan reports</h2>
            </div>
        </div>
        <?php if (count($latestScans) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Scan Type</th>
                        <th class="px-4 py-3 text-left">Date & Time</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Threats</th>
                        <th class="px-4 py-3 text-left">Duration</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($latestScans as $scan): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <?php if ($scan['scan_type'] === 'quick'): ?>
                                <span class="inline-flex items-center gap-2 text-indigo-700 font-semibold"><i class='bx bx-bolt-circle'></i>Quick</span>
                            <?php elseif ($scan['scan_type'] === 'full'): ?>
                                <span class="inline-flex items-center gap-2 text-slate-700 font-semibold"><i class='bx bx-target-lock'></i>Full</span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-2 text-rose-700 font-semibold"><i class='bx bx-bug-alt'></i>Malware</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-slate-700"><?= date('M d, Y H:i', strtotime($scan['scan_date'])) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($scan['status'] === 'completed'): ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">✓ Completed</span>
                            <?php elseif ($scan['status'] === 'running'): ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 px-3 py-1 text-xs font-semibold">⟳ Running</span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 text-rose-700 px-3 py-1 text-xs font-semibold">✗ Error</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            <?= (int)$scan['threat_count'] ?> threat<?= $scan['threat_count'] != 1 ? 's' : '' ?>
                        </td>
                        <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($scan['duration']) ?>s</td>
                        <td class="px-4 py-3">
                            <a href="#" onclick="viewReport(<?= $scan['id'] ?>); return false;" class="text-indigo-600 font-semibold hover:text-indigo-800">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-8 text-center text-slate-500">
            <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                <i class='bx bxs-shield-alt-2 text-2xl'></i>
            </div>
            <p class="font-semibold">No scans performed yet</p>
            <p class="text-sm">Run your first scan to see reports here.</p>
        </div>
        <?php endif; ?>
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
}

function viewReport(id) {
    if (!window.lastScanReport) {
        alert('No scan data available. Run a scan first.');
        return;
    }

    const critical = window.lastScanReport.critical || [];
    const warnings = window.lastScanReport.warnings || [];
    const info = window.lastScanReport.info || [];

    let msg = 'Scan Report\n\n';
    msg += 'CRITICAL (' + critical.length + '):\n';
    critical.slice(0, 5).forEach(item => { msg += '  • ' + (item.message || item.type || 'Issue') + '\n'; });

    msg += '\nWARNINGS (' + warnings.length + '):\n';
    warnings.slice(0, 5).forEach(item => { msg += '  • ' + (item.message || item.type || 'Warning') + '\n'; });

    msg += '\nINFO (' + info.length + '):\n';
    info.slice(0, 3).forEach(item => { msg += '  • ' + (item.message || item.type || 'Info') + '\n'; });

    alert(msg);
}

function emailReport() {
    if (!window.lastScanReport) {
        alert('No scan data available. Run a scan first.');
        return;
    }

    const recipient = document.getElementById('reportEmail').value || '';
    const button = event.target;
    button.disabled = true;
    const originalText = button.textContent;
    button.textContent = 'Sending...';

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
            send_email: true
        })
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;
        button.textContent = originalText;

        if (data.status === 'success' && data.sent) {
            alert('Report sent to ' + (recipient || 'company email'));
        } else if (data.status === 'success') {
            alert('Report generated but email could not be sent. Check email configuration.');
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        button.disabled = false;
        button.textContent = originalText;
        alert('Error sending report: ' + error.message);
    });
}
</script>

</body>
</html>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

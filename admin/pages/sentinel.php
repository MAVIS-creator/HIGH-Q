<?php
// Admin Security Scan page: Sentinel
$pageTitle = 'Security Scan';
$pageSubtitle = 'Run advanced security checks and view reports.';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requirePermission('sentinel');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="admin-main">
    <div class="page-header">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p><?= htmlspecialchars($pageSubtitle) ?></p>
    </div>
    <div class="card">
        <h3>Run Full Security Scan</h3>
        <form method="get" action="../modules/sentinel.php" target="scanFrame">
            <button type="submit" class="btn-approve">Run Scan</button>
            <a href="../modules/sentinel.php?set_baseline=1" target="scanFrame" class="btn-ghost" style="margin-left:10px;">Set Baseline</a>
        </form>
        <iframe name="scanFrame" style="width:100%;height:400px;border:1px solid #eee;margin-top:18px;background:#fafbff;"></iframe>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
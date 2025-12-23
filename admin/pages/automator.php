<?php
// Admin Automator page
$pageTitle = 'Automator';
$pageSubtitle = 'SEO and maintenance automation.';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requirePermission('automator');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="admin-main">
    <div class="page-header">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p><?= htmlspecialchars($pageSubtitle) ?></p>
    </div>
    <div class="card">
        <iframe src="../modules/automator.php" style="width:100%;height:600px;border:none;background:#fafbff;"></iframe>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
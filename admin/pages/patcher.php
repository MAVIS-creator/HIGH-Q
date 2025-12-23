<?php
// Admin Smart Patcher page
$pageTitle = 'Smart Patcher';
$pageSubtitle = 'Safely edit code with backups and diff preview.';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requirePermission('patcher');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="admin-main">
    <div class="page-header">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p><?= htmlspecialchars($pageSubtitle) ?></p>
    </div>
    <div class="card">
        <iframe src="../modules/patcher_ui.php" style="width:100%;height:700px;border:none;background:#fafbff;"></iframe>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
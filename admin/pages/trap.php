<?php
// Admin Canary Trap page
$pageTitle = 'Canary Trap';
$pageSubtitle = 'Active defense: canary tokens.';

require_once __DIR__ . '/../admin/includes/auth.php';
require_once __DIR__ . '/../admin/includes/db.php';
requirePermission('trap');
require_once __DIR__ . '/../admin/includes/header.php';
require_once __DIR__ . '/../admin/includes/sidebar.php';
?>
<style>
    .trap-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
    }
    
    .trap-header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
        font-weight: 600;
    }
    
    .trap-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 14px;
    }
    
    .trap-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    
    .trap-container iframe {
        width: 100%;
        height: 700px;
        border: none;
        display: block;
    }
</style>

<div class="admin-main" style="max-width: 1200px; margin: 0 auto;">
    <div class="trap-header">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p><?= htmlspecialchars($pageSubtitle) ?></p>
    </div>
    <div class="trap-container">
        <iframe src="../modules/trap.php"></iframe>
    </div>
</div>
<?php require_once __DIR__ . '/../admin/includes/footer.php'; ?>

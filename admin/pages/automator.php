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
<style>
    .automator-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .automator-header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
        font-weight: 600;
    }
    
    .automator-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 14px;
    }
    
    .automator-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    
    .automator-container iframe {
        width: 100%;
        height: 700px;
        border: none;
        display: block;
    }
</style>

<div class="admin-main" style="max-width: 1200px; margin: 0 auto;">
    <div class="automator-header">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p><?= htmlspecialchars($pageSubtitle) ?></p>
    </div>
    <div class="automator-container">
        <iframe src="../modules/automator.php"></iframe>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
// Admin Automator page - Improved styling
$pageTitle = 'Automator';
$pageSubtitle = 'SEO and maintenance automation.';

require_once __DIR__ . '/../admin/includes/auth.php';
require_once __DIR__ . '/../admin/includes/db.php';
requirePermission('automator');
require_once __DIR__ . '/../admin/includes/header.php';
require_once __DIR__ . '/../admin/includes/sidebar.php';
?>
<style>
    .automator-wrapper {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .automator-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.35);
        position: relative;
        overflow: hidden;
    }
    
    .automator-header::before {
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
    
    .automator-header-content {
        position: relative;
        z-index: 1;
    }
    
    .automator-header h1 {
        margin: 0 0 12px 0;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    
    .automator-header p {
        margin: 0;
        opacity: 0.96;
        font-size: 15px;
        font-weight: 300;
    }
    
    .automator-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e5e7eb;
        transition: box-shadow 0.3s ease;
    }
    
    .automator-container:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .automator-container iframe {
        width: 100%;
        height: 750px;
        border: none;
        display: block;
        background: #fafbff;
    }
    
    @media (max-width: 768px) {
        .automator-header {
            padding: 30px 20px;
        }
        
        .automator-header h1 {
            font-size: 24px;
        }
        
        .automator-header p {
            font-size: 14px;
        }
        
        .automator-container iframe {
            height: 600px;
        }
    }
</style>

<div class="admin-main automator-wrapper" style="max-width: 1200px; margin: 0 auto;">
    <div class="automator-header">
        <div class="automator-header-content">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <p><?= htmlspecialchars($pageSubtitle) ?></p>
        </div>
    </div>
    <div class="automator-container">
        <iframe src="../modules/automator.php"></iframe>
    </div>
</div>
<?php require_once __DIR__ . '/../admin/includes/footer.php'; ?>

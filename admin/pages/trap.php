<?php
// Admin Canary Trap page - Improved styling
$pageTitle = 'Canary Trap';
$pageSubtitle = 'Active defense: canary tokens.';

require_once __DIR__ . '/../admin/includes/auth.php';
require_once __DIR__ . '/../admin/includes/db.php';
requirePermission('trap');
require_once __DIR__ . '/../admin/includes/header.php';
require_once __DIR__ . '/../admin/includes/sidebar.php';
?>
<style>
    .trap-wrapper {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .trap-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 40px 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 8px 16px rgba(245, 87, 108, 0.35);
        position: relative;
        overflow: hidden;
    }
    
    .trap-header::before {
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
    
    .trap-header-content {
        position: relative;
        z-index: 1;
    }
    
    .trap-header h1 {
        margin: 0 0 12px 0;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    
    .trap-header p {
        margin: 0;
        opacity: 0.96;
        font-size: 15px;
        font-weight: 300;
    }
    
    .trap-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e5e7eb;
        transition: box-shadow 0.3s ease;
    }
    
    .trap-container:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .trap-container iframe {
        width: 100%;
        height: 750px;
        border: none;
        display: block;
        background: #fafbff;
    }
    
    @media (max-width: 768px) {
        .trap-header {
            padding: 30px 20px;
        }
        
        .trap-header h1 {
            font-size: 24px;
        }
        
        .trap-header p {
            font-size: 14px;
        }
        
        .trap-container iframe {
            height: 600px;
        }
    }
</style>

<div class="admin-main trap-wrapper" style="max-width: 1200px; margin: 0 auto;">
    <div class="trap-header">
        <div class="trap-header-content">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <p><?= htmlspecialchars($pageSubtitle) ?></p>
        </div>
    </div>
    <div class="trap-container">
        <iframe src="../modules/trap.php"></iframe>
    </div>
</div>
<?php require_once __DIR__ . '/../admin/includes/footer.php'; ?>

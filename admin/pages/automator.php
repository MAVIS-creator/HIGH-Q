<?php
// Admin Automator page - Full-page layout
$pageTitle = 'Automator';
$pageSubtitle = 'SEO optimization and maintenance automation tools';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requirePermission('automator');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="admin-page-fullscreen">
    <!-- Hero Header -->
    <div class="fullscreen-hero fullscreen-hero--indigo">
        <div class="fullscreen-hero-content">
            <div class="fullscreen-hero-text">
                <span class="fullscreen-hero-badge"><i class='bx bx-cog'></i> Automation Suite</span>
                <h1 class="fullscreen-hero-title"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="fullscreen-hero-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
            </div>
            <div class="hero-status-pill">
                <span class="status-dot status-dot--success"></span>
                <span>Automation Ready</span>
            </div>
        </div>
    </div>

    <!-- Embedded Module -->
    <div class="fullscreen-module">
        <div class="module-toolbar">
            <div class="module-toolbar-info">
                <i class='bx bx-bot'></i>
                <div>
                    <h2>Workflow Console</h2>
                    <p>Manage automated tasks and workflows</p>
                </div>
            </div>
            <span class="module-toolbar-status">
                <span class="status-dot status-dot--success"></span>
                Embedded Module
            </span>
        </div>
        <div class="module-frame-container">
            <iframe src="../modules/automator.php" class="module-frame"></iframe>
        </div>
    </div>
</div>

<style>
.admin-page-fullscreen {
    margin: -24px -32px -32px -32px;
    min-height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
}

.fullscreen-hero {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    padding: 1.75rem 2.5rem;
}

.fullscreen-hero--indigo {
    background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
}

.fullscreen-hero--indigo .fullscreen-hero-badge,
.fullscreen-hero--indigo .fullscreen-hero-title {
    color: #fff;
}

.fullscreen-hero--indigo .fullscreen-hero-subtitle {
    color: rgba(255,255,255,0.85);
}

.fullscreen-hero-content {
    max-width: 1800px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.fullscreen-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: rgba(0,0,0,0.6);
    margin-bottom: 0.5rem;
}

.fullscreen-hero-badge i {
    font-size: 1rem;
}

.fullscreen-hero-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.fullscreen-hero-subtitle {
    font-size: 0.95rem;
    color: rgba(0,0,0,0.7);
    margin: 0.35rem 0 0 0;
}

.hero-status-pill {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.1rem;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 2rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: #fff;
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

.fullscreen-module {
    flex: 1;
    background: #0f172a;
    display: flex;
    flex-direction: column;
}

.module-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 2rem;
    background: #1e293b;
    border-bottom: 1px solid #334155;
}

.module-toolbar-info {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    color: #fff;
}

.module-toolbar-info i {
    font-size: 1.5rem;
    color: #818cf8;
}

.module-toolbar-info h2 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.module-toolbar-info p {
    font-size: 0.8rem;
    color: #94a3b8;
    margin: 0.15rem 0 0 0;
}

.module-toolbar-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #94a3b8;
}

.module-frame-container {
    flex: 1;
    display: flex;
}

.module-frame {
    flex: 1;
    border: none;
    min-height: 750px;
    width: 100%;
}

@media (max-width: 768px) {
    .admin-page-fullscreen {
        margin: -24px -16px -16px -16px;
    }
    
    .fullscreen-hero {
        padding: 1.25rem 1rem;
    }
    
    .fullscreen-hero-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .fullscreen-hero-title {
        font-size: 1.5rem;
    }
    
    .module-toolbar {
        flex-direction: column;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

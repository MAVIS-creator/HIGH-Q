<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

requirePermission('ai_queue');

$pageTitle = 'AI Review Queue';
$pageSubtitle = 'Approve or reject AI-proposed tasks before any action is taken';
$csrf = function_exists('generateToken') ? generateToken('ai_action_review_api') : '';

$items = [];
try {
    $stmt = $pdo->query("SELECT q.*, u.name AS user_name, u.email AS user_email, r.name AS role_name
                         FROM ai_action_queue q
                         LEFT JOIN users u ON u.id = q.user_id
                         LEFT JOIN roles r ON r.id = u.role_id
                         ORDER BY q.created_at DESC
                         LIMIT 100");
    $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    $items = [];
}
?>

<div class="dashboard-container">
    <div class="page-hero">
        <div class="page-hero-content">
            <div>
                <span class="page-hero-badge"><i class='bx bx-list-check'></i> AI Review Queue</span>
                <h1 class="page-hero-title">AI Review Queue</h1>
                <p class="page-hero-subtitle">Review assistant suggestions before they are approved for follow-up handling.</p>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class='bx bx-shield-quarter'></i> Review Rules</h3>
        </div>
        <div class="admin-card-body">
            <ul style="margin:0;padding-left:18px;line-height:1.8;">
                <li>AI suggestions are never executed automatically.</li>
                <li>Every queue decision is logged for auditability.</li>
                <li>Review actions update the queue status only.</li>
            </ul>
        </div>
    </div>

    <div class="admin-card" style="margin-top:16px;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class='bx bx-receipt'></i> Pending Suggestions</h3>
        </div>
        <div class="admin-card-body">
            <?php if (empty($items)): ?>
                <p style="margin:0;">No AI proposals are waiting for review.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Proposal</th>
                                <th>Submitted By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= (int)$item['id'] ?></td>
                                    <td><?= htmlspecialchars($item['action_type'] ?? '') ?></td>
                                    <td style="max-width:420px;white-space:normal;"><?= nl2br(htmlspecialchars($item['proposal'] ?? '')) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($item['user_name'] ?? 'Unknown') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($item['role_name'] ?? 'N/A') ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($item['status'] ?? '') ?></span></td>
                                    <td>
                                        <?php if (($item['status'] ?? '') === 'queued'): ?>
                                            <form class="d-inline ai-review-form" method="post" action="../api/ai_action_review.php" style="margin-right:6px;">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                <input type="hidden" name="queue_id" value="<?= (int)$item['id'] ?>">
                                                <input type="hidden" name="decision" value="approved">
                                                <button class="btn btn-success btn-sm" type="submit"><i class='bx bx-check'></i> Approve</button>
                                            </form>
                                            <form class="d-inline ai-review-form" method="post" action="../api/ai_action_review.php">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                <input type="hidden" name="queue_id" value="<?= (int)$item['id'] ?>">
                                                <input type="hidden" name="decision" value="rejected">
                                                <button class="btn btn-danger btn-sm" type="submit"><i class='bx bx-x'></i> Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Reviewed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    const adminBase = (window.HQ_ADMIN_PATH || '').replace(/\/$/, '');
    const reviewUrl = adminBase ? adminBase + '/api/ai_action_review.php' : '../api/ai_action_review.php';
    const forms = document.querySelectorAll('.ai-review-form');
    forms.forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
            }
            try {
                const response = await fetch(reviewUrl, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok || data.status !== 'ok') {
                    throw new Error(data.message || 'Unable to update queue item');
                }
                window.location.reload();
            } catch (err) {
                alert(err.message || 'Unable to update queue item');
                if (btn) btn.disabled = false;
            }
        });
    });
})();
</script>
